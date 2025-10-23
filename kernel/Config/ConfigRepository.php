<?php

namespace Kernel\Config;

class ConfigRepository
{
    /**
     * 存储所有配置项
     * @var array
     */
    protected array $config = [];

    /**
     * @param string $configPath config 目录的绝对路径
     */
    public function __construct(string $configPath)
    {
        $this->loadConfigurations($configPath);
    }

    /**
     * 加载所有配置文件
     */
    protected function loadConfigurations(string $path): void
    {
        // 确保路径存在
        if (!is_dir($path)) {
            // 在实际应用中, 这里应该抛出异常
            return;
        }

        // 遍历 config 目录下的所有 .php 文件
        foreach (glob($path . '/*.php') as $file) {
            // 'database.php' -> 'database'
            $key = basename($file, '.php');

            // 加载文件内容 (应该是返回一个数组)
            $this->config[$key] = require $file;
        }
    }

    /**
     * 使用点“.”符号获取配置项
     *
     * @param string $key 'database.connections.mysql.host'
     * @param mixed|null $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 检查是否是 'file.key' 这种点分格式
        if (!str_contains($key, '.')) {
            // 不是点分格式, 直接返回整个文件内容 (例如 config('database'))
            return $this->config[$key] ?? $default;
        }

        // 处理点分格式
        $keys = explode('.', $key);
        $config = $this->config;

        foreach ($keys as $segment) {
            if (is_array($config) && array_key_exists($segment, $config)) {
                $config = $config[$segment];
            } else {
                // 如果中间任何一个环节未找到, 返回默认值
                return $default;
            }
        }

        return $config;
    }

    /**
     * 检查配置项是否存在
     */
    public function has(string $key): bool
    {
        return !is_null($this->get($key));
    }
}