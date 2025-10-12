<?php

namespace App\Core\Routing;

use App\Core\Attribute\Middleware;
use App\Core\Attribute\Route;
use App\Core\Attribute\RoutePrefix;
use App\Http\Kernel;
use FilesystemIterator;
use ReflectionClass;
use RuntimeException;

class RouteCollector
{
    private static ?Kernel $kernel = null; // ✅ 用于缓存 Kernel 实例
    public static function run(): array
    {
        // 生产环境下直接从缓存加载
        if (!DEBUG && file_exists(APP_ROOT . '/cache/routes.php')) {
            return require APP_ROOT . '/cache/routes.php';
        }

        $routes = self::collectRoutes();

        // 写入缓存
        $cacheDir = APP_ROOT . '/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents(
            $cacheDir . '/routes.php',
            '<?php return ' . var_export($routes, true) . ';'
        );

        return $routes;
    }
    private static function getKernel(): Kernel
    {
        if (self::$kernel === null) {
            self::$kernel = new Kernel();
        }
        return self::$kernel;
    }
    private static function collectRoutes(): array
    {
        $routes = [];
        // ✅ 用于检测重复路由的辅助数组
        $existingRoutes = [];

        $controllerPath = APP_ROOT . '/app/Controller';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllerPath, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace([$controllerPath . '/', '.php'], '', $file->getRealPath());
            $class = 'App\\Controller\\' . str_replace('/', '\\', $relativePath);

            if (!class_exists($class)) {
                continue;
            }

            $ref = new ReflectionClass($class);
            $prefixAttrs = $ref->getAttributes(RoutePrefix::class);
            $prefix = $prefixAttrs ? $prefixAttrs[0]->newInstance()->prefix : '';
            // ✅ 1. 获取类级别中间件
            $classMiddlewareAttrs = $ref->getAttributes(Middleware::class);
            $classMiddlewares = self::parseMiddlewareAttributes($classMiddlewareAttrs);
            foreach ($ref->getMethods() as $method) {
                $routeAttrs = $method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);
                if (empty($routeAttrs)) continue;
                // ✅ 2. 获取方法级别中间件
                $methodMiddlewareAttrs = $method->getAttributes(Middleware::class);
                $methodMiddlewares = self::parseMiddlewareAttributes($methodMiddlewareAttrs);

                // ✅ 3. 合并类和方法的中间件
                $allMiddlewares = array_values(array_unique(array_merge($classMiddlewares, $methodMiddlewares)));


                foreach ($routeAttrs as $routeAttr) {
                    $route = $routeAttr->newInstance();
                    $fullPath = rtrim($prefix, '/') . '/' . ltrim($route->path, '/');
                    // ✅ 新增逻辑：检测重复路由
                    // 将所有请求方法都遍历一遍
                    foreach ($route->methods as $httpMethod) {
                        $routeIdentifier = $httpMethod . '@' . $fullPath;
                        if (isset($existingRoutes[$routeIdentifier])) {
                            // 如果路由已存在，抛出异常
                            $existingAction = $existingRoutes[$routeIdentifier];
                            throw new RuntimeException(
                                sprintf(
                                    "路由冲突: [%s %s] 同时被 %s::%s 和 %s::%s 定义。",
                                    $httpMethod,
                                    $fullPath,
                                    $class,
                                    $method->getName(),
                                    $existingAction['controller'],
                                    $existingAction['action']
                                )
                            );
                        }
                        // 记录该路由定义
                        $existingRoutes[$routeIdentifier] = [
                            'controller' => $class,
                            'action' => $method->getName(),
                        ];
                    }

                    // ✅ 新增逻辑：预编译正则表达式并存入 preg_path
                    $preg_path = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]*)', $fullPath);
                    $preg_path = rtrim($preg_path, '/') ?: '/';
                    $preg_path = '#^' . $preg_path . '$#';

                    $routes[] = [
                        'path' => $fullPath,
                        'preg_path' => $preg_path, // 新增字段
                        'methods' => $route->methods,
                        'controller' => $class,
                        'action' => $method->getName(),
                        'middlewares' => $allMiddlewares
                    ];
                }
            }
        }

        return $routes;
    }

    /**
     * ✅ 新增：解析中间件注解，支持别名
     */
    private static function parseMiddlewareAttributes(array $attributes): array
    {
        $middlewares = [];
        $aliases = self::getKernel()->getRouteMiddlewareAliases();

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            foreach ($instance->middlewares as $middleware) {
                // 如果是别名，则替换为完整的类名
                $middlewares[] = $aliases[$middleware] ?? $middleware;
            }
        }
        return $middlewares;
    }
}