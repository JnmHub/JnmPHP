<?php
declare(strict_types=1);

use App\Core\RouteCollector;
use Illuminate\Database\Capsule\Manager as Capsule;

include __DIR__ . "/vendor/autoload.php";
const APP_ROOT = __DIR__;
const DEBUG = true;
/**
 * @todo 每次执行，删除之前，创建新的
 */
$routeCacheFile = APP_ROOT . '/cache/routes.php';
if (DEBUG) { // 或者加上一个开发模式的判断
    // 这里你需要一个方法来获取所有控制器文件的路径
    $controllerFiles = glob(APP_ROOT . '/app/Controller/*.php');

    $collector = new RouteCollector();
    $routes = $collector->collect($controllerFiles);

    // 将路由表写入缓存文件
    file_put_contents($routeCacheFile, '<?php return ' . var_export($routes, true) . ';');
}

// 从缓存加载路由表
$routes = require $routeCacheFile;


$capsule = new Capsule;
$dbConfig = require APP_ROOT . '/config/database.php';
$capsule->addConnection($dbConfig);
$capsule->setAsGlobal(); // 设置为全局可用
$capsule->bootEloquent(); // 启动Eloquent ORM


$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? "GET");
$requestUri = $_GET['s'] ?? '/';
$jsonData = file_get_contents('php://input');

// 2. 解析 JSON 字符串（关键步骤）
// 第二个参数 true：解析为关联数组；false（默认）：解析为对象
$requestData = empty($jsonData) ? [] : json_decode($jsonData, true);
define("JSON", $requestData);
// 3. 验证解析结果（避免 JSON 格式错误导致的问题）
if (json_last_error() !== JSON_ERROR_NONE) {
    // 解析失败，返回错误信息（HTTP 400 表示“请求参数错误”）
    http_response_code(400);
    echo json_encode([
        'code' => 400,
        'message' => 'Invalid JSON format: ' . json_last_error_msg()
    ]);
    exit;
}
$matchedRoute = null;
foreach ($routes as $route) {
    // 匹配请求方法和URI
    if (in_array($requestMethod, $route['methods']) && $route['path'] === $requestUri) {
        $matchedRoute = $route;
        break;
    }
}

if ($matchedRoute) {
    $controllerClass = $matchedRoute['controller'];
    $actionName = $matchedRoute['action'];

    $controller = new $controllerClass();
    // 1. 获取方法反射对象
    try {
        $reflectionMethod = new ReflectionMethod($controller, $actionName);
    } catch (ReflectionException $e) {
        http_response_code(505);
        echo "505 方法执行错误";
        exit;
    }

    // 2. 获取方法的所有参数
    $params = $reflectionMethod->getParameters();

    // 3. 准备要传递给方法的参数数组
    $args = [];

    // 合并所有请求参数
    $requestParams = array_merge($_GET, $_POST);

    foreach ($params as $param) {
        $paramName = $param->getName();

        // 如果请求中有同名参数，就用它的值
        if (isset($requestParams[$paramName])) {
            $args[] = $requestParams[$paramName];
        } // 否则，检查参数是否有默认值
        elseif ($param->isDefaultValueAvailable()) {
            $args[] = $param->getDefaultValue();
        } // 如果没有默认值，可以给个 null 或者抛出异常
        else {
            $args[] = null;
        }
    }

    // 4. 使用 call_user_func_array 来调用方法并传入参数
    call_user_func_array([$controller, $actionName], $args);
} else {
    // 处理 404 Not Found
    http_response_code(404);
    echo '404 Not Found';
}