<?php
// 文件路径: app/Subscribers/DatabaseLogger/Subscriber.php

namespace App\Subscribers\DatabaseLogger;

use App\Subscribers\AbstractSubscriber;

class Subscriber extends AbstractSubscriber
{
    private const SLOW_QUERY_THRESHOLD = 100;

    public function subscribe(): void
    {
        // 使用辅助方法，代码更清晰
        $this->events()->on('app.boots', function() {
            // 示例：应用关闭时做点什么
            $this->log("asdad");
            $this->events()->dispatch('DatabaseLogger');
        });

//        DB::connection()->listen(function (QueryExecuted $query) {
//            if ($query->time > self::SLOW_QUERY_THRESHOLD) {
//
//            }
//        });
    }

    public function beforeSubscribe(): void
    {
        // 示例：确保日志目录存在
        $logDir = APP_ROOT . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    private function log(mixed $query): void
    {
        // ... log 方法保持不变 ...
        echo "$query";
    }
}