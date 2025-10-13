<?php
// 文件路径: app/Core/Events/AbstractSubscriber.php

namespace App\Subscribers;

use App\Events\EventManager;

abstract class AbstractSubscriber
{
    protected EventManager $eventManager;

    final public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * 【必须重写】
     * 注册该订阅者所关心的事件。
     * 在这个方法里，使用 $this->eventManager->on() 来注册监听器。
     */
    abstract public function subscribe(): void;

    /**
     * 【可选重写】
     * 在 subscribe() 方法被调用前执行的逻辑。
     * 可以在这里进行一些前置准备工作。
     */
    public function beforeSubscribe(): void
    {
        // 默认无操作
    }

    /**
     * 【可选重写】
     * 在 subscribe() 方法成功执行后调用的逻辑。
     */
    public function afterSubscribe(): void
    {
        // 默认无操作
    }

    /**
     * 辅助方法：提供一个方便的方式来获取事件管理器。
     */
    protected function events(): EventManager
    {
        return $this->eventManager;
    }
}