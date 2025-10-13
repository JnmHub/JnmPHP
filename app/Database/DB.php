<?php

namespace App\Database;
use Illuminate\Database\Capsule\Manager as Capsule;

class DB
{
    public static function init(): void
    {
        $capsule = new Capsule;
        $dbConfig = require APP_ROOT . '/config/database.php';
        $capsule->addConnection($dbConfig);
        $capsule->setAsGlobal(); // 设置为全局可用
        $capsule->bootEloquent(); // 启动Eloquent ORM
    }
}