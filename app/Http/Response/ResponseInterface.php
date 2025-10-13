<?php

namespace App\Http\Response;

interface ResponseInterface
{
    /**
     * 将响应发送到客户端
     */
    public function send(): void;
}