<?php
namespace Kernel\Session\Drivers;

use Kernel\Session\SessionDriverInterface;
use Kernel\Database\DB;
use RuntimeException;

class DatabaseSessionDriver implements SessionDriverInterface
{
    protected bool $started = false;
    protected array $config = [];
    protected string $sessionId;

    private const CSRF_KEY = '_token';

    public function __construct(array $config = [])
    {
        // 读取 session.database 分支；不存在则用 session 顶层
        $this->config = $config ?: (config('session.database') ?? []);
        $cookieName   = config('session.cookie', 'jnm_session');

        // 取 cookie 或生成新 id（32 hex）
        $this->sessionId = $_COOKIE[$cookieName] ?? bin2hex(random_bytes(16));

        if (defined('DEBUG') && DEBUG) {
            $this->checkTableExists();
        }
    }

    public function start(): bool
    {
        if ($this->started) return true;

        // 触发表存在 & 更新 last_activity / 会话级过期
        $this->touchSessionRow();

        // 设置标准 Cookie（SameSite/Secure/HttpOnly/Domain/Path）
        $this->setSessionCookie($this->sessionId);

        return $this->started = true;
    }

    public function id(): ?string
    {
        return $this->sessionId ?? null;
    }

    public function regenerate(bool $deleteOldSession = false): void
    {
        $oldId = $this->sessionId;
        $this->sessionId = bin2hex(random_bytes(16));

        if ($deleteOldSession) {
            DB::table($this->table())->where('id', $oldId)->delete();
        }

        // 新 id 建立/更新记录并下发新 cookie
        $this->touchSessionRow();
        $this->setSessionCookie($this->sessionId);
    }

    /**
     * 返回所有“未过期”的键（扁平化值，不含元数据）
     */
    public function all(): array
    {
        $record = $this->readRow();

        // 会话过期直接空
        if ($this->isSessionRowExpired($record)) {
            $this->deleteRow();
            return [];
        }

        $payload = $this->decodePayload($record['payload'] ?? '{}');
        $now = time();
        $out = [];

        foreach ($payload as $k => $v) {
            // 兼容：标量 => 永不过期；结构化 => 检查 expires_at
            if (is_array($v)) {
                $exp = $v['expires_at'] ?? null;
                if ($exp !== null && $exp < $now) {
                    // 惰性清理：过期 key 删除
                    unset($payload[$k]);
                    continue;
                }
                $out[$k] = array_key_exists('value', $v) ? $v['value'] : null;
            } else {
                $out[$k] = $v;
            }
        }

        // 有惰性清理则回写
        if (count($out) !== count($payload)) {
            $this->writePayload($payload); // 保持 DB 干净
        }

        return $out;
    }

    public function has(string $key): bool
    {
        $record = $this->readRow();
        if ($this->isSessionRowExpired($record)) {
            $this->deleteRow();
            return false;
        }

        $payload = $this->decodePayload($record['payload'] ?? '{}');

        if (!array_key_exists($key, $payload)) {
            return false;
        }

        $v = $payload[$key];
        if (is_array($v)) {
            $exp = $v['expires_at'] ?? null;
            if ($exp !== null && $exp < time()) {
                // 过期惰性清理
                unset($payload[$key]);
                $this->writePayload($payload);
                return false;
            }
            return array_key_exists('value', $v);
        }

        // 标量视为存在且不过期
        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $record = $this->readRow();
        if ($this->isSessionRowExpired($record)) {
            $this->deleteRow();
            return $default;
        }

        $payload = $this->decodePayload($record['payload'] ?? '{}');

        if (!array_key_exists($key, $payload)) {
            return $default;
        }

        $v = $payload[$key];
        if (is_array($v)) {
            $exp = $v['expires_at'] ?? null;
            if ($exp !== null && $exp < time()) {
                unset($payload[$key]);
                $this->writePayload($payload);
                return $default;
            }
            return array_key_exists('value', $v) ? $v['value'] : $default;
        }

        return $v;
    }

    /**
     * 设置数据，支持 key 级过期（秒）
     * 存储形态统一为： scalar 或 ['value'=>mixed, 'expires_at'=>?int]
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $record  = $this->readRow();
        $payload = $this->decodePayload($record['payload'] ?? '{}');

        if ($ttl !== null) {
            $payload[$key] = [
                'value'      => $value,
                'expires_at' => time() + (int)$ttl,
            ];
        } else {
            // 为节约体积，无 TTL 时存标量
            $payload[$key] = $value;
        }

        $this->writePayload($payload); // 同时刷新会话级过期 & last_activity
    }

    public function forget(string $key): void
    {
        $record  = $this->readRow();
        $payload = $this->decodePayload($record['payload'] ?? '{}');

        if (array_key_exists($key, $payload)) {
            unset($payload[$key]);
            $this->writePayload($payload);
        }
    }

    public function clear(): void
    {
        $this->writePayload([]);
    }

    public function destroy(): void
    {
        $this->deleteRow();
        $this->expireSessionCookie(); // 同时让浏览器侧失效
        $this->started = false;
    }

    public function save(): void
    {
        // DB 驱动为实时写入，无需额外操作
    }

    public function token(): string
    {
        if (!$this->has(self::CSRF_KEY)) {
            return $this->regenerateToken();
        }
        return (string)$this->get(self::CSRF_KEY, "");
    }

    public function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->set(self::CSRF_KEY, $token);
        return $token;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * 判断指定 key 是否过期（不读取全量值）
     */
    public function isExpired(string $key): bool
    {
        $record = $this->readRow();
        if ($this->isSessionRowExpired($record)) {
            return true; // 会话整体过期则视为 key 过期
        }

        $payload = $this->decodePayload($record['payload'] ?? '{}');
        if (!array_key_exists($key, $payload)) return false;

        $v = $payload[$key];
        if (!is_array($v)) return false;

        $exp = $v['expires_at'] ?? null;
        return $exp !== null && $exp < time();
    }

    /**
     * 设置 key 的过期时间（秒）
     */
    public function expire(string $key, int $ttl): void
    {
        $record  = $this->readRow();
        $payload = $this->decodePayload($record['payload'] ?? '{}');

        if (!array_key_exists($key, $payload)) {
            return;
        }

        $exp = time() + (int)$ttl;
        if (is_array($payload[$key])) {
            $payload[$key]['expires_at'] = $exp;
            if (!array_key_exists('value', $payload[$key])) {
                $payload[$key]['value'] = null;
            }
        } else {
            // 标量 -> 结构化
            $payload[$key] = [
                'value'      => $payload[$key],
                'expires_at' => $exp,
            ];
        }

        $this->writePayload($payload);
    }

    /* ---------------- 内部方法 ---------------- */

    protected function table(): string
    {
        return $this->config['table'] ?? 'sessions';
    }

    /**
     * 读取一行；若不存在返回 []
     */
    protected function readRow(): array
    {
        $row = DB::table($this->table())->where('id', $this->sessionId)->first();
        if (!$row) return [];
        // 强转数组（兼容 stdClass）
        return (array)$row;
    }

    /**
     * 会话是否整体过期（基于表字段 expires_at）
     */
    protected function isSessionRowExpired(array $row): bool
    {
        if (empty($row)) return false;
        if (!array_key_exists('expires_at', $row) || $row['expires_at'] === null) {
            return false;
        }
        // 兼容：字段可能是 int 时间戳或字符串日期
        $exp = is_numeric($row['expires_at']) ? (int)$row['expires_at'] : strtotime($row['expires_at']);
        return $exp !== false && $exp < time();
    }

    /**
     * 写入 payload，并刷新 last_activity + 会话级过期
     */
    protected function writePayload(array $payload): void
    {
        $now = time();
        $lifetimeSec = (int)(config('session.lifetime', 120)) * 60;

        DB::table($this->table())->updateOrInsert(
            ['id' => $this->sessionId],
            [
                'payload'       => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'last_activity' => $now,
                // 记录级过期时间 = now + lifetime（会话层面）
                'expires_at'    => $now + $lifetimeSec,
            ]
        );

        // 刷新浏览器 cookie
        $this->setSessionCookie($this->sessionId);
    }

    protected function deleteRow(): void
    {
        DB::table($this->table())->where('id', $this->sessionId)->delete();
    }

    protected function decodePayload(null|string|array $raw): array
    {
        if (is_array($raw)) return $raw;
        if (!is_string($raw) || $raw === '') return [];
        $arr = json_decode($raw, true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * 确保会话行存在（若不存在则创建空 payload），并刷新会话级过期
     */
    protected function touchSessionRow(): void
    {
        $row = $this->readRow();
        if (empty($row)) {
            $this->writePayload([]); // 会自动创建行并设置会话过期
            return;
        }
        // 只刷新会话级过期与 last_activity，不改 payload
        $now = time();
        $lifetimeSec = (int)(config('session.lifetime', 120)) * 60;

        DB::table($this->table())->where('id', $this->sessionId)->update([
            'last_activity' => $now,
            'expires_at'    => $now + $lifetimeSec,
        ]);
    }

    /**
     * 统一设置 Cookie（SameSite/Secure/HttpOnly/Domain/Path）
     */
    protected function setSessionCookie(string $sid): void
    {
        $cookieName = config('session.cookie', 'jnm_session');
        $lifetimeSec = (int)(config('session.lifetime', 120)) * 60;

        $path   = config('session.path', '/');
        $domain = config('session.domain', '');
        $secure = (bool)config('session.secure', false);
        $httpOnly = (bool)config('session.http_only', true);
        $sameSite = config('session.same_site', 'Lax');

        if (strcasecmp($sameSite, 'None') === 0) {
            $secure = true; // 浏览器要求
        }

        // PHP 7.3+ 支持数组参数
        setcookie($cookieName, $sid, [
            'expires'  => time() + $lifetimeSec,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);
    }

    protected function expireSessionCookie(): void
    {
        $cookieName = config('session.cookie', 'jnm_session');

        $path   = config('session.path', '/');
        $domain = config('session.domain', '');
        $secure = (bool)config('session.secure', false);
        $httpOnly = (bool)config('session.http_only', true);
        $sameSite = config('session.same_site', 'Lax');

        if (strcasecmp($sameSite, 'None') === 0) {
            $secure = true;
        }

        setcookie($cookieName, '', [
            'expires'  => time() - 3600,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);
    }

    /**
     * DEBUG：不存在即创建表；不同数据库用通用列
     * 注意：这里将 expires_at 定义为 **INT**（Unix 时间戳），避免各库 timestamp 差异。
     */
    protected function checkTableExists(): void
    {
        try {
            $tableName = $this->table();
            $conn   = DB::getCapsule()->getConnection();
            $schema = $conn->getSchemaBuilder();

            if (!$schema->hasTable($tableName)) {
                $schema->create($tableName, function ($table) {
                    $table->string('id', 64)->primary();
                    $table->integer('user_id')->nullable();
                    $table->longText('payload');      // JSON
                    $table->integer('last_activity'); // Unix 时间戳
                    $table->integer('expires_at')->nullable(); // 会话级过期，Unix 时间戳
                });

                if (defined('DEBUG') && DEBUG) {
                    throw new RuntimeException("Session 表 [$tableName] 不存在，已自动创建。请刷新页面再次访问。");
                }
            }
        } catch (\Throwable $e) {
            throw new RuntimeException("检查/创建 Session 表失败: " . $e->getMessage(), 0, $e);
        }
    }
}
