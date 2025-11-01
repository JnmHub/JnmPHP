<?php

namespace Kernel\Redis;

use Predis\Client;

/**
 * Class Redis (Facade)
 *
 * 提供了对 RedisManager 的静态访问代理。
 * @mixin RedisManager
 * @mixin Client
 */
class Redis
{
    /**
     * @var ?RedisManager 静态持有的 Manager 实例
     */
    protected static ?RedisManager $manager = null;

    /**
     * 由 RedisServiceProvider 在 boot 时注入
     */
    public static function setManager(RedisManager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * 获取指定的连接
     */
    public static function connection(string $name = null): Client
    {
        return self::$manager->connection($name);
    }

    /**
     * 静态代理所有调用到 'default' 连接
     */
    public static function __callStatic($method, $arguments)
    {
        // 自动使用 'default' 连接
        return self::$manager->connection('default')->$method(...$arguments);
    }
}