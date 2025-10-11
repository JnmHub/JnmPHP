<?php
namespace App\Core;

use App\Core\Attribute\PathVariable;
use App\Tools\Str;
use ReflectionMethod;

class Router
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(string $uri, string $method)
    {
        // 清理 URI 中多余的 ?参数 和 尾部 /
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            // 匹配 HTTP 方法
            if (!in_array(strtoupper($method), $route['methods'])) {
                continue;
            }

            // ✅ 允许空参数匹配：用 * 而不是 +
            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]*)', $route['path']);
            $pattern = rtrim($pattern, '/') ?: '/';
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                // 提取命名捕获组
                $params = array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);

                $controller = new $route['controller']();
                $action = $route['action'];

                try {
                    $ref = new ReflectionMethod($controller, $action);
                } catch (\ReflectionException $e) {
                    echo $e->getMessage();
                    return;
                }

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
                            // 没有默认值则显示注解提示
                            http_response_code(400);
                            echo $missingMsg;
                            return;
                        }
                    } else {
                        $args[] = Str::urldecode($value);
                    }
                }

                $args = empty($args) ? [] : array_map(['App\Tools\Str', 'urldecode'], $args);
                return $ref->invoke($controller, ...$args);
            }
        }

        // 未匹配到路由
        http_response_code(404);
        echo "404 Not Found";
    }
}
