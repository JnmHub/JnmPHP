<?php

namespace App\Providers;

use Kernel\Events\EventManager;
use Kernel\Exception\Handler;
use Kernel\Providers\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 将事件管理器作为单例绑定到容器
        $this->container->singleton(EventManager::class, function () {
            return EventManager::getInstance();
        });

        // 绑定异常处理器
        $this->container->singleton(Handler::class, function () {
            return new Handler();
        });
    }

    public function boot(): void
    {
        // 从容器中解析出处理器并注册
        $this->container->make(Handler::class)::register();
    }
}