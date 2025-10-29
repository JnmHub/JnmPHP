# 中间件系统 (Middleware System)

此目录包含 JnmPHP 框架的中间件系统实现。中间件系统采用洋葱模型设计，为 HTTP 请求处理提供了强大的可扩展性，支持全局中间件和路由级别的中间件。

## 目录结构

```
kernel/Middleware/
├── MiddlewareInterface.php    # 中间件接口定义
├── Pipeline.php               # 管道执行器（洋葱模型核心）
├── MiddlewareManager.php      # 中间件管理器
└── README.md                  # 本文档
```

## 系统架构

### 设计理念

JnmPHP 中间件系统采用以下设计理念：

1. **洋葱模型：** 请求和响应逐层通过中间件，支持前置和后置处理
2. **管道模式：** 使用 Pipeline 模式组织中间件执行流程
3. **接口规范：** 所有中间件必须实现统一的接口
4. **分层管理：** 支持全局中间件和路由中间件的分层管理
5. **灵活配置：** 支持通过别名简化中间件引用

### 中间件执行流程

```
HTTP 请求
    ↓
全局中间件 1 → 全局中间件 2 → 路由中间件 1 → 路由中间件 2
    ↓                                                    ↓
[控制器方法执行] ← 路由中间件 2 ← 路由中间件 1 ← 全局中间件 2 ← 全局中间件 1
    ↓
HTTP 响应
```

## 核心组件详解

### 1. MiddlewareInterface - 中间件接口

**功能：** 定义所有中间件必须实现的标准接口

```php
interface MiddlewareInterface
{
    /**
     * 处理传入的请求
     *
     * @param mixed $request 传入的请求对象
     * @param Closure $next 下一个中间件或控制器的闭包
     * @return mixed 处理后的响应
     */
    public function handle(mixed $request, Closure $next);
}
```

**接口规范：**
- **统一签名：** 所有中间件必须实现 `handle()` 方法
- **请求参数：** 第一个参数为请求对象
- **链式调用：** 第二个参数为下一个处理环节的闭包
- **响应返回：** 必须返回处理结果或响应

**标准中间件实现：**
```php
class ExampleMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next)
    {
        // 前置处理逻辑
        $this->beforeRequest($request);

        // 调用下一个中间件或控制器
        $response = $next($request);

        // 后置处理逻辑
        $this->afterResponse($response);

        return $response;
    }

    private function beforeRequest($request): void
    {
        // 请求前处理，如：日志记录、身份验证等
    }

    private function afterResponse($response): void
    {
        // 响应后处理，如：响应修改、统计记录等
    }
}
```

### 2. Pipeline - 管道执行器

**功能：** 实现洋葱模型的核心执行器，负责中间件的链式调用

#### 核心属性

```php
class Pipeline
{
    private mixed $passable;    // 要通过管道传递的对象（通常是请求）
    private array $pipes = [];  // 管道中的所有中间件
}
```

#### 静态初始化方法

```php
public static function init(): self
{
    return new self();
}
```

#### 流式接口设计

```php
// 设置要传递的对象
public function send(mixed $passable): self
{
    $this->passable = $passable;
    return $this;
}

// 设置中间件列表
public function through(array $pipes): self
{
    $this->pipes = $pipes;
    return $this;
}
```

#### then() 方法 - 执行管道

**功能：** 构建并执行中间件管道

```php
public function then(Closure $destination)
{
    // 使用 array_reduce 从里到外包装中间件
    $pipeline = array_reduce(
        array_reverse($this->pipes), // 反转数组，确保正确的执行顺序
        $this->carry(),
        function () use ($destination) {
            // 最里层：最终要执行的目标（控制器方法）
            return $destination();
        }
    );

    // 执行构建好的管道
    return $pipeline($this->passable);
}
```

**执行原理：**
1. **数组反转：** 确保中间件按正确顺序执行
2. **层层包装：** 每个中间件包装下一层，形成洋葱结构
3. **最终执行：** 调用最外层，触发整个链式反应

#### carry() 方法 - 包装器生成

**功能：** 生成用于包装下一层的闭包

```php
private function carry(): Closure
{
    return function ($stack, $pipe) {
        return function ($passable) use ($stack, $pipe) {
            // 验证中间件类是否存在
            if (!class_exists($pipe)) {
                throw new RuntimeException("中间件类不存在: {$pipe}");
            }

            // 通过容器实例化中间件
            $middleware = app($pipe);

            // 验证中间件接口实现
            if (!($middleware instanceof MiddlewareInterface)) {
                throw new RuntimeException("中间件 {$pipe} 必须实现 MiddlewareInterface 接口");
            }

            // 调用中间件的 handle 方法
            // 关键：将下一层（$stack）作为 $next 传递
            return $middleware->handle($passable, $stack);
        };
    };
}
```

**包装过程详解：**
```php
// 假设有中间件 [Middleware1, Middleware2, Middleware3]

// 1. 数组反转：[Middleware3, Middleware2, Middleware1]

// 2. 第一层包装（Middleware3）：
$stack1 = function($passable) use ($destination) {
    return $destination(); // 控制器执行
};

$wrapper1 = function($passable) use ($stack1, $pipe3) {
    $middleware3 = app($pipe3);
    return $middleware3->handle($passable, $stack1);
};

// 3. 第二层包装（Middleware2）：
$wrapper2 = function($passable) use ($wrapper1, $pipe2) {
    $middleware2 = app($pipe2);
    return $middleware2->handle($passable, $wrapper1);
};

// 4. 第三层包装（Middleware1）：
$finalPipeline = function($passable) use ($wrapper2, $pipe1) {
    $middleware1 = app($pipe1);
    return $middleware1->handle($passable, $wrapper2);
};

// 5. 执行顺序：Middleware1 → Middleware2 → Middleware3 → 控制器 → Middleware3 → Middleware2 → Middleware1
```

#### 使用示例

```php
// 基础用法
$response = Pipeline::init()
    ->send($request)
    ->through([Middleware1::class, Middleware2::class])
    ->then(function() use ($controller, $method) {
        return $controller->$method();
    });

// 在路由器中的实际应用
class Router
{
    public function dispatch($uri, $method, $request)
    {
        // ... 路由匹配逻辑 ...

        // 组合中间件
        $middlewares = array_merge(
            $this->kernel->getGlobalMiddleware(),
            $route['middlewares']
        );

        // 构建控制器执行闭包
        $controllerExecution = function() use ($controller, $action, $args) {
            return $container->call([$controller, $action], $args);
        };

        // 执行中间件管道
        $response = Pipeline::init()
            ->send($request)
            ->through(array_unique($middlewares))
            ->then($controllerExecution);

        return $response;
    }
}
```

### 3. MiddlewareManager - 中间件管理器

**功能：** 统一管理全局中间件和路由中间件别名

#### 全局中间件管理

```php
class MiddlewareManager
{
    /**
     * 全局中间件
     * 这些中间件会在每一次请求中按顺序执行
     */
    protected array $globalMiddleware = [
        LogRequestMiddleware::class,      // 请求日志
        StartSessionMiddleware::class,    // 会话启动
    ];

    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }
}
```

**特性说明：**
- **自动执行：** 全局中间件在所有请求中都会执行
- **执行顺序：** 按数组定义顺序依次执行
- **无需配置：** 不需要在路由或控制器中显式声明

#### 路由中间件别名管理

```php
/**
 * 路由中间件别名
 * 方便在注解中使用简短的名称代替完整的类名
 */
protected array $routeMiddlewareAliases = [
    'auth' => \App\Middleware\AuthMiddleware::class,
    'log' => LogRequestMiddleware::class,
    'admin' => \App\Middleware\AdminCheckMiddleware::class,
    'CSRF' => VerifyCsrfTokenMiddleware::class,
];

public function getRouteMiddlewareAliases(): array
{
    return $this->routeMiddlewareAliases;
}
```

**别名系统优势：**
- **简化引用：** 使用 `#[Middleware('auth')]` 代替完整类名
- **易于维护：** 统一管理中间件映射关系
- **可读性强：** 提高代码可读性和编写效率

#### 中间件注册示例

```php
// 在控制器中使用中间件别名
class UserController
{
    #[Get('/users/{id}')]
    #[Middleware('auth')]        // 使用别名
    #[Middleware('admin')]       // 多个中间件
    public function show(int $id)
    {
        // 控制器逻辑
    }
}

// 系统会自动解析为：
// [AuthMiddleware::class, AdminCheckMiddleware::class]
```

## 使用指南

### 1. 创建自定义中间件

#### 基础中间件

```php
<?php

namespace App\Middleware;

use Kernel\Middleware\MiddlewareInterface;
use Closure;

class CorsMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next)
    {
        // 前置处理：设置 CORS 头
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // 处理 OPTIONS 请求
        if ($request->getMethod() === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // 继续执行下一个中间件或控制器
        $response = $next($request);

        // 后置处理（可选）
        // 可以在这里修改响应头或内容

        return $response;
    }
}
```

#### 参数化中间件

```php
class RoleMiddleware implements MiddlewareInterface
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(mixed $request, Closure $next)
    {
        $user = session('user');

        if (!$user || !in_array($user->role, $this->allowedRoles)) {
            throw new HttpException(403, '权限不足');
        }

        return $next($request);
    }
}
```

#### 条件中间件

```php
class MaintenanceMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next)
    {
        // 检查是否处于维护模式
        if (config('app.maintenance_mode', false)) {
            // 检查是否有维护权限
            if (!$this->hasMaintenanceAccess($request)) {
                return response('系统维护中，请稍后再试', 503);
            }
        }

        return $next($request);
    }

    private function hasMaintenanceAccess($request): bool
    {
        $allowedIps = config('app.maintenance_ips', []);
        $clientIp = $request->getClientIp();

        return in_array($clientIp, $allowedIps);
    }
}
```

### 2. 注册中间件

#### 注册全局中间件

```php
// 在 MiddlewareManager 中添加全局中间件
class MiddlewareManager
{
    protected array $globalMiddleware = [
        LogRequestMiddleware::class,
        StartSessionMiddleware::class,
        CorsMiddleware::class,           // 新增
        MaintenanceMiddleware::class,    // 新增
    ];
}
```

#### 注册路由中间件别名

```php
// 在 MiddlewareManager 中添加别名
class MiddlewareManager
{
    protected array $routeMiddlewareAliases = [
        'auth' => \App\Middleware\AuthMiddleware::class,
        'admin' => \App\Middleware\AdminCheckMiddleware::class,
        'cors' => CorsMiddleware::class,           // 新增
        'throttle' => ThrottleMiddleware::class,   // 新增
        'cache' => CacheMiddleware::class,         // 新增
    ];
}
```

#### 在控制器中使用

```php
class ApiController
{
    #[Post('/api/data')]
    #[Middleware('cors')]
    #[Middleware('throttle:60,1')]  // 每分钟60次请求
    #[Middleware('auth')]
    public function createData(Request $request)
    {
        // 控制器逻辑
    }

    #[Get('/api/public/data')]
    #[Middleware('cors')]
    #[Middleware('cache:300')]  // 缓存5分钟
    public function getPublicData()
    {
        // 公开接口逻辑
    }
}
```

### 3. 中间件执行流程

#### 请求处理流程

```php
// 1. 路由匹配成功后
$route = [
    'controller' => 'UserController',
    'action' => 'show',
    'middlewares' => ['auth', 'admin'] // 来自注解的中间件
];

// 2. 组合中间件
$allMiddlewares = array_merge(
    $middlewareManager->getGlobalMiddleware(), // ['LogRequest', 'StartSession']
    $route['middlewares']                       // ['AuthMiddleware', 'AdminCheckMiddleware']
);
// 结果：['LogRequest', 'StartSession', 'AuthMiddleware', 'AdminCheckMiddleware']

// 3. 执行管道
$response = Pipeline::init()
    ->send($request)
    ->through($allMiddlewares)
    ->then(function() use ($controller, $action, $args) {
        return $controller->$action(...$args);
    });
```

#### 执行顺序图

```
请求进入
    ↓
LogRequestMiddleware::handle()
    - 记录请求日志
    ↓
StartSessionMiddleware::handle()
    - 启动会话
    ↓
AuthMiddleware::handle()
    - 验证用户身份
    ↓
AdminCheckMiddleware::handle()
    - 检查管理员权限
    ↓
UserController::show()
    - 执行控制器方法
    ↓
AdminCheckMiddleware (后置处理)
    - 记录管理操作
    ↓
AuthMiddleware (后置处理)
    - 更新最后活动时间
    ↓
StartSessionMiddleware (后置处理)
    - 保存会话数据
    ↓
LogRequestMiddleware (后置处理)
    - 记录响应日志
    ↓
响应返回
```

### 4. 高级用法

#### 中间件参数传递

```php
// 自定义中间件支持参数
class ThrottleMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = 'throttle:' . $request->getClientIp();
        $attempts = cache()->get($key, 0);

        if ($attempts >= $maxAttempts) {
            throw new HttpException(429, '请求过于频繁');
        }

        cache()->put($key, $attempts + 1, $decayMinutes * 60);

        $response = $next($request);

        return $response;
    }
}

// 在注解中使用参数
#[Middleware('throttle:100,2')]  // 每2分钟100次请求
#[Middleware('cache:3600')]       // 缓存1小时
```

#### 中间件依赖注入

```php
class DatabaseTransactionMiddleware implements MiddlewareInterface
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function handle(mixed $request, Closure $next)
    {
        // 开启事务
        $this->db->beginTransaction();

        try {
            $response = $next($request);

            // 提交事务
            $this->db->commit();

            return $response;
        } catch (\Exception $e) {
            // 回滚事务
            $this->db->rollback();
            throw $e;
        }
    }
}
```

#### 条件中间件执行

```php
class ConditionalMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next)
    {
        // 根据条件决定是否执行中间件逻辑
        if ($this->shouldExecute($request)) {
            return $this->process($request, $next);
        }

        return $next($request);
    }

    private function shouldExecute($request): bool
    {
        // 判断条件：API 请求、特定路由、环境等
        return str_starts_with($request->getPathInfo(), '/api/');
    }

    private function process($request, $next)
    {
        // 实际的中间件处理逻辑
        return $next($request);
    }
}
```

## 系统集成

### 1. 与路由系统集成

#### 路由属性解析

```php
class RouteCollector
{
    private function parseMiddlewareAttributes($reflection): array
    {
        $middlewares = [];
        $attributes = $reflection->getAttributes(Middleware::class);

        foreach ($attributes as $attribute) {
            $middleware = $attribute->newInstance();
            $middlewares[] = $middleware->name;
        }

        return $middlewares;
    }
}
```

#### 路由执行

```php
class Router
{
    public function dispatch($uri, $method, $request)
    {
        // 路由匹配...

        // 解析中间件
        $routeMiddlewares = $this->parseMiddlewares($route);

        // 组合中间件
        $middlewares = array_merge(
            $this->middlewareManager->getGlobalMiddleware(),
            $routeMiddlewares
        );

        // 执行管道
        return Pipeline::init()
            ->send($request)
            ->through($middlewares)
            ->then($controllerExecution);
    }
}
```

### 2. 与容器集成

#### 中间件解析

```php
// Pipeline 中的中间件解析
$middleware = app($pipe);

// 支持依赖注入
class LoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle($request, Closure $next)
    {
        $this->logger->info('Processing request', [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod()
        ]);

        return $next($request);
    }
}
```

### 3. 与事件系统集成

#### 中间件事件触发

```php
class EventAwareMiddleware implements MiddlewareInterface
{
    private EventManager $events;

    public function __construct(EventManager $events)
    {
        $this->events = $events;
    }

    public function handle($request, Closure $next)
    {
        // 触发中间件开始事件
        $this->events->dispatch('middleware.start', static::class, $request);

        try {
            $response = $next($request);

            // 触发中间件完成事件
            $this->events->dispatch('middleware.complete', static::class, $request, $response);

            return $response;
        } catch (\Exception $e) {
            // 触发中间件错误事件
            $this->events->dispatch('middleware.error', static::class, $request, $e);
            throw $e;
        }
    }
}
```

## 最佳实践

### 1. 中间件设计原则

#### 单一职责原则

```php
// ✅ 推荐：每个中间件专注一个功能
class AuthenticationMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        // 只负责身份验证
        if (!$this->isAuthenticated($request)) {
            throw new HttpException(401, '未授权');
        }

        return $next($request);
    }
}

// ✅ 推荐：权限验证单独的中间件
class AuthorizationMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        // 只负责权限检查
        if (!$this->isAuthorized($request)) {
            throw new HttpException(403, '权限不足');
        }

        return $next($request);
    }
}

// ❌ 避免：一个中间件处理多个职责
class AuthMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        // 既处理身份验证又处理权限检查
        if (!$this->isAuthenticated($request)) {
            throw new HttpException(401, '未授权');
        }

        if (!$this->isAuthorized($request)) {
            throw new HttpException(403, '权限不足');
        }

        return $next($request);
    }
}
```

#### 无状态设计

```php
// ✅ 推荐：无状态中间件
class CacheMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        $cacheKey = $this->generateCacheKey($request);

        if ($cached = cache()->get($cacheKey)) {
            return $cached;
        }

        $response = $next($request);
        cache()->put($cacheKey, $response, 3600);

        return $response;
    }
}

// ❌ 避免：有状态中间件（除非必要）
class StatefulMiddleware implements MiddlewareInterface
{
    private $requestCount = 0; // 状态存储

    public function handle($request, Closure $next)
    {
        $this->requestCount++; // 状态修改

        if ($this->requestCount > 100) {
            throw new HttpException(429, '请求过多');
        }

        return $next($request);
    }
}
```

### 2. 性能优化

#### 中间件顺序优化

```php
// ✅ 推荐：合理排序中间件
protected array $globalMiddleware = [
    // 1. 快速失败的中间件放前面
    MaintenanceMiddleware::class,        // 维护检查
    CorsMiddleware::class,              // CORS 处理

    // 2. 资源密集型中间件放中间
    StartSessionMiddleware::class,      // 会话启动
    AuthenticationMiddleware::class,    // 身份验证

    // 3. 日志记录中间件放最后
    LogRequestMiddleware::class,        // 请求日志
];

// ❌ 避免：重中间件在前面
protected array $globalMiddleware = [
    DatabaseTransactionMiddleware::class,  // 重操作在前面
    LogRequestMiddleware::class,            // 轻操作在后面
];
```

#### 条件执行优化

```php
// ✅ 推荐：条件执行避免不必要处理
class ApiVersionMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        // 只对 API 路径执行
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return $next($request);
        }

        // API 版本处理逻辑
        $version = $this->extractApiVersion($request);
        $request->headers->set('API-Version', $version);

        return $next($request);
    }
}
```

### 3. 错误处理

#### 优雅的错误处理

```php
class RobustMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (HttpException $e) {
            // 处理 HTTP 异常
            $this->logHttpException($e);
            throw $e;
        } catch (\Exception $e) {
            // 处理其他异常
            $this->logException($e);

            // 转换为用户友好的错误
            if (config('app.debug')) {
                throw $e;
            } else {
                throw new HttpException(500, '服务器内部错误');
            }
        }
    }

    private function logHttpException(HttpException $e): void
    {
        logger()->warning('HTTP Exception in middleware', [
            'middleware' => static::class,
            'message' => $e->getMessage(),
            'code' => $e->getStatusCode()
        ]);
    }

    private function logException(\Exception $e): void
    {
        logger()->error('Unexpected exception in middleware', [
            'middleware' => static::class,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
```

### 4. 测试友好设计

#### 可测试的中间件

```php
class TestableMiddleware implements MiddlewareInterface
{
    private ExternalService $service;
    private LoggerInterface $logger;

    public function __construct(ExternalService $service, LoggerInterface $logger)
    {
        $this->service = $service;
        $this->logger = $logger;
    }

    public function handle($request, Closure $next)
    {
        if ($this->service->isValid($request)) {
            $this->logger->info('Request is valid');
            return $next($request);
        }

        $this->logger->warning('Invalid request blocked');
        throw new HttpException(400, '请求无效');
    }
}

// 单元测试
class TestableMiddlewareTest extends TestCase
{
    public function testValidRequestPassesThrough()
    {
        $mockService = Mockery::mock(ExternalService::class);
        $mockService->shouldReceive('isValid')->andReturn(true);

        $mockLogger = Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')->once();

        $middleware = new TestableMiddleware($mockService, $mockLogger);
        $request = $this->createMockRequest();

        $nextCalled = false;
        $next = function() use (&$nextCalled) {
            $nextCalled = true;
            return 'response';
        };

        $result = $middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals('response', $result);
    }
}
```

## 故障排除

### 1. 常见问题

#### 中间件不执行

```php
// 问题：中间件没有执行
class MyMiddleware implements MiddlewareInterface
{
    public function handle($request, $next)
    {
        // 这个方法没有被调用
        return $next($request);
    }
}

// 解决方案：
// 1. 检查中间件是否正确注册
$middlewareManager->getGlobalMiddleware(); // 确认中间件在列表中

// 2. 检查接口实现
class MyMiddleware implements MiddlewareInterface  // 确保实现了接口
{
    public function handle(mixed $request, Closure $next)  // 确保方法签名正确
    {
        return $next($request);
    }
}

// 3. 检查路由配置
#[Get('/test')]
#[Middleware('my-middleware-alias')]  // 确保别名正确
public function test() {}
```

#### 中间件执行顺序错误

```php
// 问题：中间件执行顺序不符合预期
// 实际顺序：B → A → 控制器 → A → B
// 期望顺序：A → B → 控制器 → B → A

// 解决方案：检查数组定义顺序
protected array $globalMiddleware = [
    MiddlewareA::class,  // 先定义的先执行
    MiddlewareB::class,  // 后定义的后执行
];

// 路由中间件顺序
#[Middleware('middleware-a')]  // 先定义的先执行
#[Middleware('middleware-b')]  // 后定义的后执行
```

#### 中间件依赖注入失败

```php
// 问题：中间件依赖无法注入
class MyMiddleware implements MiddlewareInterface
{
    public function __construct(SomeService $service)  // SomeService 无法解析
    {
        $this->service = $service;
    }
}

// 解决方案：
// 1. 确保依赖已注册到容器
$app->singleton(SomeService::class, SomeServiceImpl::class);

// 2. 检查服务提供者是否已加载
// config/providers.php 中确保服务提供者已注册

// 3. 使用可选依赖
class MyMiddleware implements MiddlewareInterface
{
    public function __construct(SomeService $service = null)
    {
        $this->service = $service ?: new DefaultService();
    }
}
```

### 2. 调试技巧

#### 中间件执行追踪

```php
class DebuggingMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        echo "中间件 " . static::class . " 开始执行\n";

        try {
            $response = $next($request);

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            echo "中间件 " . static::class . " 执行完成\n";
            echo "耗时: " . (($endTime - $startTime) * 1000) . "ms\n";
            echo "内存使用: " . ($endMemory - $startMemory) . " bytes\n";

            return $response;
        } catch (\Exception $e) {
            echo "中间件 " . static::class . " 执行出错: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
```

#### 管道执行调试

```php
// 临时修改 Pipeline 类添加调试
class Pipeline
{
    public function then(Closure $destination)
    {
        echo "开始执行管道，中间件数量: " . count($this->pipes) . "\n";

        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            function () use ($destination) {
                echo "到达目标控制器\n";
                return $destination();
            }
        );

        $result = $pipeline($this->passable);

        echo "管道执行完成\n";

        return $result;
    }
}
```

这个中间件系统为 JnmPHP 框架提供了强大的请求处理扩展能力，通过洋葱模型和管道模式实现了灵活的中间件链式执行，支持复杂的业务逻辑处理和横切关注点的实现。