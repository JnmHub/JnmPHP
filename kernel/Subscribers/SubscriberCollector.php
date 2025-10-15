<?php

namespace Kernel\Subscribers;

use ReflectionClass;

class SubscriberCollector
{
    /**
     * 运行收集器，从缓存或实时扫描中获取所有订阅者类名。
     * @return string[]
     */
    public static function run(): array
    {
        $cacheFile = APP_ROOT . '/cache/subscribers.php';

        // 生产环境下，如果缓存存在，直接加载
        if (!DEBUG && file_exists($cacheFile)) {
            return require $cacheFile;
        }

        // DEBUG 模式或缓存不存在时，重新收集
        $subscribers = self::collectSubscribers();

        // 写入缓存
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export($subscribers, true) . ';'
        );

        return $subscribers;
    }
    public static function collect(): void
    {
        $cacheFile = APP_ROOT . '/cache/subscribers.php';
        $subscribers = self::collectSubscribers();
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export($subscribers, true) . ';'
        );
    }
    /**
     * 扫描订阅者目录，收集所有主文件中的订阅者类名。
     * @return string[]
     */
    private static function collectSubscribers(): array
    {
        $subscriberClasses = [];
        $subscriberPath = APP_ROOT . '/app/Subscribers';

        if (!is_dir($subscriberPath)) {
            return [];
        }

        // 使用 DirectoryIterator 只遍历第一层目录（每个订阅者模块）
        $iterator = new \DirectoryIterator($subscriberPath);

        foreach ($iterator as $dirInfo) {
            if ($dirInfo->isDot() || !$dirInfo->isDir()) {
                continue;
            }

            // 约定：只检查每个模块目录下的 Subscriber.php 文件
            $mainFile = $dirInfo->getPathname() . '/Subscriber.php';

            if (!file_exists($mainFile)) {
                continue;
            }

            // 根据目录名和文件名构建类名
            $moduleName = $dirInfo->getBasename();
            $class = 'App\\Subscribers\\' . $moduleName . '\\Subscriber';

            if (class_exists($class)) {
                $reflection = new ReflectionClass($class);
                // 确保它继承自我们的抽象类并且是可实例化的
                if ($reflection->isInstantiable() && $reflection->isSubclassOf(AbstractSubscriber::class)) {
                    $subscriberClasses[] = $class;
                }
            }
        }

        return $subscriberClasses;
    }
}