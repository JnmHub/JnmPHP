<?php

namespace Kernel\Database;
use Illuminate\Database\Capsule\Manager as Capsule;

class DB
{
    /**
     * 【新增】静态持有的 Capsule 实例
     */
    public static ?Capsule $capsule = null;

    public static function init(): void
    {
        // 1. 创建实例并保存到静态属性中
        self::$capsule = new Capsule;

        $dbConfig = require APP_ROOT . '/config/database.php';

        // 2. 【保留上一步的修复】设置 config，让 DatabaseManager 能找到配置
        self::$capsule->getContainer()->singleton('config', function () use ($dbConfig) {
            return [
                'database.default' => 'default',
                'database.connections' => [
                    'default' => $dbConfig
                ],
            ];
        });

        // 3. 【保留上一步的修复】不再调用 addConnection

        // 4. 在静态实例上调用方法
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