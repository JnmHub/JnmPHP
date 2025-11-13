<?php

namespace App\Middleware;

use Closure;
use Kernel\Middleware\MiddlewareInterface;
use Kernel\Session\SessionManager;

class StartSessionMiddleware implements MiddlewareInterface
{
    protected SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function handle(mixed $request, Closure $next)
    {
        // 启动 Session
        $this->session->start();

        // 继续处理请求
        $r =  $next($request);

        $this->session->save();

        return $r;
    }
}