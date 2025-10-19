<?php

namespace App\Providers;

use Kernel\Providers\ServiceProvider;
use Kernel\Routing\RouteCollector;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 路由是核心配置，在 register 阶段加载并绑定到容器
        $this->container->singleton('routes', function () {
            return RouteCollector::run();
        });
    }

    public function boot(): void
    {
        // 路由在 register 阶段已加载，这里无需操作
    }
}