<?php
// 文件路径: app/Core/Attribute/Middleware.php

namespace Kernel\Attribute;

use Attribute;

/**
 * 中间件注解
 *
 * 可以应用在类或方法上, 并且可以重复使用
 * #[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    public array $middlewares;

    /**
     * 构造函数允许传入一个或多个中间件类名或别名
     *
     * 示例:
     * #[Middleware(LogMiddleware::class)]
     * #[Middleware('auth')]
     * #[Middleware(AuthMiddleware::class, 'admin')]
     *
     * @param string ...$middlewares
     */
    public function __construct(string ...$middlewares)
    {
        $this->middlewares = $middlewares;
    }
}