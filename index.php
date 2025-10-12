<?php
declare(strict_types=1);

use App\Core\Database\DB;
use App\Core\Events\EventManager;
use App\Core\Events\SubscriberCollector;
use App\Core\Routing\RouteCollector;
use App\Exception\handler\ExceptionHandler;

include __DIR__ . "/vendor/autoload.php";
const APP_ROOT = __DIR__;
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
ExceptionHandler::register();
// 从缓存加载路由表
$routes = RouteCollector::run();
// 初始化数据库，并连接
DB::init();

// 格式化JSON和初始化请求参数
\App\Core\Http\Request::formData();
// 路由转发
$router = new \App\Core\Routing\Router($routes);
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
$eventManager->dispatch('app.shutdown');