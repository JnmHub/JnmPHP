<?php

namespace App\Middleware;

use Closure;
use Kernel\Exception\HttpException;
use Kernel\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next)
    {
        $expectedToken = 'Bearer my-secret-token';

        if (!isset($_SERVER['HTTP_AUTHORIZATION']) || $_SERVER['HTTP_AUTHORIZATION'] !== $expectedToken) {
            // 验证失败, 抛出异常, 中断请求
            throw new HttpException(401, 'Unauthorized');
        }

        // 验证通过, 继续处理请求
        return $next($request);
    }
}