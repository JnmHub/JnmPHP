<?php
namespace Kernel\Session\Drivers;

use Kernel\Session\SessionDriverInterface;
use Kernel\Database\DB;
use RuntimeException;

/**
 * Class DatabaseSessionDriver
 *
 * 将 Session 数据存储在数据库中。
 * 自动检测并创建 sessions 表，兼容 MySQL/SQLite/Postgres/SQLServer。
 */
class DatabaseSessionDriver implements SessionDriverInterface
{
    protected bool $started = false;
    protected array $config = [];
    protected string $sessionId;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('session.database');
        $cookieName   = config('session.cookie', 'jnm_session');
        $this->sessionId = $_COOKIE[$cookieName] ?? bin2hex(random_bytes(16));

        // DEBUG 下检查表
        if (defined('DEBUG') && DEBUG) {
            $this->checkTableExists();
        }
    }

    public function start(): bool
    {
        return $this->started = true;
    }

    public function id(): ?string
    {
        return $this->sessionId;
    }

    public function regenerate(bool $deleteOldSession = false): void
    {
        $oldId = $this->sessionId;
        $this->sessionId = bin2hex(random_bytes(16));

        if ($deleteOldSession) {
            DB::table($this->config['table'])->where('id', $oldId)->delete();
        }

        setcookie(config('session.cookie'), $this->sessionId, time() + config('session.lifetime') * 60, '/');
    }

    public function all(): array
    {
        $record = $this->read();
        return json_decode($record['payload'] ?? '{}', true);
    }

    public function has(string $key): bool
    {
        $data = $this->all();
        return array_key_exists($key, $data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->all();
        return $data[$key] ?? $default;
    }

    /**
     * 设置数据，并支持独立过期时间（秒）
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $data = $this->all();
        $data[$key] = $value;

        // 计算过期时间，单位为秒
        $expiresAt = $ttl ? time() + $ttl : null;

        // 存入数据库
        $this->write($data, $expiresAt);
    }

    public function forget(string $key): void
    {
        $data = $this->all();
        unset($data[$key]);
        $this->write($data);
    }

    public function clear(): void
    {
        $this->write([]);
    }

    public function destroy(): void
    {
        DB::table($this->config['table'])->where('id', $this->sessionId)->delete();
    }

    public function save(): void
    {
        // database 驱动是实时写入的，这里可空
    }

    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->regenerateToken();
        }
        return $this->get('_token');
    }

    public function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->set('_token', $token);
        return $token;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * 判断指定的 session key 是否过期
     *
     * @param string $key
     * @return bool
     */
    public function isExpired(string $key): bool
    {
        $record = $this->read();

        // 如果没有 expires_at 字段，认为不过期
        if (!isset($record['payload'][$key]['expires_at'])) {
            return false;
        }

        return $record['payload'][$key]['expires_at'] < time(); // 比较过期时间
    }

    /**
     * 单独设置 session key 的过期时间
     *
     * @param string $key
     * @param int $ttl 过期秒数
     */
    public function expire(string $key, int $ttl): void
    {
        $data = $this->all();
        $data[$key]['expires_at'] = time() + $ttl;  // 更新 expires_at

        // 更新数据库
        $this->write($data);
    }

    /**
     * 从数据库读取当前会话
     */
    protected function read(): array
    {
        $record = DB::table($this->config['table'])
            ->where('id', $this->sessionId)
            ->first();

        return $record ? (array)$record : [];
    }

    /**
     * 写入 session 数据
     */
    protected function write(array $payload, ?int $expiresAt = null): void
    {
        DB::table($this->config['table'])
            ->updateOrInsert(
                ['id' => $this->sessionId],
                [
                    'payload'       => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'last_activity' => time(),
                    'expires_at'    => $expiresAt,  // 保存过期时间
                ]
            );

        setcookie(config('session.cookie'), $this->sessionId, time() + config('session.lifetime') * 60, '/');
    }

    /**
     * DEBUG 模式下检查表是否存在，若不存在则自动创建（跨数据库兼容）
     */
    protected function checkTableExists(): void
    {
        try {
            $tableName = $this->config['table'] ?? 'sessions';
            $conn   = DB::getCapsule()->getConnection();
            $schema = $conn->getSchemaBuilder();

            if (!$schema->hasTable($tableName)) {
                $schema->create($tableName, function ($table) {
                    $table->string('id', 64)->primary();
                    $table->integer('user_id')->nullable();
                    $table->longText('payload');   // 各驱动自动适配
                    $table->integer('last_activity');
                    $table->timestamp('expires_at')->nullable();  // 新增过期时间字段
                });

                if (DEBUG) {
                    throw new RuntimeException("Session 表 [$tableName] 不存在，已自动创建。请刷新页面再次访问。");
                }
            }
        } catch (\Throwable $e) {
            throw new RuntimeException("检查或创建 Session 表失败: " . $e->getMessage(), 0, $e);
        }
    }
}
