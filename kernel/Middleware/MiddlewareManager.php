<?php

namespace Kernel\Middleware;

use App\Middleware\LogRequestMiddleware;
use App\Middleware\StartSessionMiddleware;
use App\Middleware\VerifyCsrfTokenMiddleware;

class MiddlewareManager
{
    /**
     * 全局中间件
     * 这些中间件会在每一次请求中按顺序执行
     * @var array
     */
    protected array $globalMiddleware = [
        LogRequestMiddleware::class,
        StartSessionMiddleware::class,
    ];

    /**
     * 路由中间件别名
     * 方便在注解中使用简短的名称代替完整的类名
     * @var array
     */
    protected array $routeMiddlewareAliases = [
        'auth' => \App\Middleware\AuthMiddleware::class,
        'log' => LogRequestMiddleware::class,
        'admin' => \App\Middleware\AdminCheckMiddleware::class,
        'CSRF' => VerifyCsrfTokenMiddleware::class,

    ];

    // Getter for global middleware
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    // Getter for aliases
    public function getRouteMiddlewareAliases(): array
    {
        return $this->routeMiddlewareAliases;
    }
}