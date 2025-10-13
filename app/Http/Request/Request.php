<?php
// 文件路径: app/Core/Request.php

namespace App\Http\Request;

class Request
{
    public readonly string $uri;
    public readonly string $method;
    public readonly array $headers;
    public readonly array $get;
    public readonly array $post;
    public readonly array $json;

    private function __construct()
    {
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->headers = $this->getAllHeaders();

        $this->get = $_GET;
        $this->post = $_POST;
        $this->json = $this->getJsonBody();
    }

    /**
     * 创建一个包含当前HTTP请求信息的实例
     * @return static
     */
    public static function capture(): static
    {
        return new static();
    }

    /**
     * 获取 Body 中的 JSON 数据
     */
    private function getJsonBody(): array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return [];
        }

        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // 在实际应用中，这里应该抛出异常而不是直接退出
            throw new \RuntimeException('JSON格式错误: ' . json_last_error_msg());
        }

        return is_array($data) ? $data : [];
    }

    /**
     * 获取所有请求头
     */
    private function getAllHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($name, 5)));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    /**
     * 获取单个请求头
     */
    public function header(string $key, $default = null): ?string
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    /**
     * 获取JSON、POST、GET中的数据 (优先级: JSON > POST > GET)
     */
    public function input(string $key, $default = null)
    {
        return $this->json[$key] ?? $this->post[$key] ?? $this->get[$key] ?? $default;
    }
}