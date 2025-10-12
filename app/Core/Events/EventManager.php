<?php
// app/Core/Events/EventManager.php

namespace App\Core\Events;

class EventManager
{
    /**
     * @var array 存放所有监听器
     * ['eventName' => [callable1, callable2], ...]
     */
    private static array $listeners = [];

    private static ?self $instance = null;

    private function __construct() {} // 私有构造函数，防止外部 new

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 注册一个事件监听器
     * @param string $eventName 事件名称
     * @param callable $callback 回调函数
     */
    public function on(string $eventName, callable $callback): void
    {
        self::$listeners[$eventName][] = $callback;
    }

    /**
     * 分发一个事件
     * @param string $eventName 事件名称
     * @param mixed ...$args 传递给回调函数的参数
     */
    public function dispatch(string $eventName, ...$args): void
    {
        if (isset(self::$listeners[$eventName])) {
            foreach (self::$listeners[$eventName] as $callback) {
                // 调用回调函数，并传入所有参数
                call_user_func($callback, ...$args);
            }
        }
    }
}