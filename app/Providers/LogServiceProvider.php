<?php

namespace App\Providers;

use Kernel\Providers\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 我们将 LoggerInterface 绑定为单例
        $this->container->singleton(LoggerInterface::class, function () {
            $configPath = APP_ROOT . '/config/logging.php';
            if (!file_exists($configPath)) {
                $logger = new Logger('jnmphp_fallback');
                $logger->pushHandler(new StreamHandler(APP_ROOT . '/logs/jnm_fallback.log', Logger::DEBUG));
                return $logger;
            }
            $config = require $configPath;
            $defaultChannelName = $config['default'] ?? 'daily';
            $channelConfig = $config['channels'][$defaultChannelName] ?? $config['channels']['daily'];

            $logger = new Logger($defaultChannelName);

            // 根据驱动创建 Handler
            switch ($channelConfig['driver']) {
                case 'daily':
                    $level = $this->parseLogLevel(env('APP_LOG_LEVEL', 'debug')); 
                    $handler = new RotatingFileHandler(
                        $channelConfig['path'],
                        $channelConfig['days'] ?? 7,
                        $level 
                    );
                    break;

                case 'single':
                default:
                    $level = $this->parseLogLevel(env('APP_LOG_LEVEL', 'debug')); 
                    $handler = new StreamHandler(
                        $channelConfig['path'],
                        $level 
                    );
                    break;
            }

            $logger->pushHandler($handler);
            return $logger;
        });
    }

    public function boot(): void
    {
        // 日志服务通常不需要 boot 操作
    }

    /**
     * 将 .env 中的字符串级别转换为 Monolog 的 int 级别
     * @param string $level
     * @return int
     */
    protected function parseLogLevel(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
            default => Logger::DEBUG,
        };
    }
}