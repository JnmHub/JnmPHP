<?php
namespace App\Core\Routing;

use App\Core\Attribute\PathVariable;
use App\Core\Events\EventManager;
use App\Core\Http\JsonResponse;
use App\Core\Http\Pipeline;
use App\Core\Http\ResponseInterface;
use App\Exception\HttpException;
use App\Http\Kernel;
use App\Tools\Str;
use ReflectionMethod;

class Router
{
    private array $routes;
    private Kernel $kernel;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
        $this->kernel = new Kernel(); // ✅ 实例化 Kernel
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
                $params = array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);

                $controller = new $route['controller']();
                $action = $route['action'];

                $ref = new ReflectionMethod($controller, $action);

                $args = [];

                foreach ($ref->getParameters() as $param) {
                    $attrs = $param->getAttributes(PathVariable::class);
                    $name = $param->getName();
                    $missingMsg = "缺少参数：{$name}";

                    if ($attrs) {
                        $anno = $attrs[0]->newInstance();
                        $name = $anno->name;
                        if ($anno->missingParamMessage) {
                            $missingMsg = $anno->missingParamMessage;
                        }
                    }

                    $hasKey = array_key_exists($name, $params);
                    $value = $hasKey ? $params[$name] : null;

                    // ✅ 空字符串也算“缺少参数”
                    $isMissing = !$hasKey || $value === '';

                    if ($isMissing) {
                        if ($param->isDefaultValueAvailable()) {
                            // 有默认值则使用默认值
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw new HttpException(400, $missingMsg);
                        }
                    } else {
                        $args[] = Str::urldecode($value);
                    }
                }

                $args = empty($args) ? [] : array_map(['App\Tools\Str', 'urldecode'], $args);
                EventManager::getInstance()->dispatch('controller.before_execute', $controller, $action, $args);

                // ✅ 1. 定义管道的最终目的地：执行控制器
                $controllerExecution = function () use ($ref, $controller, $args) {
                    return $ref->invoke($controller, ...$args);
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
