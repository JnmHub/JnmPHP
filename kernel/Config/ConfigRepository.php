<?php

namespace Kernel\Config;

use ArrayAccess;

/**
 * Class ConfigRepository
 *
 * 这是一个配置仓库类，用于加载并管理 config 目录下的所有配置文件。
 * 同时实现了 ArrayAccess 接口，使得配置项可以通过数组方式访问：
 *
 * 示例：
 *   $config = new ConfigRepository('/path/to/config');
 *   echo $config['database.default'];
 *   echo $config['app.name'];
 *
 * 它与 Laravel 的配置访问方式保持一致，
 * 以支持 Illuminate\Database\Capsule\Eloquent 等组件正常取值。
 *
 * @package Kernel\Config
 */
class ConfigRepository implements ArrayAccess
{
    /**
     * 存储所有已加载的配置项
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * 构造函数：自动加载配置目录下的所有 .php 文件
     *
     * @param string $configPath 配置目录的绝对路径
     */
    public function __construct(string $configPath)
    {
        $this->loadConfigurations($configPath);
    }

    /**
     * 加载 config 目录中的所有配置文件
     *
     * @param string $path 配置文件路径
     * @return void
     */
    protected function loadConfigurations(string $path): void
    {
        if (!is_dir($path)) {
            // 实际使用中建议抛出异常或记录日志
            return;
        }

        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }

    /**
     * 获取配置项的值，支持点号访问。
     *
     * 示例：
     *   get('database.default')
     *   get('app.name', 'MyApp')
     *
     * @param string $key     配置键名，例如 'database.connections.mysql.host'
     * @param mixed|null $default 默认值，当配置不存在时返回该值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!str_contains($key, '.')) {
            return $this->config[$key] ?? $default;
        }

        $segments = explode('.', $key);
        $data = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    /**
     * 设置配置项的值，支持点号路径。
     *
     * 示例：
     *   set('app.debug', true)
     *   set('database.connections.mysql.password', '123456')
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->setDotValue($key, $value);
    }

    /**
     * 检查配置项是否存在。
     *
     * 示例：
     *   has('app.name')
     *   has('database.connections.mysql.driver')
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return !is_null($this->get($key));
    }

    /* =====================================================
     * 实现 ArrayAccess 接口，使配置可用数组语法访问
     * ===================================================== */

    /**
     * 判断配置项是否存在
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string)$offset);
    }

    /**
     * 获取配置项值（通过数组方式）
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string)$offset);
    }

    /**
     * 设置配置项值（通过数组方式）
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string)$offset, $value);
    }

    /**
     * 删除配置项（通过数组方式）
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->set((string)$offset, null);
    }

    /* =====================================================
     * 内部工具：支持点号路径的取值与赋值
     * ===================================================== */

    /**
     * 按点号路径写入配置值
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setDotValue(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $data =& $this->config;

        foreach ($segments as $segment) {
            if (!isset($data[$segment]) || !is_array($data[$segment])) {
                $data[$segment] = [];
            }
            $data =& $data[$segment];
        }

        $data = $value;
    }
}
