<?php


use Illuminate\Container\Container;
use kernel\Application;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (!function_exists('app')) {
    /**
     * @param string|null $abstract
     * @return Container|mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function app(string $abstract = null)
    {
        $container = Application::getInstance()->getContainer();
        if (is_null($abstract)) {
            return $container;
        }

        return $container->get($abstract);
    }
}

if (!function_exists('e')) {
    /**
     * 对字符串进行 HTML 转义.
     *
     * @param string|null $value
     * @param bool $doubleEncode
     * @return string
     */
    function e($value, $doubleEncode = true)
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}
if (!function_exists('env')) {
    /**
     * 获取一个环境变量的值，并进行智能类型转换。
     * 优先检查 getenv()，然后检查 $_ENV（根据你的要求）。
     *
     * @param string $key 环境变量的键名
     * @param mixed|null $default 默认值
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        // 1. 优先检查 getenv() (根据你的要求)
        $value = getenv($key);

        // 2. 如果 getenv() 失败 (返回 false)，再尝试 $_ENV
        if ($value === false) {
            $value = $_ENV[$key] ?? false;
        }

        // 3. 如果两者都失败，返回默认值
        if ($value === false) {
            return $default;
        }

        // 4. 执行你要求的类型转换：
        // (vlucas/phpdotenv 库通常已经会把 true/false 转为 bool，
        //  但这里我们再次检查，以防 .env 中写的是 "true" 字符串)
        if (is_string($value)) {
            $lowerValue = strtolower($value);
            if ($lowerValue === 'true') {
                return true;
            }
            if ($lowerValue === 'false') {
                return false;
            }
        }

        // 5. 返回值 (可能是 bool, string, null)
        //    这满足了你的核心需求：bool 是 bool，其他（如 "Asia/Shanghai"）是 string
        return $value;
    }
}