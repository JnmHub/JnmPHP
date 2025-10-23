<?php


use Illuminate\Container\Container;
use kernel\Application;
use Kernel\Config\ConfigRepository;
use Kernel\Session\SessionManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
if (!function_exists('session')) {
    /**
     * 获取 session 实例或 session 中的值
     */
    function session($key = null, $default = null)
    {
        $session = app(SessionManager::class);
        if (is_null($key)) {
            return $session;
        }
        return $session->get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * 获取 CSRF Token
     */
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * 生成包含 CSRF Token 的表单隐藏域
     */
    function csrf_field(): string
    {
        $token = csrf_token();
        return "<input type=\"hidden\" name=\"_token\" value=\"{$token}\">";
    }
}
if (!function_exists('base_path')) {
    /**
     * 获取项目根目录的绝对路径
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        // APP_ROOT 是在 index.php 和 jnm 脚本中定义的
        if (empty($path)) {
            return APP_ROOT;
        }

        // 确保路径分隔符正确 (e.g., config -> /config)
        return APP_ROOT . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('config')) {
    /**
     * 获取指定的配置项
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key = null, mixed $default = null): mixed
    {
        $configRepo = app(ConfigRepository::class);

        if (is_null($key)) {
            return $configRepo;
        }

        return $configRepo->get($key, $default);
    }
}

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