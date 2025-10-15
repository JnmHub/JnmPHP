<?php
// 文件路径: app/Subscribers/DatabaseLogger/Subscriber.php

namespace App\Subscribers\SubscriberCollector;


use Kernel\Subscribers\AbstractSubscriber;
use Kernel\Subscribers\SubscriberCollector;

class Subscriber extends AbstractSubscriber
{

    public function subscribe(): void
    {

        $this->events()->on('SubscriberCollect', function() {
            SubscriberCollector::collect();
        });

    }

    public function beforeSubscribe(): void
    {

    }

    private function log(mixed $query): void
    {

    }
}