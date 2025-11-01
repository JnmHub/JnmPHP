<?php
namespace Kernel\Session\Drivers;

use Kernel\Session\SessionDriverInterface;
use Kernel\Redis\RedisManager; // ✅ 1. 引入 RedisManager
use Predis\Client as RedisClient;

class RedisSessionDriver implements SessionDriverInterface
{
    protected bool $started = false;
    protected string $sessionId;
    protected RedisClient $redis; // ✅ 2. 仍然是 Predis\Client

    /**
     * Session 键的前缀
     */
    protected string $prefix = 'session:';

    public function __construct()
    {
        // ✅ 3. 从容器中获取共享的 RedisManager
        $redisManager = app(RedisManager::class);

        // ✅ 4. 从 Manager 获取 'session' 连接
        $this->redis = $redisManager->connection('session');

        // 5. 获取 Session ID (逻辑不变)
        $cookieName = config('session.cookie', 'jnm_session');
        $this->sessionId = $_COOKIE[$cookieName] ?? $this->generateSessionId();
    }

    // ... (id(), getKey(), generateSessionId() ... 等方法) ...
    // ... (all(), has(), get(), set(), forget(), clear(), destroy() ... 等方法) ...

    /**
     * 保存 Session（核心）
     * 职责：设置整个 Session Hash 的过期时间。
     */
    public function save(): void
    {
        $lifetimeInSeconds = config('session.lifetime') * 60;

        if ($lifetimeInSeconds > 0) {
            $this->redis->expire($this->getKey(), $lifetimeInSeconds);
        }

        // ✅ 6. 关键：【移除】 $this->redis->disconnect();
        // 这是一个共享连接，我们不能关闭它！

        $this->started = false;
    }

    // ... (token(), regenerateToken(), isStarted(), isExpired(), expire() ... 等方法) ...

    /* ================================================================== */
    /* =================== (请复制粘贴以下完整方法) ======================= */
    /* ================================================================== */

    protected function getKey(): string
    {
        return $this->prefix . $this->sessionId;
    }

    protected function generateSessionId(): string
    {
        return bin2hex(random_bytes(20)); // 40位长度
    }

    public function start(): bool
    {
        setcookie(
            config('session.cookie'),
            $this->sessionId,
            time() + config('session.lifetime') * 60,
            config('session.path', '/'),
            config('session.domain', ''),
            config('session.secure', false),
            config('session.http_only', true)
        );
        return $this->started = true;
    }

    public function id(): ?string
    {
        return $this->sessionId;
    }

    public function regenerate(bool $deleteOldSession = false): void
    {
        if ($deleteOldSession) {
            $this->redis->del([$this->getKey()]);
        }
        $this->sessionId = $this->generateSessionId();
        $this->start();
    }

    public function all(): array
    {
        $data = $this->redis->hgetall($this->getKey());
        $unserializedData = [];
        foreach ($data as $key => $value) {
            $unserializedData[$key] = unserialize($value);
        }
        return $unserializedData;
    }

    public function has(string $key): bool
    {
        return (bool) $this->redis->hexists($this->getKey(), $key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->hget($this->getKey(), $key);
        return $value !== null ? unserialize($value) : $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->redis->hset($this->getKey(), $key, serialize($value));
    }

    public function forget(string $key): void
    {
        $this->redis->hdel($this->getKey(), [$key]);
    }

    public function clear(): void
    {
        $this->redis->del([$this->getKey()]);
    }

    public function destroy(): void
    {
        $this->clear();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                config('session.cookie'), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        $this->started = false;
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

    public function isExpired(string $key): bool
    {
        return !$this->has($key);
    }

    public function expire(string $key, int $ttl): void
    {
        // 不支持单独为 key 设置 TTL
    }
}