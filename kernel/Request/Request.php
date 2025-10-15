<?php
// 文件路径: app/Core/Http/Request.php

namespace Kernel\Request;


class Request
{
    // 使用 ?? 提供默认值，增加健壮性
    public readonly string $uri;
    public readonly string $method;
    public readonly array $headers;
    public readonly array $get;
    public readonly array $post;

    // 将 json 改为私有属性，用于惰性加载
    private ?array $json = null;

    // 移除单例的静态属性
    // public static ?self $instance = null;

    private function __construct()
    {
        // 1. 为 $_SERVER 访问增加保护
        $this->uri = $_SERVER['REQUEST_URI'] ?? '';
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->headers = $this->getAllHeaders();
        $this->get = $_GET;
        $this->post = $_POST;
    }

    /**
     * 创建一个包含当前HTTP请求信息的实例
     * @return static
     */
    public static function capture(): static
    {
        // 3. 即使保留静态创建方法，也不建议使用单例
        // 每次调用都返回一个新实例，或者让调用者自己 new
        return new self();
    }

    /**
     * 获取 Body 中的 JSON 数据 (惰性加载)
     */
    public function json(): array
    {
        if ($this->json === null) {
            $this->json = $this->parseJsonBody();
        }
        return $this->json;
    }

    /**
     * 实际执行 JSON 解析的私有方法
     */
    private function parseJsonBody(): array
    {
        // 检查 Content-Type 是否为 JSON，避免对非 JSON 请求进行解析
        $contentType = $this->header('Content-Type');
        if ($contentType && str_contains(strtolower($contentType), 'application/json')) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return is_array($data) ? $data : [];
                }
                throw new \RuntimeException('JSON格式错误: ' . json_last_error_msg());
            }
        }
        return [];
    }

    /**
     * 获取所有请求头 (使用内置函数作为首选)
     */
    private function getAllHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            // getallheaders() 在没有请求头时可能返回 false
            if ($headers !== false) {
                return array_change_key_case($headers);
            }
        }

        // 如果内置函数不可用，则使用手动遍历作为后备
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($name, 5)));
                $headers[$headerName] = $value;
            }
        }
        return array_change_key_case($headers);
    }

    /**
     * 获取单个请求头
     */
    public function header(string $key, $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    /**
     * 获取JSON、POST、GET中的数据 (优先级: JSON > POST > GET)
     */
    public function input(string $key, mixed $default = null): mixed
    {
        // 4. 在需要时才调用 json() 方法进行解析
        return $this->json()[$key] ?? $this->post[$key] ?? $this->get[$key] ?? $default;
    }
}