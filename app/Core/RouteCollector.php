<?php
// 在 app/Core/ 目录下创建 RouteCollector.php
namespace App\Core;

use App\Core\Attribute\Route;
use App\Core\Attribute\RoutePrefix;
use ReflectionClass;
use ReflectionMethod;

class RouteCollector
{
    public function collect(array $controllerPaths): array
    {
        $routes = [];

        foreach ($controllerPaths as $path) {
            // 这里需要一个逻辑来从文件路径获取完整的类名
            // 比如，将 /path/to/app/Controller/UserController.php 转换为 App\Controller\UserController
            $className = $this->getClassNameFromFile($path);

            if (!class_exists($className)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($className);
            $classPrefix = '';
            $classAttributes = $reflectionClass->getAttributes(RoutePrefix::class);
            if (!empty($classAttributes)) {
                // 获取注解实例并拿到前缀值
                $classPrefix = $classAttributes[0]->newInstance()->prefix;
            }
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    /** @var Route $route */
                    $route = $attribute->newInstance();
                    $fullPath = rtrim($classPrefix, '/') . '/' . ltrim($route->path, '/');

                    $routes[] = [
                        'path' => $fullPath, // <-- 使用拼接后的完整路径
                        'methods' => $route->methods,
                        'controller' => $className,
                        'action' => $method->getName(),
                    ];
                }
            }
        }

        return $routes;
    }

    // 一个辅助方法，需要你根据项目结构具体实现
    private function getClassNameFromFile(string $file): string
    {
        // 简化实现
        $ns = 'App\\Controller\\';
        $name = str_replace('.php', '', basename($file));
        return $ns . $name;
    }
}