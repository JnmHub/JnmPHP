<?php

namespace App\Http\Middleware;

use App\Exception\HttpException;
use App\Http\MiddlewareInterface;
use Closure;

class AdminCheckMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next)
    {
        // 这是一个简单的示例，实际项目中应从Session或Token中获取用户信息来判断
        $isAdmin = isset($_GET['role']) && $_GET['role'] === 'admin';

        if (!$isAdmin) {
            throw new HttpException(403, 'Forbidden: You must be an administrator.');
        }

        return $next($request);
    }
}