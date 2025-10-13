<?php
// 文件路径: app/Subscribers/DatabaseLogger/Subscriber.php

namespace App\Subscribers\SubscriberCollector;


use App\Subscribers\AbstractSubscriber;
use App\Subscribers\SubscriberCollector;

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