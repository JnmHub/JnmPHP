<?php
namespace Kernel\Session;

/**
 * Interface SessionDriverInterface
 *
 * 定义所有 Session 驱动必须实现的标准方法。
 * 与当前 SessionManager 保持一致，包含 token/CSRF 方法。
 */
interface SessionDriverInterface
{
    /**
     * 启动 Session
     */
    public function start(): bool;

    /**
     * 获取当前 Session ID
     */
    public function id(): ?string;

    /**
     * 重新生成 Session ID
     */
    public function regenerate(bool $deleteOldSession = false): void;

    /**
     * 获取所有 Session 数据
     */
    public function all(): array;

    /**
     * 判断是否存在指定 key
     */
    public function has(string $key): bool;

    /**
     * 获取 Session 值
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 设置 Session 值
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * 删除指定键
     */
    public function forget(string $key): void;

    /**
     * 清空 Session 数据（但保留 ID）
     */
    public function clear(): void;

    /**
     * 销毁整个 Session（包括 ID）
     */
    public function destroy(): void;

    /**
     * 保存 Session 状态
     */
    public function save(): void;

    /**
     * 获取 CSRF Token，不存在时生成
     */
    public function token(): string;

    /**
     * 重新生成 CSRF Token
     */
    public function regenerateToken(): string;

    /**
     * 检查 Session 是否已启动
     */
    public function isStarted(): bool;

    /**
     * 单独设置过期时间
     *
     * @param string $key
     * @param int $ttl 过期秒数
     */
    public function expire(string $key, int $ttl): void;

    /**
     * 判断指定的 session key 是否过期
     *
     * @param string $key
     * @return bool
     */
    public function isExpired(string $key): bool;
}
