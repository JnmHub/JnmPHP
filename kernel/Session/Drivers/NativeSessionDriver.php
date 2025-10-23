<?php
namespace Kernel\Session\Drivers;

use Kernel\Session\SessionDriverInterface;

/**
 * Class NativeSessionDriver
 *
 * 使用 PHP 内建 session 的原生驱动实现。
 * 逻辑来源于当前的 SessionManager，补全了 id、regenerate、all、clear、destroy、save 等方法。
 */
class NativeSessionDriver implements SessionDriverInterface
{
    protected bool $started = false;
    protected array $config = [];

    public function __construct(array $config = [])
    {
        // 默认从全局配置加载
        $this->config = $config ?: config('session');
    }

    /**
     * 启动 Session
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return $this->started = true;
        }

        $options = [
            'lifetime' => $this->config['lifetime'] * 60,
            'path'     => $this->config['path'],
            'domain'   => $this->config['domain'],
            'secure'   => $this->config['secure'],
            'httponly' => $this->config['http_only'],
            'samesite' => $this->config['same_site'] ?? 'Lax',
        ];

        session_name($this->config['cookie']);
        session_set_cookie_params($options);

        if (session_start()) {
            return $this->started = true;
        }

        return false;
    }

    /**
     * 获取当前 Session ID
     */
    public function id(): ?string
    {
        $this->start();
        return session_id() ?: null;
    }

    /**
     * 重新生成 Session ID
     */
    public function regenerate(bool $deleteOldSession = false): void
    {
        $this->start();
        session_regenerate_id($deleteOldSession);
    }

    /**
     * 获取所有 Session 数据
     */
    public function all(): array
    {
        $this->start();
        return $_SESSION ?? [];
    }

    /**
     * 判断是否存在 key
     */
    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * 获取 Session 值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        if (isset($_SESSION[$key]['expires_at']) && $_SESSION[$key]['expires_at'] < time()) {
            unset($_SESSION[$key]);
            return $default;
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * 设置 Session 值，并支持过期时间
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->start();
        $_SESSION[$key] = [
            'value' => $value,
            'expires_at' => $ttl ? time() + $ttl : null  // 设置过期时间
        ];
    }

    /**
     * 删除指定键
     */
    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * 清空所有数据但保留 ID
     */
    public function clear(): void
    {
        $this->start();
        $_SESSION = [];
    }

    /**
     * 销毁整个 Session（包含 cookie）
     */
    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            $this->started = false;
        }
    }

    /**
     * 保存 Session
     */
    public function save(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            $this->started = false;
        }
    }

    /**
     * 获取 CSRF Token
     */
    public function token(): string
    {
        $this->start();
        if (!$this->has('_token')) {
            $this->regenerateToken();
        }
        return $this->get('_token');
    }

    /**
     * 重新生成 CSRF Token
     */
    public function regenerateToken(): string
    {
        $this->start();
        $token = bin2hex(random_bytes(32));
        $this->set('_token', $token);
        return $token;
    }

    /**
     * 检查是否已启动
     */
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
        $this->start();

        if (isset($_SESSION[$key]['expires_at']) && $_SESSION[$key]['expires_at'] < time()) {
            return true;
        }

        return false;
    }

    /**
     * 设置 session key 的过期时间
     *
     * @param string $key
     * @param int $ttl 过期秒数
     */
    public function expire(string $key, int $ttl): void
    {
        $this->start();

        if (isset($_SESSION[$key])) {
            $_SESSION[$key]['expires_at'] = time() + $ttl;
        }
    }
}
