<?php

namespace App\Http;

use Closure;

/**
 * 中间件接口
 */
interface MiddlewareInterface
{
    /**
     * 处理传入的请求.
     *
     * @param mixed $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(mixed $request, Closure $next);
}