<?php

namespace Kernel\Redis;

use RuntimeException;

/**
 * Class RedisValidator
 *
 * 负责验证Redis扩展和依赖是否可用
 */
class RedisValidator
{
    /**
     * 检查Redis相关的PHP扩展是否可用
     *
     * @return bool
     */
    public static function isRedisExtensionAvailable(): bool
    {
        // 检查PHP Redis扩展
        if (extension_loaded('redis')) {
            return true;
        }

        // 检查Predis包是否可用（项目使用的是Predis）
        if (class_exists('Predis\Client')) {
            return true;
        }

        return false;
    }

    /**
     * 验证Redis扩展是否可用，如果不可用则抛出异常
     *
     * @param string $context 使用Redis的上下文（例如：'session', 'cache', 'database'）
     * @throws RuntimeException
     */
    public static function validateRedisExtension(string $context = 'Redis'): void
    {
        if (!self::isRedisExtensionAvailable()) {
            $message = sprintf(
                "[%s] Redis功能不可用。请安装以下依赖之一：\n" .
                "1. PHP Redis扩展 (redis extension): pecl install redis\n" .
                "2. 或确保Composer依赖已安装: composer require predis/predis\n\n" .
                "当前状态：\n" .
                "- PHP Redis扩展: %s\n" .
                "- Predis包: %s",
                $context,
                extension_loaded('redis') ? '已安装' : '未安装',
                class_exists('Predis\Client') ? '已安装' : '未安装'
            );

            throw new RuntimeException($message);
        }
    }

    /**
     * 获取Redis支持状态的详细信息
     *
     * @return array
     */
    public static function getRedisSupportInfo(): array
    {
        return [
            'php_redis_extension' => extension_loaded('redis'),
            'predis_package' => class_exists('Predis\Client'),
            'recommended' => extension_loaded('redis'), // 推荐使用PHP扩展，性能更好
            'available' => self::isRedisExtensionAvailable(),
        ];
    }
}