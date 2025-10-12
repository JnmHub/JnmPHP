<?php
namespace App\Core\Routing;

use App\Core\Attribute\PathVariable;
use App\Core\Events\EventManager;
use App\Core\Http\JsonResponse;
use App\Core\Http\ResponseInterface;
use App\Exception\HttpException;
use App\Tools\Str;
use ReflectionMethod;

class Router
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @throws \ReflectionException
     * @throws HttpException
     */
    public function dispatch(string $uri, string $method)
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

                $response = $ref->invoke($controller, ...$args);

                // ✅ 核心改动：智能响应处理器
                if ($response instanceof ResponseInterface) {
                    // 1. 如果控制器返回的是一个响应对象 (JsonResponse 或 ViewResponse)
                    //    直接调用它的 send 方法
                    $response->send();
                } else {
                    // 2. 否则，默认其为API数据，自动用 JsonResponse::success 包装
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
