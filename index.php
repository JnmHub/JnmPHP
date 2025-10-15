<?php
declare(strict_types=1);

use Kernel\Container\Container;
use Kernel\Database\DB;
use Kernel\Events\EventManager;
use Kernel\Exception\Handler;
use Kernel\Routing\RouteCollector;
use Kernel\Subscribers\SubscriberCollector;

include __DIR__ . "/vendor/autoload.php";
const APP_ROOT = __DIR__;
date_default_timezone_set('Asia/Shanghai');
const DEBUG = true;
$eventManager = EventManager::getInstance();
$subscriberClasses = SubscriberCollector::run();

// 2. 遍历类名，实例化并执行其生命周期方法
foreach ($subscriberClasses as $class) {
    $subscriber = new $class($eventManager); // 构造函数注入 EventManager

    $subscriber->beforeSubscribe(); // 调用前置钩子
    $subscriber->subscribe();      // 执行核心事件注册
    $subscriber->afterSubscribe(); // 调用后置钩子
}
// 钩子 : 应用初始化后
$eventManager->dispatch('app.boot');
Container::init();
Handler::register();
// 从缓存加载路由表
$routes = RouteCollector::run();
// 初始化数据库，并连接
DB::init();

// 格式化JSON和初始化请求参数
$request = \Kernel\Request\Request::capture();
// 路由转发
$router = new \Kernel\Routing\Router($routes);
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'],$request);
// 钩子 : 应用结束前
$eventManager->dispatch('app.shutdown');