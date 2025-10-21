<?php
use Monolog\Level;

return [

    /*
    |--------------------------------------------------------------------------
    | 默认日志通道
    |--------------------------------------------------------------------------
    |
    | APP_LOG_CHANNEL 环境变量用于指定默认通道。
    |
    */

    'default' => env('APP_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | 日志通道
    |--------------------------------------------------------------------------
    |
    | 这里定义了可用的日志通道。
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'], // 默认堆叠到 'daily' 通道
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => APP_ROOT . '/logs/jnm.log', // 使用 logs 目录
            'level' => env('APP_LOG_LEVEL', 'debug'),
            'days' => 14, // 保留14天的日志
        ],

        'single' => [
            'driver' => 'single',
            'path' => APP_ROOT . '/logs/jnm.log',
            'level' => env('APP_LOG_LEVEL', 'debug'),
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => Monolog\Handler\StreamHandler::class,
            'with' => [
                'stream' => 'php://stderr',
            ],
            'level' => 'debug',
        ],
    ],

];