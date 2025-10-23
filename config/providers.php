<?php

// 注册你的核心服务提供者
use App\Providers\AppServiceProvider;
use App\Providers\ConfigServiceProvider;
use App\Providers\DatabaseServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\LogServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\ViewServiceProvider;

return [
    ConfigServiceProvider::class,
    AppServiceProvider::class,
    LogServiceProvider::class,
    EventServiceProvider::class,
    DatabaseServiceProvider::class,
    RouteServiceProvider::class,
    ViewServiceProvider::class,
];