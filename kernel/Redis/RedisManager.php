<?php

namespace Kernel\Redis;

use Predis\Client;
use RuntimeException;

/**
 * Class RedisManager
 *
 * 负责创建、管理和缓存 (单例) Predis 客户端连接。
 */
class RedisManager
{
    /**
     * @var array Redis 配置
     */
    protected array $config;

    /**
     * @var Client[] 已缓存的连接实例
     */
    protected array $connections = [];

    /**
     * @param array $config 通常是 config('database.redis')
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 获取一个 Redis 连接实例 (单例)
     *
     * @param string|null $name 连接名称 (例如 'default', 'session')
     * @return Client
     */
    public function connection(string $name = null): Client
    {
        // 验证Redis扩展是否可用
        RedisValidator::validateRedisExtension('RedisManager');
        $name = $name ?: 'default';

        // 1. 如果已缓存，直接返回
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // 2. 获取该连接的配置
        if (!isset($this->config[$name])) {
            throw new RuntimeException("Redis 连接配置 [$name] 未在 config/database.php 中定义。");
        }
        $connectionConfig = $this->config[$name];

        // 3. 创建新连接
        try {
            $client = new Client([
                'scheme'   => 'tcp',
                'host'     => $connectionConfig['host'],
                'port'     => $connectionConfig['port'],
                'password' => $connectionConfig['password'] ?: null,
                'database' => $connectionConfig['database'],
                'timeout'  => $connectionConfig['timeout'] ?? 2.0,
            ]);
            $client->connect();

            // 4. 缓存并返回
            return $this->connections[$name] = $client;

        } catch (\Throwable $e) {
            throw new RuntimeException("无法连接到 Redis [$name]: " . $e->getMessage());
        }
    }

    /**
     * 动态代理方法到 'default' 连接
     */
    public function __call($method, $arguments)
    {
        return $this->connection('default')->$method(...$arguments);
    }
}