<?php
declare(strict_types=1);

use Kernel\Application;
use Kernel\Events\EventManager;
use Kernel\Request\Request;

include __DIR__ . "/vendor/autoload.php";
const APP_ROOT = __DIR__;
require APP_ROOT . '/kernel/Helpers/functions.php'; // 加载你的 env() 函数


$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set(env('APP_TIMEZONE'));
define("DEBUG", env('APP_DEBUG', false));


$app = new Application();
$app->registerProviders();
$app->bootProviders();



$eventManager = $app->getContainer()->make(EventManager::class);
$eventManager->dispatch('app.boot');


$request = Request::capture();
$app->handle($request);


$eventManager->dispatch('app.shutdown');