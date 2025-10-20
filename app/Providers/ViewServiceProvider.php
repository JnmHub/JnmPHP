<?php

namespace App\Providers;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use kernel\Providers\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 视图文件所在的目录
        $viewPaths = [APP_ROOT . '/app/View'];
        // 编译缓存存放的目录
        $cachePath = APP_ROOT . '/cache/views';

        // 依赖项
        $filesystem = new Filesystem();
        $eventDispatcher = $this->container->get(Dispatcher::class); // 从你的容器中获取已注册的事件调度器

        // 1. 创建视图查找器
        $viewFinder = new FileViewFinder($filesystem, $viewPaths);

        // 2. 创建 Blade 编译器
        $bladeCompiler = new BladeCompiler($filesystem, $cachePath);

        // 3. 创建引擎解析器
        $engineResolver = new EngineResolver();

        // 4. 为 Blade 注册编译器引擎
        $engineResolver->register('blade', function () use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });

        // 5. 创建 Blade 视图工厂 (核心服务)
        $viewFactory = new Factory($engineResolver, $viewFinder, $eventDispatcher);

        // 6. 将视图工厂注册为单例
        $this->container->singleton(Factory::class, fn() => $viewFactory);

        // 别名，方便在 ViewResponse 中获取
        $this->container->singleton('view', fn() => $viewFactory);
    }
}