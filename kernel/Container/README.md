# 容器系统 (Container System)

此目录包含 JnmPHP 框架的服务容器实现。容器系统基于 Laravel 的 IoC 容器，提供了依赖注入和服务管理的核心功能。

## 目录结构

```
kernel/Container/
├── KernelContainer.php    # 核心容器管理类
└── README.md              # 本文档
```

## 核心组件

### KernelContainer - 内核容器

`KernelContainer` 是容器系统的核心管理类，负责：

- **单例管理：** 确保容器的唯一实例
- **初始化管理：** 容器的初始化和配置
- **访问接口：** 提供容器实例的统一访问入口

## 系统架构

### 设计理念

JnmPHP 的容器系统采用以下设计理念：

1. **单一实例：** 整个应用生命周期中只有一个容器实例
2. **延迟初始化：** 容器在首次使用时才进行初始化
3. **自动绑定：** 容器实例自动绑定到自身，支持循环引用
4. **透明访问：** 通过辅助函数提供便捷的访问方式

### 容器层次结构

```
Application (应用单例)
    └── Container (IoC 容器)
        ├── Services (服务实例)
        ├── Bindings (服务绑定)
        ├── Singletons (单例服务)
        └── Instances (具体实例)
```

## 功能详解

### 1. 容器初始化

#### init() 方法 - 初始化容器

```php
public static function init(): IlluminateContainer
{
    if (self::$instance === null) {
        // 创建新的容器实例
        self::$instance = new IlluminateContainer();

        // 将容器实例自身绑定到容器中
        self::$instance->instance(IlluminateContainer::class, self::$instance);
    }
    return self::$instance;
}
```

**特性说明：**
- **单例模式：** 确保只创建一个容器实例
- **自绑定：** 容器可以解析自身，支持循环依赖
- **延迟创建：** 只在首次调用时创建实例

#### 初始化时机

```php
// 在应用构造函数中初始化
class Application
{
    private function __construct()
    {
        // 1. 初始化容器
        $this->container = KernelContainer::getInstance();

        // 2. 绑定应用实例到容器
        $this->container->instance(Application::class, $this);
        $this->container->alias(Application::class, 'app');
    }
}
```

### 2. 容器访问

#### getInstance() 方法 - 获取容器实例

```php
public static function getInstance(): IlluminateContainer
{
    if (self::$instance === null) {
        // 确保容器已初始化
        return self::init();
    }
    return self::$instance;
}
```

**使用方式：**

```php
// 直接获取容器实例
$container = KernelContainer::getInstance();

// 通过应用获取
$container = Application::getInstance()->getContainer();

// 通过辅助函数
$container = app();
```

### 3. 容器集成

#### 与 Application 集成

```php
class Application
{
    protected Container $container;

    public function __construct()
    {
        // 获取容器实例
        $this->container = KernelContainer::getInstance();

        // 绑定应用实例
        $this->container->instance(Application::class, $this);
        $this->container->alias(Application::class, 'app');
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
```

#### 与服务提供者集成

```php
abstract class ServiceProvider
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    // 在服务提供者中使用容器
    protected function bind(string $abstract, $concrete): void
    {
        $this->container->bind($abstract, $concrete);
    }

    protected function singleton(string $abstract, $concrete): void
    {
        $this->container->singleton($abstract, $concrete);
    }
}
```

## 使用指南

### 1. 基础使用

#### 获取容器实例

```php
use Kernel\Container\KernelContainer;

// 方法一：直接获取
$container = KernelContainer::getInstance();

// 方法二：通过应用获取
$container = app();

// 方法三：在服务提供者中
class MyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // $this->container 已经可用
        $this->container->bind('service', MyService::class);
    }
}
```

#### 解析服务

```php
// 解析类实例
$service = app(MyService::class);

// 解析接口实现
$logger = app(LoggerInterface::class);

// 解析别名服务
$config = app('config');
```

### 2. 服务绑定

#### 在服务提供者中绑定

```php
class MyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 绑定实现类
        $this->container->bind(MyService::class, MyServiceImpl::class);

        // 绑定接口
        $this->container->bind(
            LoggerInterface::class,
            MonologLogger::class
        );

        // 绑定闭包
        $this->container->bind('calculator', function() {
            return new Calculator();
        });

        // 单例绑定
        $this->container->singleton(ConfigRepository::class, function() {
            return new ConfigRepository(APP_ROOT . '/config');
        });
    }
}
```

#### 直接绑定服务

```php
$container = app();

// 绑定类
$container->bind(Repository::class, DatabaseRepository::class);

// 绑定实例
$container->instance('config', new ConfigRepository());

// 绑定单例
$container->singleton('cache', function() {
    return new CacheManager();
});
```

### 3. 依赖注入

#### 构造函数注入

```php
class UserController extends BaseController
{
    private LoggerInterface $logger;
    private UserRepository $repository;

    public function __construct(
        LoggerInterface $logger,
        UserRepository $repository
    ) {
        $this->logger = $logger;
        $this->repository = $repository;
    }
}

// 容器自动解析依赖
$userController = app(UserController::class);
```

#### 方法注入

```php
class MyService
{
    public function processData(LoggerInterface $logger): void
    {
        $logger->info('Processing data...');
    }
}

// 手动调用时注入参数
$service = app(MyService::class);
$service->processData(app(LoggerInterface::class));
```

### 4. 辅助函数使用

#### app() 函数

```php
// 获取容器实例
$container = app();

// 解析服务
$service = app(MyService::class);

// 带参数解析
$config = app('config')->get('app.name');
```

#### 其他依赖函数

```php
// 使用容器的其他辅助函数
$config = config('app.name'); // 依赖容器解析配置服务
$session = session(); // 依赖容器解析会话服务
```

## 高级特性

### 1. 上下文绑定

```php
$container = app();

// 为特定类绑定不同的实现
$container->when(UserController::class)
          ->needs(LoggerInterface::class)
          ->give(UserLogger::class);

$container->when(OrderController::class)
          ->needs(LoggerInterface::class)
          ->give(OrderLogger::class);
```

### 2. 标签服务

```php
// 绑定多个服务到同一标签
$container->tag(['service1', 'service2'], 'services');

// 解析标签下的所有服务
$services = app()->tagged('services');
```

### 3. 扩展服务

```php
// 扩展已绑定的服务
$container->extend('request', function($request) {
    return $request->withHeaders(['X-Custom' => 'value']);
});
```

### 4. 事件监听

```php
// 监听容器解析事件
$container->resolving(function ($object, $container) {
    // 服务解析后执行
});

$container->afterResolving(LoggerInterface::class, function ($logger) {
    // 特定服务解析后执行
});
```

## 性能优化

### 1. 单例模式

```php
// 使用单例减少对象创建开销
$container->singleton(ConfigRepository::class, function() {
    return new ConfigRepository(APP_ROOT . '/config');
});

// 全局访问单例
$config = app(ConfigRepository::class); // 同一实例
```

### 2. 延迟解析

```php
// 延迟绑定，只在需要时创建
$container->bind('expensive_service', function() {
    return new ExpensiveService();
});

// 只有在调用时才创建实例
$service = app('expensive_service');
```

### 3. 容器缓存

```php
// 容器缓存解析结果
class CachedContainer extends Container
{
    private array $cache = [];

    public function get($abstract)
    {
        $key = $abstract;

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $instance = parent::get($abstract);
        $this->cache[$key] = $instance;

        return $instance;
    }
}
```

## 调试和监控

### 1. 容器状态检查

```php
// 检查服务是否已绑定
if (app()->bound(MyService::class)) {
    $service = app(MyService::class);
}

// 检查服务是否为单例
if (app()->isShared(MyService::class)) {
    echo "服务是单例";
}
```

### 2. 服务解析调试

```php
// 调试模式下的服务解析
class DebugContainer extends Container
{
    protected function resolve($abstract, $parameters = [])
    {
        echo "解析服务: {$abstract}\n";

        $instance = parent::resolve($abstract, $parameters);

        echo "服务解析完成: " . get_class($instance) . "\n";

        return $instance;
    }
}
```

### 3. 依赖关系图

```php
// 生成服务依赖关系图
class ContainerAnalyzer
{
    public function analyzeDependencies(Container $container): array
    {
        $graph = [];

        foreach ($container->getBindings() as $abstract => $binding) {
            $dependencies = $this->extractDependencies($binding);
            $graph[$abstract] = $dependencies;
        }

        return $graph;
    }
}
```

## 最佳实践

### 1. 服务设计原则

#### 接口隔离

```php
// ✅ 推荐：面向接口编程
class UserController
{
    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}

// ❌ 避免：直接依赖具体实现
class UserController
{
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }
}
```

#### 依赖倒置

```php
// ✅ 推荐：高层模块不依赖低层模块
interface PaymentGatewayInterface
{
    public function charge(float $amount): bool;
}

class PaymentService
{
    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }
}
```

### 2. 绑定策略

#### 优先使用单例

```php
// ✅ 推荐：无状态服务使用单例
$container->singleton(ConfigRepository::class, ConfigRepository::class);
$container->singleton(LoggerInterface::class, Logger::class);

// ❌ 避免：有状态服务使用单例
$container->bind(Request::class, Request::class); // 每次请求新建
```

#### 合理使用闭包

```php
// ✅ 推荐：简单对象使用闭包
$container->singleton('cache', function() {
    return new CacheManager();
});

// ✅ 推荐：需要配置的对象使用闭包
$container->singleton(DatabaseManager::class, function($container) {
    return new DatabaseManager($container->make('config'));
});
```

### 3. 命名规范

#### 服务命名

```php
// ✅ 推荐：使用有意义的别名
$container->singleton('user.repository', UserRepository::class);
$container->singleton('payment.gateway', PaymentGateway::class);

// ❌ 避免：使用模糊的别名
$container->singleton('service1', Service1::class);
$container->singleton('helper', HelperClass::class);
```

## 故障排除

### 1. 常见问题

#### 循环依赖

```php
// 问题：A 依赖 B，B 依赖 A
class ServiceA
{
    public function __construct(ServiceB $b) {}
}

class ServiceB
{
    public function __construct(ServiceA $a) {}
}

// 解决：使用接口或事件解耦
interface ServiceAInterface
{
    public function process();
}

class ServiceA implements ServiceAInterface
{
    public function __construct(ServiceB $b) {}
}

class ServiceB
{
    public function __construct(ServiceAInterface $a) {}
}
```

#### 服务未找到

```php
// 问题：解析未绑定的服务
$service = app('unknown.service'); // 抛出异常

// 解决：检查绑定或提供默认值
if (app()->bound('unknown.service')) {
    $service = app('unknown.service');
} else {
    $service = new DefaultService();
}
```

### 2. 调试技巧

#### 检查绑定

```php
// 列出所有绑定
$bindings = app()->getBindings();
var_dump($bindings);

// 检查特定绑定
if (app()->bound(MyService::class)) {
    echo "服务已绑定";
} else {
    echo "服务未绑定";
}
```

#### 查看实例

```php
// 获取容器中的所有实例
$instances = app()->getInstances();
var_dump($instances);

// 检查是否为单例
if (app()->isShared(MyService::class)) {
    echo "服务是单例";
}
```

这个容器系统为 JnmPHP 框架提供了强大的依赖注入和服务管理能力，基于成熟的 Laravel IoC 容器，确保了稳定性和性能。它是框架基础设施的核心组成部分，为整个应用的组件化架构提供了支撑。