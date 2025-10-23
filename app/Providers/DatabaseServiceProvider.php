<?php

namespace App\Providers;

use Kernel\Database\DB;
use Kernel\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 数据库连接在请求开始时就需要，所以在 boot 中初始化
    }

    public function boot(): void
    {
        DB::init($this->container);
    }
}