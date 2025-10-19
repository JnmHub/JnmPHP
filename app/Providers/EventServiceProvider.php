<?php

namespace App\Providers;

use Kernel\Events\EventManager;
use Kernel\Providers\ServiceProvider;
use Kernel\Subscribers\SubscriberCollector;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 订阅者在 boot 阶段才需要，这里无需操作
    }

    public function boot(): void
    {
        // 将 index.php 中的订阅者加载逻辑移到这里
        $eventManager = $this->container->make(EventManager::class);
        $subscriberClasses = SubscriberCollector::run();

        foreach ($subscriberClasses as $class) {
            $subscriber = new $class($eventManager);
            $subscriber->beforeSubscribe();
            $subscriber->subscribe();
            $subscriber->afterSubscribe();
        }
    }
}