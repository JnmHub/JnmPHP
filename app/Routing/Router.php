<?php
namespace App\Routing;

use App\Attribute\PathVariable;
use App\Events\EventManager;
use App\Exception\HttpException;
use App\Helpers\Str;
use App\Http\MiddlewareManager;
use App\Http\Pipeline;
use App\Http\Request\Request;
use App\Http\Response\JsonResponse;
use App\Http\Response\ResponseInterface;
use Kernel\Container\Container;
use ReflectionMethod;

class Router
{
    private array $routes;
    private MiddlewareManager $kernel;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
        $this->kernel = new MiddlewareManager(); // ✅ 实例化 Kernel
    }

    /**
     * @throws \ReflectionException
     * @throws HttpException
     */
    public function dispatch(string $uri, string $method, $request)
    {
        // 清理 URI 中多余的 ?参数 和 尾部 /
        EventManager::getInstance()->dispatch('router.before_dispatch', $uri, $method);
        $uri = preg_replace('~/+~', '/', $uri);
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            // 匹配 HTTP 方法
            if (!in_array(strtoupper($method), $route['methods'])) {
                continue;
            }
            $pattern = $route['preg_path'];


            if (preg_match($pattern, $uri, $matches)) {
                // 提取命名捕获组
                $params = array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);  // 获取所有的请求的参数
                $params = array_map(['App\Helpers\Str', 'urldecode'], $params);
                $container = Container::getInstance();
                $controller = $container->make($route['controller']);
                $action = $route['action'];

                $ref = new ReflectionMethod($controller, $action);

                $args = [];

                foreach ($ref->getParameters() as $param) {
                    $paramType = $param->getType();
                    $paramName = $param->getName();
                    if ($paramType && !$paramType->isBuiltin() && $paramType->getName() === Request::class) {
                        $args[$paramName] = Request::capture();
                        continue; // 继续处理下一个参数
                    }
                    $attrs = $param->getAttributes(PathVariable::class); // 获取是否有注解
                    $name = $paramName; // 获取参数的名，不是注解的名
                    $missingMsg = "缺少参数：{$name}"; // 默认缺少提示

                    if ($attrs) {  // 如果有注解
                        $anno = $attrs[0]->newInstance();  // 获取这个注解的实例
                        $name = $anno->name;  // 注解自定义的名赋予name
                        if ($anno->missingParamMessage) {  // 看一下是否有默认的错误提示信息，如果有则覆盖之前的
                            $missingMsg = $anno->missingParamMessage;
                        }
                    }

                    $hasKey = array_key_exists($name, $params);  // 看看是否有这个key
                    $value = $hasKey ? $params[$name] : null;  // 设置值

                    // ✅ 空字符串也算“缺少参数”
                    $isMissing = !$hasKey || $value === '';

                    if ($isMissing) {
                        if ($param->isDefaultValueAvailable()) {
                            // 有默认值则使用默认值
                            $args[$paramName] = $param->getDefaultValue();
                        } else {
                            throw new HttpException(400, $missingMsg);
                        }
                    } else {
                        $valueToInject = $value;
                        if ($paramType && $paramType->isBuiltin()) {
                            switch ($paramType->getName()) {
                                case 'int':
                                    $valueToInject = (int)$valueToInject;
                                    break;
                                case 'float':
                                    $valueToInject = (float)$valueToInject;
                                    break;
                                case 'bool':
                                    $valueToInject = filter_var($valueToInject, FILTER_VALIDATE_BOOLEAN);
                                    break;
                            }
                        }
                        $args[$paramName] = $valueToInject;
                    }
                }

                EventManager::getInstance()->dispatch('controller.before_execute', $controller, $action, $args);

                // ✅ 1. 定义管道的最终目的地：执行控制器
                $controllerExecution = function () use ($container, $controller, $action, $args) {
                    // 现在，$methodParams 的键名已经和方法参数名完全对应了
                    return $container->call([$controller, $action], $args);
                };

                // ✅ 2. 组合所有需要执行的中间件
                $middlewares = array_merge(
                    $this->kernel->getGlobalMiddleware(), // 全局中间件
                    $route['middlewares'] // 注解中定义的路由中间件
                );

                $response = Pipeline::init()->send($request) // 实际项目中应传入 Request 对象
                    ->through(array_unique($middlewares))
                    ->then($controllerExecution);

                // ✅ 核心改动：智能响应处理器
                if ($response instanceof ResponseInterface) {
                    $response->send();
                } else {
                    JsonResponse::success($response)->send();
                }
                EventManager::getInstance()->dispatch('controller.after_execute', $response);

                return; // 请求处理完毕，终止执行
            }
        }
        // 未匹配到路由
        throw new HttpException(404, "404 Not Found");
    }
}
