<?php

namespace App\Subscribers\Cors;

use App\Http\Request\Request;
use App\Subscribers\AbstractSubscriber;

class Subscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        $this->events()->on('router.before_dispatch', function() {
            $request = Request::capture();
            header('Access-Control-Allow-Origin: *');

            // 允许的 HTTP 方法
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

            // 允许前端在请求中携带的自定义请求头
            header('Access-Control-Allow-Headers: DNT, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Range, Authorization');

            // 允许浏览器在跨域请求中携带凭证（如 Cookies）
            header('Access-Control-Allow-Credentials: true');

            // 如果是 OPTIONS 预检请求，直接返回成功，不继续执行后续操作
            if ($request->method == 'OPTIONS') {
                http_response_code(204); // 204 No Content
                exit();
            }
        });

    }
}