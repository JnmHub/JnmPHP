# 事件订阅者系统

Subscribers 命名空间为 JnmPHP 框架提供了完整的事件订阅者管理功能。它采用基于目录约定的自动发现机制，支持缓存优化，为应用程序提供了强大而灵活的事件处理能力。

## 概述

事件订阅者系统是 JnmPHP 框架事件系统的重要组成部分，它允许开发者以模块化的方式组织和注册事件监听器。通过继承抽象基类和遵循目录约定，系统可以自动发现和注册所有订阅者。

## 核心功能

- **自动发现机制**：基于目录约定的订阅者自动扫描
- **缓存优化**：生产环境支持订阅者缓存以提高性能
- **模块化组织**：每个订阅者模块独立组织在各自的目录中
- **生命周期管理**：提供订阅前后的钩子方法
- **类型安全**：确保订阅者继承正确的抽象基类
- **调试友好**：开发环境下实时扫描订阅者变更

## 核心组件

### AbstractSubscriber.php

抽象订阅者基类，定义了所有订阅者必须遵循的接口规范。

#### 主要方法

**核心方法：**
- `subscribe(): void` - **必须实现**，注册该订阅者关心的事件
- `beforeSubscribe(): void` - **可选**，订阅前执行的准备工作
- `afterSubscribe(): void` - **可选**，订阅后执行的清理工作

**辅助方法：**
- `events(): EventManager` - 获取事件管理器实例

#### 设计特点

- **final 构造函数**：确保正确的事件管理器注入
- **抽象方法强制**：要求子类必须实现 `subscribe()` 方法
- **钩子方法**：提供订阅生命周期的扩展点
- **类型安全**：确保所有订阅者都有统一的接口

### SubscriberCollector.php

订阅者收集器，负责扫描和缓存订阅者类信息。

#### 主要方法

**静态方法：**
- `run(): array` - 运行收集器，获取所有订阅者类名
- `collect(): void` - 重新收集并写入缓存
- `collectSubscribers(): array` - 扫描订阅者目录，收集类名

#### 工作流程

1. **缓存检查**：生产环境优先使用缓存
2. **目录扫描**：DEBUG 模式或缓存缺失时重新扫描
3. **类验证**：确保类存在且继承自 `AbstractSubscriber`
4. **缓存写入**：将收集结果写入缓存文件

#### 目录约定

```
app/Subscribers/
├── User/
│   └── Subscriber.php          # App\Subscribers\User\Subscriber
├── Order/
│   └── Subscriber.php          # App\Subscribers\Order\Subscriber
├── System/
│   └── Subscriber.php          # App\Subscribers\System\Subscriber
└── Log/
    └── Subscriber.php          # App\Subscribers\Log\Subscriber
```

## 使用示例

### 基本订阅者实现

```php
<?php
// app/Subscribers/User/Subscriber.php
namespace App\Subscribers\User;

use Kernel\Subscribers\AbstractSubscriber;
use Kernel\Events\EventManager;

class Subscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        // 监听用户注册事件
        $this->events()->on('user.registered', [$this, 'onUserRegistered']);

        // 监听用户登录事件
        $this->events()->on('user.login', [$this, 'onUserLogin']);

        // 监听用户注销事件
        $this->events()->on('user.logout', [$this, 'onUserLogout']);
    }

    public function onUserRegistered(array $user): void
    {
        // 发送欢迎邮件
        $this->sendWelcomeEmail($user['email']);

        // 记录日志
        Log::info("新用户注册: {$user['email']}");
    }

    public function onUserLogin(array $user): void
    {
        // 更新最后登录时间
        User::where('id', $user['id'])->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip()
        ]);

        // 记录登录日志
        Log::info("用户登录: {$user['email']}");
    }

    public function onUserLogout(array $user): void
    {
        // 记录注销日志
        Log::info("用户注销: {$user['email']}");
    }

    private function sendWelcomeEmail(string $email): void
    {
        // 发送邮件逻辑
        Mail::to($email)->send(new WelcomeEmail());
    }
}
```

### 带生命周期钩子的订阅者

```php
<?php
// app/Subscribers/Order/Subscriber.php
namespace App\Subscribers\Order;

use Kernel\Subscribers\AbstractSubscriber;

class Subscriber extends AbstractSubscriber
{
    private array $orderStats = [];

    public function beforeSubscribe(): void
    {
        // 订阅前的准备工作
        $this->orderStats = [
            'created' => 0,
            'paid' => 0,
            'shipped' => 0,
            'cancelled' => 0
        ];

        // 初始化统计缓存
        $this->initStatsCache();
    }

    public function subscribe(): void
    {
        // 订单相关事件监听
        $this->events()->on('order.created', [$this, 'onOrderCreated']);
        $this->events()->on('order.paid', [$this, 'onOrderPaid']);
        $this->events()->on('order.shipped', [$this, 'onOrderShipped']);
        $this->events()->on('order.cancelled', [$this, 'onOrderCancelled']);
    }

    public function afterSubscribe(): void
    {
        // 订阅后的清理工作
        $this->saveStatsCache();
        $this->scheduleStatsCleanup();
    }

    public function onOrderCreated(array $order): void
    {
        $this->orderStats['created']++;

        // 库存预扣
        $this->reserveInventory($order);

        // 发送确认邮件
        $this->sendOrderConfirmation($order);
    }

    public function onOrderPaid(array $order): void
    {
        $this->orderStats['paid']++;

        // 确认库存扣减
        $this->confirmInventoryDeduction($order);

        // 通知仓库准备发货
        $this->notifyWarehouse($order);
    }

    public function onOrderShipped(array $order): void
    {
        $this->orderStats['shipped']++;

        // 发送发货通知
        $this->sendShippingNotification($order);

        // 更新物流信息
        $this->updateTrackingInfo($order);
    }

    public function onOrderCancelled(array $order): void
    {
        $this->orderStats['cancelled']++;

        // 释放库存
        $this->releaseInventory($order);

        // 处理退款
        $this->processRefund($order);
    }

    private function initStatsCache(): void
    {
        // 初始化统计缓存逻辑
    }

    private function saveStatsCache(): void
    {
        // 保存统计缓存逻辑
    }

    private function scheduleStatsCleanup(): void
    {
        // 调度统计清理任务
    }
}
```

### 系统级订阅者

```php
<?php
// app/Subscribers/System/Subscriber.php
namespace App\Subscribers\System;

use Kernel\Subscribers\AbstractSubscriber;

class Subscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        // 系统级事件监听
        $this->events()->on('router.before_dispatch', [$this, 'onBeforeDispatch']);
        $this->events()->on('router.after_dispatch', [$this, 'onAfterDispatch']);
        $this->events()->on('controller.before_execute', [$this, 'onBeforeExecute']);
        $this->events()->on('controller.after_execute', [$this, 'onAfterExecute']);
        $this->events()->on('exception.occurred', [$this, 'onException']);
    }

    public function onBeforeDispatch(string $uri, string $method): void
    {
        // 记录请求开始
        $this->startRequestTimer();

        // 访问日志
        Log::info("请求开始: {$method} {$uri}", [
            'ip' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'timestamp' => microtime(true)
        ]);
    }

    public function onAfterDispatch(): void
    {
        // 记录请求结束
        $duration = $this->endRequestTimer();

        Log::info("请求完成", [
            'duration' => $duration,
            'memory_usage' => memory_get_peak_usage(true)
        ]);
    }

    public function onBeforeExecute($controller, string $action, array $args): void
    {
        // 性能监控开始
        $this->startProfiling($controller, $action);

        // 权限检查
        $this->checkPermissions($controller, $action);
    }

    public function onAfterExecute($response): void
    {
        // 性能监控结束
        $profile = $this->endProfiling();

        // 响应大小统计
        $responseSize = $this->calculateResponseSize($response);

        Log::debug("控制器执行完成", [
            'profile' => $profile,
            'response_size' => $responseSize
        ]);
    }

    public function onException(\Exception $exception): void
    {
        // 异常处理
        Log::error("应用异常", [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // 发送告警
        $this->sendAlert($exception);
    }

    private function startRequestTimer(): void
    {
        $_SERVER['REQUEST_START_TIME'] = microtime(true);
    }

    private function endRequestTimer(): float
    {
        return microtime(true) - ($_SERVER['REQUEST_START_TIME'] ?? 0);
    }

    private function startProfiling($controller, string $action): void
    {
        $_SERVER['PROFILE_START'] = [
            'controller' => $controller,
            'action' => $action,
            'time' => microtime(true),
            'memory' => memory_get_usage(true)
        ];
    }

    private function endProfiling(): array
    {
        $start = $_SERVER['PROFILE_START'] ?? [];
        $end = [
            'time' => microtime(true),
            'memory' => memory_get_usage(true)
        ];

        return [
            'controller' => $start['controller'] ?? 'unknown',
            'action' => $start['action'] ?? 'unknown',
            'duration' => $end['time'] - ($start['time'] ?? $end['time']),
            'memory_used' => $end['memory'] - ($start['memory'] ?? $end['memory'])
        ];
    }

    private function checkPermissions($controller, string $action): void
    {
        // 权限检查逻辑
    }

    private function calculateResponseSize($response): int
    {
        // 计算响应大小
        return strlen(serialize($response));
    }

    private function sendAlert(\Exception $exception): void
    {
        // 发送告警逻辑
    }
}
```

## 订阅者注册和使用

### 自动注册过程

```php
// 在框架启动时（通常是 EventServiceProvider 中）
class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 收集所有订阅者
        $subscriberClasses = SubscriberCollector::run();

        // 实例化并注册每个订阅者
        foreach ($subscriberClasses as $class) {
            $subscriber = new $class($this->app->make(EventManager::class));
            $subscriber->beforeSubscribe();
            $subscriber->subscribe();
            $subscriber->afterSubscribe();
        }
    }
}
```

### 手动触发事件

```php
// 在控制器或其他地方触发事件
class UserController extends BaseController
{
    public function register(Request $request): JsonResponse
    {
        $user = User::create($request->validated());

        // 触发用户注册事件
        EventManager::getInstance()->dispatch('user.registered', [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent')
        ]);

        return JsonResponse::success($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // 触发登录事件
            EventManager::getInstance()->dispatch('user.login', [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'ip' => $request->ip(),
                'remember' => $request->has('remember')
            ]);

            return JsonResponse::success(['user' => $user]);
        }

        return JsonResponse::error('登录失败');
    }
}
```

## 性能优化

### 缓存机制

```php
// 缓存文件示例：cache/subscribers.php
<?php return [
    'App\\Subscribers\\User\\Subscriber',
    'App\\Subscribers\\Order\\Subscriber',
    'App\\Subscribers\\System\\Subscriber',
    'App\\Subscribers\\Log\\Subscriber',
];
```

### 开发环境优化

```php
// 开发环境实时扫描
if (DEBUG) {
    // 每次请求都重新收集订阅者
    $subscriberClasses = SubscriberCollector::collectSubscribers();
} else {
    // 生产环境使用缓存
    $subscriberClasses = SubscriberCollector::run();
}
```

### 性能监控

```php
class PerformanceSubscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        $this->events()->on('*', [$this, 'monitorAllEvents']);
    }

    public function monitorAllEvents(string $eventName, ...$args): void
    {
        static $eventCounts = [];
        static $eventTimes = [];

        $start = microtime(true);

        // 记录事件调用次数
        $eventCounts[$eventName] = ($eventCounts[$eventName] ?? 0) + 1;

        // 记录事件执行时间
        $eventTimes[$eventName][] = microtime(true) - $start;

        // 定期输出统计信息
        if (count($eventCounts) % 100 === 0) {
            $this->logEventStats($eventCounts, $eventTimes);
        }
    }

    private function logEventStats(array $counts, array $times): void
    {
        Log::debug('事件统计', [
            'counts' => $counts,
            'avg_times' => array_map(function ($timeArray) {
                return array_sum($timeArray) / count($timeArray);
            }, $times)
        ]);
    }
}
```

## 最佳实践

### 1. 订阅者组织原则

```php
// 按功能模块组织订阅者
app/Subscribers/
├── Auth/          # 认证相关事件
├── User/          # 用户相关事件
├── Order/         # 订单相关事件
├── Payment/       # 支付相关事件
├── Notification/  # 通知相关事件
└── System/        # 系统级事件
```

### 2. 事件命名规范

```php
// 使用一致的命名格式：模块.动作
'user.registered'
'user.login'
'user.logout'

'order.created'
'order.paid'
'order.shipped'

'payment.success'
'payment.failed'

'notification.sent'
'notification.failed'
```

### 3. 错误处理

```php
class RobustSubscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        $this->events()->on('order.created', [$this, 'onOrderCreated']);
    }

    public function onOrderCreated(array $order): void
    {
        try {
            // 主要业务逻辑
            $this->processOrder($order);
        } catch (\Exception $e) {
            // 错误处理
            Log::error("订单处理失败", [
                'order_id' => $order['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'subscriber' => static::class
            ]);

            // 不要让订阅者错误影响主流程
        }
    }

    private function processOrder(array $order): void
    {
        // 具体的订单处理逻辑
    }
}
```

### 4. 依赖注入

```php
class DIsubscriber extends AbstractSubscriber
{
    private UserRepository $userRepository;
    private EmailService $emailService;
    private LoggerInterface $logger;

    // 通过构造函数注入依赖（需要修改抽象基类）
    public function __construct(EventManager $eventManager, UserRepository $userRepository, EmailService $emailService)
    {
        parent::__construct($eventManager);
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }

    public function subscribe(): void
    {
        $this->events()->on('user.registered', [$this, 'onUserRegistered']);
    }

    public function onUserRegistered(array $userData): void
    {
        $user = $this->userRepository->find($userData['id']);
        $this->emailService->sendWelcomeEmail($user);
    }
}
```

## 故障排除

### 常见问题

1. **订阅者未被注册**
   ```php
   // 检查类名是否正确
   $class = 'App\\Subscribers\\User\\Subscriber';
   if (!class_exists($class)) {
       error_log("订阅者类不存在: {$class}");
   }

   // 检查是否继承正确的基类
   $reflection = new ReflectionClass($class);
   if (!$reflection->isSubclassOf(AbstractSubscriber::class)) {
       error_log("订阅者未继承 AbstractSubscriber: {$class}");
   }
   ```

2. **缓存问题**
   ```php
   // 清除订阅者缓存
   $cacheFile = APP_ROOT . '/cache/subscribers.php';
   if (file_exists($cacheFile)) {
       unlink($cacheFile);
   }

   // 重新收集
   SubscriberCollector::collect();
   ```

3. **事件未触发**
   ```php
   // 检查事件名称是否匹配
   $eventName = 'user.registered';
   error_log("触发事件: {$eventName}");

   // 检查事件管理器实例
   $eventManager = EventManager::getInstance();
   error_log("事件管理器状态: " . get_class($eventManager));
   ```

4. **订阅者执行异常**
   ```php
   // 在订阅者中添加错误处理
   public function onEvent($data): void
   {
       try {
           // 业务逻辑
       } catch (\Exception $e) {
           error_log("订阅者执行异常: " . $e->getMessage());
           // 继续执行，不要影响其他订阅者
       }
   }
   ```

### 调试技巧

```php
// 调试订阅者收集过程
class DebugSubscriberCollector extends SubscriberCollector
{
    public static function run(): array
    {
        $subscribers = parent::run();

        if (DEBUG) {
            error_log("发现的订阅者: " . implode(', ', $subscribers));

            // 检查每个订阅者的详细信息
            foreach ($subscribers as $class) {
                $reflection = new ReflectionClass($class);
                error_log("订阅者详情: {$class}", [
                    'file' => $reflection->getFileName(),
                    'methods' => array_map(fn($m) => $m->getName(), $reflection->getMethods()),
                    'is_instantiable' => $reflection->isInstantiable()
                ]);
            }
        }

        return $subscribers;
    }
}
```

## 扩展指南

### 动态订阅者

```php
class DynamicSubscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        // 根据配置动态订阅事件
        $events = config('subscriptions.events', []);

        foreach ($events as $event => $handler) {
            if (method_exists($this, $handler)) {
                $this->events()->on($event, [$this, $handler]);
            }
        }
    }
}
```

### 条件订阅

```php
class ConditionalSubscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        // 只在特定环境下订阅
        if (config('features.email_notifications', false)) {
            $this->events()->on('user.registered', [$this, 'sendWelcomeEmail']);
        }

        // 只在调试模式下订阅
        if (DEBUG) {
            $this->events()->on('*', [$this, 'logAllEvents']);
        }
    }
}
```

### 事件过滤

```php
class FilteringSubscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        $this->events()->on('order.created', [$this, 'onOrderCreated'], [
            'filter' => function ($order) {
                // 只处理金额大于100的订单
                return $order['amount'] > 100;
            }
        ]);
    }

    public function onOrderCreated(array $order): void
    {
        // 处理大额订单
        $this->processLargeOrder($order);
    }
}
```