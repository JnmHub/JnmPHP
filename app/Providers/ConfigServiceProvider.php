<?php

namespace App\Providers;

use Kernel\Config\ConfigRepository;
use Kernel\Providers\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function register():void
    {
        // 将 ConfigRepository 注册为单例
        // 我们在构造函数中传入 config 目录的路径
        $this->container->singleton(ConfigRepository::class, function () {
            return new ConfigRepository(base_path('config'));
        });

        // (可选) 绑定一个别名, 方便在容器中通过 'config' 字符串访问
        $this->container->bind('config', function ($container) {
            return $container->make(ConfigRepository::class);
        });
    }

    public function boot():void
    {
        // Config 服务不需要 boot 阶段的操作
    }
}