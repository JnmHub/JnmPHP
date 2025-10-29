# 服务提供者 (Service Providers)

此目录包含 JnmPHP 框架的所有服务提供者类。服务提供者是框架的核心组件，负责服务的注册、配置和初始化。

## 目录结构

```
app/Providers/
├── AppServiceProvider.php         # 应用核心服务提供者
├── ConfigServiceProvider.php      # 配置服务提供者
├── DatabaseServiceProvider.php    # 数据库服务提供者
├── EventServiceProvider.php       # 事件服务提供者
├── LogServiceProvider.php         # 日志服务提供者
├── RouteServiceProvider.php       # 路由服务提供者
├── SessionServiceProvider.php     # 会话服务提供者
├── ViewServiceProvider.php        # 视图服务提供者
└── README.md                      # 本文档
```

## 服务提供者架构

### 基础抽象类

所有服务提供者都继承自 `Kernel\Providers\ServiceProvider`：

```php
abstract class ServiceProvider
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    abstract public function register(): void;
    public function boot(): void
    {
        // 默认无操作，子类可以重写
    }
}
```

### 生命周期

1. **register() 阶段：** 服务注册到容器，只做绑定，不执行实际操作
2. **boot() 阶段：** 所有服务已注册，可以安全地使用其他服务

### 注册顺序

在 `config/providers.php` 中定义的注册顺序：

```php
return [
    ConfigServiceProvider::class,      // 1. 配置服务（最先加载）
    AppServiceProvider::class,         // 2. 应用核心服务
    LogServiceProvider::class,         // 3. 日志服务
    EventServiceProvider::class,       // 4. 事件系统
    DatabaseServiceProvider::class,    // 5. 数据库连接
    RouteServiceProvider::class,       // 6. 路由系统
    ViewServiceProvider::class,        // 7. 视图引擎
    SessionServiceProvider::class,     // 8. 会话管理
];
```

## 服务提供者详解

### 1. ConfigServiceProvider - 配置服务

**功能：** 提供配置文件管理和访问服务

#### 服务绑定
- `ConfigRepository` - 配置仓库（单例）
- `config` - 配置访问别名

#### 实现细节
```php
public function register(): void
{
    $this->container->singleton(ConfigRepository::class, function () {
        return new ConfigRepository(base_path('config'));
    });

    $this->container->bind('config', function ($container) {
        return $container->make(ConfigRepository::class);
    });
}
```

#### 配置目录结构
```
config/
├── app.php          # 应用配置
├── database.php     # 数据库配置
├── logging.php      # 日志配置
└── providers.php    # 服务提供者配置
```

#### 使用示例
```php
// 在任何地方获取配置
$config = app('config');
$appName = $config->get('app.name', 'JnmPHP');

// 使用辅助函数
$dbConfig = config('database.connections.mysql');
```

---

### 2. AppServiceProvider - 应用核心服务

**功能：** 注册应用的核心服务和全局功能

#### 服务绑定
- `EventManager` - 事件管理器（单例）
- `Handler` - 异常处理器（单例）
- `translator` - 翻译器（单例）
- `validator` - 验证器工厂（单例）

#### 实现细节
```php
public function register(): void
{
    // 事件管理器
    $this->container->singleton(EventManager::class, function () {
        return EventManager::getInstance();
    });

    // 异常处理器
    $this->container->singleton(Handler::class, function () {
        return new Handler(
            $this->container->make(LoggerInterface::class)
        );
    });

    // 翻译器
    $this->container->singleton('translator', function () {
        $loader = new FileLoader(new Filesystem(), APP_ROOT . '/lang');
        return new Translator($loader, env('APP_LOCALE', 'en'));
    });

    // 验证器
    $this->container->singleton('validator', function () {
        return new ValidatorFactory($this->container->make('translator'));
    });
}

public function boot(): void
{
    // 注册错误和异常处理器
    $handler = $this->container->make(Handler::class);
    set_error_handler([$handler, 'handleError']);
    set_exception_handler([$handler, 'handleException']);
}
```

#### 功能特性
- **多语言支持：** 支持基于 Laravel 翻译组件的多语言
- **验证系统：** 集成验证器工厂，支持复杂验证规则
- **异常处理：** 统一的错误和异常处理机制
- **事件系统：** 全局事件管理器

---

### 3. LogServiceProvider - 日志服务

**功能：** 提供完整的日志记录服务，基于 Monolog

#### 服务绑定
- `LoggerInterface` - PSR-3 日志接口（单例）

#### 支持的日志驱动
- **daily** - 按天轮转日志文件（默认）
- **single** - 单一日志文件

#### 配置文件结构 (`config/logging.php`)
```php
return [
    'default' => 'daily',
    'channels' => [
        'daily' => [
            'driver' => 'daily',
            'path' => APP_ROOT . '/logs/jnmphp.log',
            'days' => 7,
        ],
        'single' => [
            'driver' => 'single',
            'path' => APP_ROOT . '/logs/jnmphp.log',
        ],
    ],
];
```

#### 实现特性
```php
public function register(): void
{
    $this->container->singleton(LoggerInterface::class, function () {
        $config = require APP_ROOT . '/config/logging.php';
        $defaultChannelName = $config['default'] ?? 'daily';
        $channelConfig = $config['channels'][$defaultChannelName];

        $logger = new Logger($defaultChannelName);

        // 根据驱动创建处理器
        switch ($channelConfig['driver']) {
            case 'daily':
                $handler = new RotatingFileHandler(
                    $channelConfig['path'],
                    $channelConfig['days'] ?? 7,
                    $this->parseLogLevel(env('APP_LOG_LEVEL', 'debug'))
                );
                break;
            // ... 其他驱动
        }

        $logger->pushHandler($handler);
        return $logger;
    });
}
```

#### 日志级别支持
- `debug` - 调试信息
- `info` - 一般信息
- `notice` - 注意信息
- `warning` - 警告信息
- `error` - 错误信息
- `critical` - 严重错误
- `alert` - 警报
- `emergency` - 紧急情况

#### 使用示例
```php
// 在控制器或其他服务中注入
class MyController extends BaseController
{
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function someMethod()
    {
        $this->logger->info('用户登录', ['user_id' => 123]);
        $this->logger->error('数据库连接失败', ['error' => $e->getMessage()]);
    }
}
```

---

### 4. DatabaseServiceProvider - 数据库服务

**功能：** 初始化数据库连接和 Eloquent ORM

#### 服务初始化
```php
public function boot(): void
{
    DB::init($this->container);
}
```

#### 特性
- **Eloquent ORM：** 基于 Laravel 的数据库 ORM
- **连接池：** 支持数据库连接配置
- **迁移支持：** 支持数据库迁移功能
- **查询构建器：** 提供强大的查询构建器

#### 配置文件 (`config/database.php`)
```php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];
```

---

### 5. RouteServiceProvider - 路由服务

**功能：** 加载和注册路由系统

#### 服务绑定
- `routes` - 路由收集器（单例）

#### 实现细节
```php
public function register(): void
{
    $this->container->singleton('routes', function () {
        return RouteCollector::run();
    });
}
```

#### 路由加载流程
1. **扫描控制器：** 自动发现带有路由属性的控制器
2. **解析属性：** 解析 `#[Get]`, `#[Post]` 等路由属性
3. **收集路由：** 将路由信息收集到路由表中
4. **缓存机制：** 支持路由缓存以提高性能

#### 路由特性
- **属性路由：** 基于 PHP 8 Attributes 的路由定义
- **中间件支持：** 支持路由级别的中间件
- **参数绑定：** 自动参数绑定和类型转换
- **缓存机制：** 开发环境自动清除缓存

---

### 6. EventServiceProvider - 事件服务

**功能：** 注册和管理事件订阅者

#### 实现流程
```php
public function boot(): void
{
    // 扫描并加载所有订阅者
    $subscriberClasses = SubscriberCollector::run();

    foreach ($subscriberClasses as $class) {
        $subscriber = $this->container->make($class);
        $subscriber->beforeSubscribe();
        $subscriber->subscribe();
        $subscriber->afterSubscribe();
    }
}
```

#### 事件系统特性
- **订阅者模式：** 基于订阅者的事件处理
- **依赖注入：** 订阅者支持依赖注入
- **生命周期钩子：** 提供 before/after 订阅钩子
- **自动发现：** 自动发现事件订阅者

#### 订阅者目录结构
```
app/Subscribers/
├── UserEventSubscriber.php
├── LogEventSubscriber.php
└── ...
```

---

### 7. ViewServiceProvider - 视图服务

**功能：** 配置 Blade 模板引擎和视图系统

#### 服务绑定
- `Factory` - 视图工厂（单例）
- `view` - 视图工厂别名

#### 实现特性
```php
public function register(): void
{
    $viewPaths = [APP_ROOT . '/app/View'];
    $cachePath = APP_ROOT . '/cache/views';

    // 创建完整的 Blade 视图系统
    $viewFactory = new Factory(
        $engineResolver,
        $viewFinder,
        $eventDispatcher
    );

    $this->container->singleton(Factory::class, fn() => $viewFactory);
    $this->container->singleton('view', fn() => $viewFactory);
}

public function boot(): void
{
    // 全局共享 CSRF 令牌
    $session = $this->container->make(SessionManager::class);
    $this->container->make('view')->share('_token', $session->token());
}
```

#### 视图系统特性
- **Blade 模板：** 完整的 Laravel Blade 模板引擎
- **编译缓存：** 支持模板编译缓存
- **全局变量：** 自动共享 CSRF 令牌等全局变量
- **模板继承：** 支持模板继承和组件化

#### 视图目录结构
```
app/View/
├── layouts/           # 布局模板
├── components/        # 组件模板
├── index/            # 首页相关模板
├── users/            # 用户相关模板
└── ...
```

---

### 8. SessionServiceProvider - 会话服务

**功能：** 提供会话管理服务

#### 服务绑定
- `SessionManager` - 会话管理器（单例）
- `session` - 会话管理器别名

#### 实现细节
```php
public function register(): void
{
    $this->container->singleton(SessionManager::class, function (Container $app) {
        return new SessionManager($app);
    });

    $this->container->bind('session', function (Container $app) {
        return $app->make(SessionManager::class);
    });
}
```

#### 会话驱动支持
- **native** - PHP 原生 Session（默认）
- **database** - 数据库驱动
- **redis** - Redis 驱动（预留）

#### 配置文件 (`config/session.php`)
```php
return [
    'driver' => env('SESSION_DRIVER', 'native'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE', false),
    'http_only' => true,
    'same_site' => 'lax',
];
```

## 使用指南

### 创建自定义服务提供者

#### 1. 创建服务提供者类

```php
<?php

namespace App\Providers;

use Kernel\Providers\ServiceProvider;

class CustomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册服务到容器
        $this->container->singleton(MyService::class, function () {
            return new MyService();
        });

        // 绑定别名
        $this->container->bind('my_service', function ($container) {
            return $container->make(MyService::class);
        });
    }

    public function boot(): void
    {
        // 启动时的操作
        // 可以使用其他已注册的服务
        $logger = $this->container->make(LoggerInterface::class);
        $logger->info('CustomServiceProvider booted');
    }
}
```

#### 2. 注册服务提供者

在 `config/providers.php` 中添加：

```php
return [
    // ... 其他服务提供者
    CustomServiceProvider::class,
];
```

### 依赖注入模式

#### 构造函数注入
```php
class MyController extends BaseController
{
    public function __construct(
        LoggerInterface $logger,
        SessionManager $session
    ) {
        $this->logger = $logger;
        $this->session = $session;
    }
}
```

#### 方法注入
```php
public function myMethod(LoggerInterface $logger)
{
    $logger->info('Method called');
}
```

#### 容器解析
```php
// 直接从容器解析
$service = app(MyService::class);

// 通过别名解析
$config = app('config');

// 使用 make 方法
$logger = app()->make(LoggerInterface::class);
```

### 容器绑定类型

#### Singleton（单例）
```php
$this->container->singleton(MyService::class, function () {
    return new MyService();
});
```

#### Bind（每次创建新实例）
```php
$this->container->bind(MyService::class, function () {
    return new MyService();
});
```

#### Instance（绑定具体实例）
```php
$instance = new MyService();
$this->container->instance(MyService::class, $instance);
```

## 最佳实践

### 1. 服务提供者设计原则

- **单一职责：** 每个服务提供者专注于特定领域
- **延迟加载：** 在 boot 阶段进行重量级操作
- **依赖管理：** 明确定义服务间的依赖关系
- **配置驱动：** 通过配置文件控制服务行为

### 2. 注册顺序考虑

```php
// ✅ 正确的顺序
ConfigServiceProvider::class,    // 配置先行
LogServiceProvider::class,      // 日志其次
DatabaseServiceProvider::class, // 数据库再次
OtherServiceProviders::class,   // 其他服务最后
```

### 3. 性能优化

```php
// ✅ 推荐：使用单例避免重复创建
$this->container->singleton(ExpensiveService::class);

// ✅ 推荐：延迟初始化
$this->container->singleton(LazyService::class, function () {
    return new LazyService(config('lazy.config'));
});

// ❌ 避免：在 register 中执行重量级操作
public function register(): void
{
    // 不要在这里执行数据库查询或文件操作
}
```

### 4. 错误处理

```php
public function register(): void
{
    $this->container->singleton(MyService::class, function () {
        try {
            return new MyService();
        } catch (\Exception $e) {
            $logger = $this->container->make(LoggerInterface::class);
            $logger->error('Failed to create service', ['error' => $e->getMessage()]);
            throw $e;
        }
    });
}
```

### 5. 测试友好设计

```php
public function register(): void
{
    $this->container->singleton(MyService::class, function () {
        $config = $this->container->make('config');
        return new MyService($config->get('my_service'));
    });
}

// 测试时可以轻松替换
app()->instance(MyService::class, $mockService);
```

## 调试和监控

### 服务注册调试

```php
public function boot(): void
{
    if (env('APP_DEBUG')) {
        $logger = $this->container->make(LoggerInterface::class);
        $logger->debug('Service booted', ['service' => static::class]);
    }
}
```

### 容器检查

```php
// 检查服务是否已注册
if (app()->bound(MyService::class)) {
    $service = app(MyService::class);
}

// 检查单例
if (app()->isShared(MyService::class)) {
    // 服务是单例
}
```

这个服务提供者系统为 JnmPHP 框架提供了强大而灵活的依赖注入和服务管理能力，支持现代化的 PHP 开发最佳实践。