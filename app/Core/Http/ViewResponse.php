<?php

namespace App\Core\Http;

class ViewResponse implements ResponseInterface
{
    protected string $view;
    protected array $data;

    public function __construct(string $view, array $data = [])
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function send(): void
    {
        $viewFile = APP_ROOT . '/app/View/' . $this->view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("视图文件未找到: {$viewFile}");
        }

        // 设置正确的响应头为 HTML
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        // 将数组中的键值对转换为变量，以便在视图文件中直接使用
        extract($this->data);

        // 引入并执行PHP视图文件，渲染HTML
        require $viewFile;
    }
}