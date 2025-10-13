<?php

namespace App\Http\Response;

class JsonResponse implements ResponseInterface
{
    /**
     * @var int 状态码
     */
    public int $code;

    /**
     * @var string 提示信息
     */
    public string $message;

    /**
     * @var mixed|null 响应数据
     */
    public mixed $data;

    public function __construct(int $code = 200, string $message = 'success', mixed $data = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * 输出JSON响应
     */
    public function send(): void
    {
        // 这里的逻辑对于JSON响应是完全正确的
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($this, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); // 加上 PRETTY_PRINT 更易读
    }

    /**
     * 静态方法快速成功响应
     * @param mixed|null $data
     * @return static
     */
    public static function success(mixed $data = null): static
    {
        return new static(200, 'success', $data);
    }

    /**
     * 静态方法快速失败响应
     * @param string $message
     * @param int $code
     * @return static
     */
    public static function error(string $message, int $code = 400): static
    {
        return new static($code, $message, null);
    }
}