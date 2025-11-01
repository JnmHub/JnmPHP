<?php
namespace Kernel\Session;

use Illuminate\Contracts\Container\Container;
use Kernel\Session\Drivers\NativeSessionDriver;
use Kernel\Session\Drivers\DatabaseSessionDriver;
use Kernel\Session\Drivers\RedisSessionDriver;

//use Kernel\Session\Drivers\RedisSessionDriver;

/**
 * Class SessionManager
 *
 * 会话调度管理器，负责根据配置选择对应驱动。
 * 通过 __call 代理所有方法到具体驱动。
 */
class SessionManager
{
    protected mixed $driver;
    protected mixed $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $driverName = config('session.driver', 'native');

        switch ($driverName) {
            case 'database':
                if (class_exists(DatabaseSessionDriver::class)) {
                    $this->driver = new DatabaseSessionDriver();
                } else {
                    throw new \RuntimeException("DatabaseSessionDriver not found.");
                }
                break;
            case 'redis':
                if (class_exists(RedisSessionDriver::class)) {
                    $this->driver = new RedisSessionDriver();
                } else {
                    throw new \RuntimeException("RedisSessionDriver not found.");
                }
                break;

            default:
                $this->driver = new NativeSessionDriver();
        }
    }

    /**
     * 启动 session
     */
    public function start(): void
    {
        $this->driver->start();
    }

    /**
     * 获取驱动实例
     */
    public function driver(): SessionDriverInterface
    {
        return $this->driver;
    }

    /**
     * 代理调用具体驱动的方法
     */
    public function __call($method, $arguments)
    {
        if (!method_exists($this->driver, $method)) {
            throw new \BadMethodCallException("Method {$method} not found in session driver.");
        }
        return $this->driver->$method(...$arguments);
    }
}
