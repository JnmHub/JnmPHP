<?php

namespace Kernel\Response;

use Illuminate\View\Factory as ViewFactory; // 引入 Blade 视图工厂
use kernel\Application; // 引入你的应用实例

class ViewResponse implements ResponseInterface
{
    protected $template;
    protected $data;

    public function __construct($template, $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * 使用 Blade 引擎渲染视图
     * @return string
     * @throws \Exception
     */
    public function render()
    {
        /** @var ViewFactory $viewFactory */
        // 从容器中获取我们注册的 'view' 服务
        $viewFactory = Application::getInstance()->getContainer()->get('view');

        // Blade 使用 . 来分隔目录，所以我们把 'index/index' 转换为 'index.index'
        $templateName = str_replace('/', '.', $this->template);

        // 使用 Blade 的 make 方法来渲染模板
        return $viewFactory->make($templateName, $this->data)->render();
    }

    public function send(): void
    {
        echo $this->render();
    }
}