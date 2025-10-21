<?php

namespace Kernel;

use Illuminate\Container\Container;
use Kernel\Container\KernelContainer;
use Kernel\Request\Request;
use Kernel\Routing\Router;

class Application
{
    private static ?self $instance = null;
    /**
     * 服务容器实例
     */
    protected Container $container;

    /**
     * 已注册的服务提供者
     */
    protected array $providers = [];
    public static function getInstance(): self
    {
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct()
    {
        // 1. 初始化容器
        $this->container = KernelContainer::getInstance();

        // 2. 将应用实例自身绑定到容器中，方便其他服务使用
        $this->container->instance(Application::class, $this);
    }

    /**
     * 注册所有的服务提供者
     */
    public function registerProviders(): void
    {
        // 1. 从配置文件加载提供者列表
        $providers = require APP_ROOT . '/config/providers.php';

        foreach ($providers as $providerClass) {
            // 2. 实例化提供者，并注入容器
            $providerInstance = new $providerClass($this->container);

            // 3. 调用 register 方法
            $providerInstance->register();

            // 4. 保存实例，以便后续调用 boot
            $this->providers[] = $providerInstance;
        }
    }

    /**
     * 启动所有已注册的服务提供者
     */
    public function bootProviders(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }

    /**
     * 处理 HTTP 请求
     */
    public function handle(Request $request): void
    {
        // 从容器中获取已注册的路由表
        $routes = $this->container->make('routes');

        // 实例化路由并分发
        $router = new Router($routes);

        //  dispatch 包含处理中间件、控制器和响应的逻辑
        $router->dispatch(
            $request->uri,
            $request->method,
            $request
        );
    }
    public function getContainer(): Container
    {
        return $this->container;
    }
}