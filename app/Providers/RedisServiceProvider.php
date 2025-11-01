<?php

namespace App\Providers;

use Kernel\Config\ConfigRepository;
use Kernel\Providers\ServiceProvider;
use Kernel\Redis\RedisManager;
use Kernel\Redis\Redis; // 引入我们下一步要创建的 Facade

class RedisServiceProvider extends ServiceProvider
{
    /**
     * 注册 RedisManager 作为单例
     */
    public function register(): void
    {
        $this->container->singleton(RedisManager::class, function ($container) {
            // 从配置仓库获取 redis 配置
            $config = config('database.redis');
            if (empty($config)) {
                throw new \RuntimeException("Redis 配置 (config/database.php) 未找到。");
            }

            return new RedisManager($config);
        });

        // 添加别名，方便通过 app('redis') 访问
        $this->container->alias(RedisManager::class, 'redis');
    }

    /**
     * 启动服务，将 Manager 注入 Facade
     */
    public function boot(): void
    {
        // 将容器中的单例 Manager 注入到 Redis Facade 中
        Redis::setManager($this->container->make(RedisManager::class));
    }
}