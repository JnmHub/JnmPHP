<?php

namespace Kernel\Database;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

class DB
{
    /**
     * 【新增】静态持有的 Capsule 实例
     */
    public static ?Capsule $capsule = null;

    public static function init(Container $container): void
    {
        self::$capsule = new Capsule($container);
        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();
    }

    /**
     * 【新增】提供一个获取 Capsule 实例的公共方法
     */
    public static function getCapsule(): ?Capsule
    {
        return self::$capsule;
    }
}