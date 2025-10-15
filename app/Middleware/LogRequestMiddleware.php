<?php

namespace App\Middleware;

use Closure;
use Kernel\Middleware\MiddlewareInterface;

class LogRequestMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next)
    {
        $logPath = APP_ROOT . '/logs/requests.log';
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0755, true);
        }

        $logMessage = sprintf(
            "[%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI']
        );

        file_put_contents($logPath, $logMessage, FILE_APPEND);

        // 继续处理请求
        return $next($request);
    }
}