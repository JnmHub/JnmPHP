<?php

namespace Kernel\Session;

use Kernel\Container\KernelContainer;

class SessionManager
{
    protected bool $started = false;
    protected array $config = [];

    public function __construct()
    {
        $this->config = config('session');
    }

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
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['http_only'],
            'samesite' => $this->config['same_site']
        ];

        session_name($this->config['cookie']);
        session_set_cookie_params($options);

        if (session_start()) {
            return $this->started = true;
        }

        return false;
    }

    /**
     * 获取 Session 中的值
     */
    public function get(string $key, $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * 设置 Session 值
     */
    public function set(string $key, $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * 检查是否存在
     */
    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * 删除 Session 值
     */
    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * 获取并存储 CSRF Token
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
        $token = bin2hex(random_bytes(32));
        $this->set('_token', $token);
        return $token;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }
}