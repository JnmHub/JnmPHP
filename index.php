<?php
declare(strict_types=1);

use App\Core\DB;
use App\Core\RouteCollector;
use App\Core\ExceptionHandler;

include __DIR__ . "/vendor/autoload.php";
const APP_ROOT = __DIR__;
const DEBUG = true;
ExceptionHandler::register();
// 从缓存加载路由表
$routes = RouteCollector::run();
// 初始化数据库，并连接
DB::init();

// 格式化JSON和初始化请求参数
\App\Core\Request::formData();
// 路由转发
$router = new \App\Core\Router($routes);
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
