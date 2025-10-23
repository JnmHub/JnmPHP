<?php

namespace App\Providers;

use Illuminate\Contracts\Container\Container;
use Kernel\Providers\ServiceProvider;
use Kernel\Session\SessionManager;

class SessionServiceProvider extends ServiceProvider
{
    public function register():void
    {
        // 注册为单例
        $this->container->singleton(SessionManager::class, function () {
            return new SessionManager();
        });

        // 绑定一个别名，方便访问
        $this->container->bind('session', function (Container $app) {
            return $app->make(SessionManager::class);
        });
    }

    public function boot():void
    {
        // Session 服务不需要 boot 阶段的操作
    }
}