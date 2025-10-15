<?php

namespace Kernel\Exception;

class HttpException extends BaseException
{
    public int $statusCode;
    public array $headers;

    /**
     * @param int $statusCode HTTP状态码
     * @param string $message 错误信息
     * @param array $headers 需要发送的HTTP头
     * @param \Throwable|null $previous
     */
    public function __construct(int $statusCode, string $message = "", array $headers = [], \Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        parent::__construct($message, 0, $previous);
    }
}