<?php

namespace Kernel\Providers;

use Illuminate\Container\Container;

abstract class ServiceProvider
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * 注册服务到容器
     * (此时不应做任何实际操作，只做绑定)
     */
    abstract public function register(): void;

    /**
     * 启动服务
     * (此时所有服务都已注册，可以安全地使用)
     */
    public function boot(): void
    {
        // 默认无操作，子类可以重写
    }
}