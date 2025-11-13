<?php
namespace Kernel\Session\Drivers;

use Kernel\Session\SessionDriverInterface;
use Kernel\Redis\RedisManager;
use Predis\Client as RedisClient;

class RedisSessionDriver implements SessionDriverInterface
{
    protected bool $started = false;
    protected string $sessionId = '';
    protected RedisClient $redis;

    protected string $prefix = 'session:';          // key: session:{sid}
    protected bool $slidingExpireOnRead = true;     // 读操作是否滑动续期

    private const CSRF_KEY = '_token';

    public function __construct()
    {
        $redisManager = app(RedisManager::class);
        $this->redis  = $redisManager->connection('session');

        $cookieName   = config('session.cookie', 'jnm_session');
        $this->sessionId = $_COOKIE[$cookieName] ?? $this->generateSessionId();
    }

    /* -------------------- 基础 -------------------- */

    protected function getKey(?string $sid = null): string
    {
        return $this->prefix . ($sid ?? $this->sessionId);
    }

    protected function generateSessionId(): string
    {
        return bin2hex(random_bytes(20)); // 40 chars
    }

    protected function lifetime(): int
    {
        return (int)config('session.lifetime', 120) * 60;
    }

    public function start(): bool
    {
        // 只负责下发/刷新 Cookie 与设置会话过期
        $this->setSessionCookie($this->sessionId);
        $this->touchExpire(); // 设置/刷新会话级 TTL
        return $this->started = true;
    }

    public function id(): ?string
    {
        return $this->sessionId;
    }

    public function regenerate(bool $deleteOldSession = false): void
    {
        $oldSid = $this->sessionId;
        $newSid = $this->generateSessionId();

        $oldKey = $this->getKey($oldSid);
        $newKey = $this->getKey($newSid);

        // 迁移旧数据（deleteOld=false 时）
        if (!$deleteOldSession && $this->redis->exists($oldKey)) {
            $data = $this->redis->hgetall($oldKey);
            if (!empty($data)) {
                // 批量写入新 key
                $this->redis->hmset($newKey, $data);
            }
        }

        // 删除旧 key（根据标志）
        if ($deleteOldSession && $this->redis->exists($oldKey)) {
            $this->redis->del([$oldKey]);
        }

        $this->sessionId = $newSid;

        // 刷新 Cookie + 会话 TTL
        $this->setSessionCookie($this->sessionId);
        $this->touchExpire();
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    /* -------------------- 数据读写 -------------------- */

    public function all(): array
    {
        $key = $this->getKey();
        $data = $this->redis->hgetall($key);

        if ($this->slidingExpireOnRead) {
            $this->touchExpire();
        }

        if (empty($data)) {
            return [];
        }

        $now = time();
        $out = [];
        $toDel = [];

        foreach ($data as $field => $raw) {
            $decoded = $this->decode($raw);

            // 结构化：带 expires_at
            if (is_array($decoded) && array_key_exists('expires_at', $decoded)) {
                $exp = $decoded['expires_at'];
                if (is_int($exp) && $exp < $now) {
                    $toDel[] = $field;
                    continue;
                }
                $out[$field] = $decoded['value'] ?? null;
            } else {
                // 标量
                $out[$field] = $decoded;
            }
        }

        if (!empty($toDel)) {
            $this->redis->hdel($key, $toDel);
        }

        return $out;
    }

    public function has(string $key): bool
    {
        $k = $this->getKey();
        $raw = $this->redis->hget($k, $key);

        if ($raw === null) {
            return false;
        }

        $val = $this->decode($raw);

        if (is_array($val) && array_key_exists('expires_at', $val)) {
            if (is_int($val['expires_at']) && $val['expires_at'] < time()) {
                // 惰性清理
                $this->redis->hdel($k, [$key]);
                return false;
            }
        }

        if ($this->slidingExpireOnRead) {
            $this->touchExpire();
        }

        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $k = $this->getKey();
        $raw = $this->redis->hget($k, $key);

        if ($raw === null) {
            return $default;
        }

        $val = $this->decode($raw);

        if (is_array($val) && array_key_exists('expires_at', $val)) {
            if (is_int($val['expires_at']) && $val['expires_at'] < time()) {
                $this->redis->hdel($k, [$key]);
                return $default;
            }
            $ret = $val['value'] ?? $default;
        } else {
            $ret = $val;
        }

        if ($this->slidingExpireOnRead) {
            $this->touchExpire();
        }

        return $ret;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $k = $this->getKey();

        if ($ttl !== null) {
            $payload = [
                'value'      => $value,
                'expires_at' => time() + (int)$ttl,
            ];
        } else {
            $payload = $value; // 无 TTL 用标量，节省体积
        }

        $this->redis->hset($k, $key, $this->encode($payload));
        $this->touchExpire(); // 写操作续期会话
    }

    public function forget(string $key): void
    {
        $this->redis->hdel($this->getKey(), [$key]);
        $this->touchExpire();
    }

    public function clear(): void
    {
        $this->redis->del([$this->getKey()]);
        $this->touchExpire(); // 清后仍维持会话键的 TTL（可选）
    }

    public function destroy(): void
    {
        $this->redis->del([$this->getKey()]);
        $this->expireSessionCookie();
        $this->started = false;
    }

    public function save(): void
    {
        // 共享连接，不要 disconnect。这里做“兜底续期”
        $this->touchExpire();
        $this->started = false;
    }

    /* -------------------- Token -------------------- */

    public function token(): string
    {
        if (!$this->has(self::CSRF_KEY)) {
            return $this->regenerateToken();
        }
        return (string)$this->get(self::CSRF_KEY, '');
    }

    public function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->set(self::CSRF_KEY, $token);
        return $token;
    }

    /* -------------------- 过期 API -------------------- */

    public function isExpired(string $key): bool
    {
        $k = $this->getKey();
        $raw = $this->redis->hget($k, $key);
        if ($raw === null) return false; // 没有即视为“未过期”（是否存在由 has 决定）

        $val = $this->decode($raw);
        if (!is_array($val) || !array_key_exists('expires_at', $val)) return false;

        return is_int($val['expires_at']) && $val['expires_at'] < time();
    }

    public function expire(string $key, int $ttl): void
    {
        $k = $this->getKey();
        $raw = $this->redis->hget($k, $key);
        if ($raw === null) return;

        $val = $this->decode($raw);
        $exp = time() + (int)$ttl;

        if (is_array($val) && array_key_exists('expires_at', $val)) {
            $val['expires_at'] = $exp;
            if (!array_key_exists('value', $val)) {
                $val['value'] = null;
            }
        } else {
            // 标量 -> 结构化
            $val = ['value' => $val, 'expires_at' => $exp];
        }

        $this->redis->hset($k, $key, $this->encode($val));
        $this->touchExpire();
    }

    /* -------------------- 内部工具 -------------------- */

    protected function encode(mixed $v): string
    {
        return json_encode($v, JSON_UNESCAPED_UNICODE);
    }

    protected function decode(?string $raw): mixed
    {
        if ($raw === null || $raw === '') return null;
        $val = json_decode($raw, true);
        // 兼容历史 serialize() 数据（可选）
        if (json_last_error() !== JSON_ERROR_NONE) {
            // 尝试 unserialize（如果你历史上确实写过 serialize）
            $tmp = @unserialize($raw);
            return $tmp === false && $raw !== 'b:0;' ? $raw : $tmp;
        }
        return $val;
    }

    protected function touchExpire(): void
    {
        $ttl = $this->lifetime();
        if ($ttl > 0) {
            $this->redis->expire($this->getKey(), $ttl);
        }
    }

    protected function setSessionCookie(string $sid): void
    {
        $cookieName = config('session.cookie', 'jnm_session');
        $lifetime   = $this->lifetime();

        $path     = config('session.path', '/');
        $domain   = config('session.domain', '');
        $secure   = (bool)config('session.secure', false);
        $httpOnly = (bool)config('session.http_only', true);
        $sameSite = config('session.same_site', 'Lax');

        if (strcasecmp($sameSite, 'None') === 0) {
            $secure = true;
        }

        setcookie($cookieName, $sid, [
            'expires'  => time() + $lifetime,
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

        $path     = config('session.path', '/');
        $domain   = config('session.domain', '');
        $secure   = (bool)config('session.secure', false);
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
}
