# 事件系统 (Event System)

此目录包含 JnmPHP 框架的事件管理系统。事件系统实现了发布-订阅模式，为应用程序组件间的解耦通信提供了强大的支持。

## 目录结构

```
kernel/Events/
├── EventManager.php    # 事件管理器核心类
└── README.md           # 本文档
```

## 系统架构

### 设计理念

JnmPHP 事件系统采用以下设计理念：

1. **单例模式：** 确保全局只有一个事件管理器实例
2. **发布-订阅模式：** 实现松耦合的组件通信
3. **轻量级实现：** 简洁而高效的事件处理机制
4. **动态注册：** 支持运行时动态注册事件监听器

### 核心组件

#### EventManager - 事件管理器

`EventManager` 是事件系统的核心类，提供以下主要功能：

- **监听器注册：** 注册事件监听器
- **事件分发：** 分发事件到相应的监听器
- **单例管理：** 确保全局唯一的实例

## 功能详解

### 1. 单例模式实现

#### getInstance() 方法 - 获取单例实例

```php
private static ?self $instance = null;

public static function getInstance(): self
{
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

**特性说明：**
- **延迟初始化：** 只在首次调用时创建实例
- **私有构造：** 防止外部直接实例化
- **全局访问：** 确保整个应用使用同一个事件管理器

#### 私有构造函数

```php
private function __construct() {}
```

**设计目的：**
- **单例保证：** 防止创建多个实例
- **封装性：** 控制实例创建过程

### 2. 监听器注册

#### on() 方法 - 注册事件监听器

```php
public function on(string $eventName, callable $callback): void
{
    self::$listeners[$eventName][] = $callback;
}
```

**参数说明：**
- `eventName` (string): 事件名称，用于标识特定的事件类型
- `callback` (callable): 回调函数，当事件触发时执行

**使用示例：**
```php
// 注册监听器
$eventManager = EventManager::getInstance();

// 监听用户注册事件
$eventManager->on('user.registered', function($user) {
    echo "用户 {$user->name} 已注册\n";
});

// 监听订单创建事件
$eventManager->on('order.created', function($order, $user) {
    // 处理订单创建逻辑
    $this->sendOrderConfirmation($order, $user);
});
```

#### 监听器数据结构

```php
/**
 * @var array 存放所有监听器
 * ['eventName' => [callable1, callable2], ...]
 */
private static array $listeners = [];
```

**数据组织：**
- **键名：** 事件名称
- **值：** 监听器回调函数数组（支持多个监听器）

### 3. 事件分发

#### dispatch() 方法 - 分发事件

```php
public function dispatch(string $eventName, ...$args): void
{
    if (isset(self::$listeners[$eventName])) {
        foreach (self::$listeners[$eventName] as $callback) {
            // 调用回调函数，并传入所有参数
            call_user_func($callback, ...$args);
        }
    }
}
```

**参数说明：**
- `eventName` (string): 要分发的事件名称
- `...$args` (mixed): 传递给监听器的参数

**执行流程：**
1. **检查监听器：** 查找是否有注册该事件的监听器
2. **遍历执行：** 依次调用所有相关的监听器
3. **参数传递：** 将所有参数传递给监听器回调

**使用示例：**
```php
// 分发用户注册事件
$eventManager = EventManager::getInstance();
$eventManager->dispatch('user.registered', $user);

// 分发订单创建事件
$eventManager->dispatch('order.created', $order, $user, $orderItems);

// 分发系统事件
$eventManager->dispatch('system.booted');
```

## 使用指南

### 1. 基础使用

#### 获取事件管理器

```php
use Kernel\Events\EventManager;

// 方法一：直接获取实例
$eventManager = EventManager::getInstance();

// 方法二：通过服务容器获取
$eventManager = app(EventManager::class);

// 方法三：在订阅者中自动注入
class MySubscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        // $this->events() 返回 EventManager 实例
        $this->events()->on('my.event', $this->handleEvent(...));
    }
}
```

#### 注册监听器

```php
// 注册闭包监听器
$eventManager->on('user.login', function($user) {
    // 处理用户登录逻辑
    $this->logUserLogin($user);
});

// 注册类方法监听器
class UserListener
{
    public function handleLogin($user)
    {
        // 处理用户登录
    }
}

$listener = new UserListener();
$eventManager->on('user.login', [$listener, 'handleLogin']);

// 注册静态方法监听器
$eventManager->on('user.logout', [UserListener::class, 'handleLogout']);
```

#### 分发事件

```php
// 分发简单事件
$eventManager->dispatch('app.ready');

// 分发带参数的事件
$eventManager->dispatch('user.registered', $user, $timestamp);

// 分发复杂事件
$eventManager->dispatch('order.processed', [
    'order' => $order,
    'user' => $user,
    'items' => $items,
    'payment' => $payment
]);
```

### 2. 与订阅者系统集成

#### 在订阅者中使用

```php
class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册事件管理器为单例
        $this->container->singleton(EventManager::class, function () {
            return EventManager::getInstance();
        });
    }

    public function boot(): void
    {
        // 加载订阅者
        $subscriberClasses = SubscriberCollector::run();

        foreach ($subscriberClasses as $class) {
            $subscriber = $this->container->make($class);
            $subscriber->beforeSubscribe();
            $subscriber->subscribe();
            $subscriber->afterSubscribe();
        }
    }
}
```

#### 订阅者示例

```php
class UserEventSubscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        // 监听用户相关事件
        $this->events()->on('user.registered', [$this, 'handleUserRegistered']);
        $this->events()->on('user.login', [$this, 'handleUserLogin']);
        $this->events()->on('user.logout', [$this, 'handleUserLogout']);
    }

    public function handleUserRegistered($user)
    {
        // 发送欢迎邮件
        $this->sendWelcomeEmail($user);

        // 创建用户档案
        $this->createUserProfile($user);

        // 记录注册日志
        $this->logUserRegistration($user);
    }

    public function handleUserLogin($user)
    {
        // 更新最后登录时间
        $user->last_login_at = now();
        $user->save();

        // 记录登录日志
        $this->logUserLogin($user);
    }
}
```

### 3. 事件参数处理

#### 简单参数传递

```php
// 注册监听器
$eventManager->on('user.created', function($user) {
    echo "创建用户: {$user->name}\n";
});

// 分发事件
$eventManager->dispatch('user.created', $user);
```

#### 多参数传递

```php
// 注册监听器
$eventManager->on('order.created', function($order, $user, $items) {
    echo "用户 {$user->name} 创建了订单 {$order->id}\n";
    echo "订单包含 " . count($items) . " 个商品\n";
});

// 分发事件
$eventManager->dispatch('order.created', $order, $user, $items);
```

#### 数组参数传递

```php
// 注册监听器
$eventManager->on('data.processed', function($data) {
    echo "处理了 {$data['count']} 条记录\n";
    echo "耗时: {$data['duration']}ms\n";
});

// 分发事件
$eventManager->dispatch('data.processed', [
    'count' => 100,
    'duration' => 250,
    'success' => true
]);
```

### 4. 事件命名规范

#### 推荐的命名约定

```php
// ✅ 推荐：使用动词过去式
'user.registered'
'user.logged_in'
'order.created'
'payment.processed'

// ✅ 推荐：使用点号分隔的层级结构
'user.registered.email_sent'
'order.created.inventory_updated'
'system.cache.cleared'

// ✅ 推荐：系统级事件
'system.booted'
'system.shutdown'
'system.error_occurred'
```

#### 避免的命名方式

```php
// ❌ 避免：使用现在时
'user.register'  // 应该用 user.registered
'user.login'     // 应该用 user.logged_in

// ❌ 避免：使用下划线连接
'user_registered'
'order_created

// ❌ 避免：使用模糊的名称
'data_processed'
'something_happened'
```

## 高级特性

### 1. 事件监听器优先级

虽然当前实现没有内置优先级机制，但可以通过注册顺序模拟：

```php
class PriorityEventManager extends EventManager
{
    private static array $priorityGroups = [
        'high' => [],
        'normal' => [],
        'low' => []
    ];

    public function on(string $eventName, callable $callback, int $priority = 0): void
    {
        if ($priority > 0) {
            self::$priorityGroups['high'][$eventName][] = $callback;
        } elseif ($priority < 0) {
            self::$priorityGroups['low'][$eventName][] = $callback;
        } else {
            self::$priorityGroups['normal'][$eventName][] = $callback;
        }
    }

    public function dispatch(string $eventName, ...$args): void
    {
        // 按优先级顺序执行
        $groups = ['high', 'normal', 'low'];

        foreach ($groups as $group) {
            if (isset(self::$priorityGroups[$group][$eventName])) {
                foreach (self::$priorityGroups[$group][$eventName] as $callback) {
                    call_user_func($callback, ...$args);
                }
            }
        }
    }
}
```

### 2. 事件数据传递

#### 使用数据传输对象

```php
class UserRegisteredEvent
{
    public function __construct(
        public User $user,
        public DateTimeInterface $timestamp,
        public string $ipAddress
    ) {}
}

// 注册监听器
$eventManager->on('user.registered', function(UserRegisteredEvent $event) {
    echo "用户 {$event->user->name} 在 {$event->timestamp->format('Y-m-d H:i:s')} 从 {$event->ipAddress} 注册\n";
});

// 分发事件
$event = new UserRegisteredEvent($user, now(), $request->ip());
$eventManager->dispatch('user.registered', $event);
```

### 3. 异步事件处理

#### 模拟异步处理

```php
class AsyncEventManager extends EventManager
{
    public function dispatchAsync(string $eventName, ...$args): void
    {
        // 将事件加入队列
        $this->addToQueue($eventName, $args);
    }

    private function addToQueue(string $eventName, array $args): void
    {
        // 实现队列逻辑（简化示例）
        $queueData = [
            'event' => $eventName,
            'args' => $args,
            'timestamp' => time()
        ];

        file_put_contents('event_queue.log', json_encode($queueData) . "\n", FILE_APPEND);
    }

    public function processQueue(): void
    {
        // 处理队列中的事件
        $queueFile = 'event_queue.log';

        if (file_exists($queueFile)) {
            $lines = file($queueFile, FILE_IGNORE_NEW_LINES);

            foreach ($lines as $line) {
                $queueData = json_decode($line, true);
                parent::dispatch($queueData['event'], ...$queueData['args']);
            }

            // 清空队列
            file_put_contents($queueFile, '');
        }
    }
}
```

### 4. 事件中间件

#### 事件处理中间件

```php
class EventMiddlewareManager
{
    private array $middlewares = [];

    public function addMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function dispatchWithMiddleware(string $eventName, ...$args): void
    {
        $next = function() use ($eventName, $args) {
            EventManager::getInstance()->dispatch($eventName, ...$args);
        };

        // 反向执行中间件（类似洋葱模型）
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = fn() => $middleware($eventName, $args, $next);
        }

        $next();
    }
}
```

## 性能优化

### 1. 监听器缓存

```php
class CachedEventManager extends EventManager
{
    private static array $listenerCache = [];

    public function on(string $eventName, callable $callback): void
    {
        parent::on($eventName, $callback);

        // 缓存监听器以提高查找性能
        $this->cacheListeners();
    }

    private function cacheListeners(): void
    {
        self::$listenerCache = self::$listeners;
    }

    public function dispatch(string $eventName, ...$args): void
    {
        // 优先使用缓存的监听器
        $listeners = self::$listenerCache[$eventName] ?? [];

        foreach ($listeners as $callback) {
            call_user_func($callback, ...$args);
        }
    }
}
```

### 2. 延迟事件处理

```php
class LazyEventManager extends EventManager
{
    private array $pendingEvents = [];

    public function defer(string $eventName, ...$args): void
    {
        $this->pendingEvents[] = [
            'event' => $eventName,
            'args' => $args
        ];
    }

    public function flush(): void
    {
        foreach ($this->pendingEvents as $event) {
            $this->dispatch($event['event'], ...$event['args']);
        }

        $this->pendingEvents = [];
    }
}
```

### 3. 事件池

```php
class EventPool
{
    private static array $pools = [];

    public static function get(string $eventName): array
    {
        return self::$pools[$eventName] ?? [];
    }

    public static function add(string $eventName, callable $callback): void
    {
        self::$pools[$eventName][] = $callback;
    }

    public static function remove(string $eventName, callable $callback): void
    {
        if (isset(self::$pools[$eventName])) {
            self::$pools[$eventName] = array_filter(
                self::$pools[$eventName],
                fn($cb) => $cb !== $callback
            );
        }
    }
}
```

## 调试和监控

### 1. 事件日志

```php
class LoggingEventManager extends EventManager
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function on(string $eventName, callable $callback): void
    {
        parent::on($eventName, $callback);

        $this->logger->debug("注册事件监听器: {$eventName}");
    }

    public function dispatch(string $eventName, ...$args): void
    {
        $startTime = microtime(true);

        $this->logger->info("分发事件: {$eventName}");

        parent::dispatch($eventName, ...$args);

        $duration = (microtime(true) - $startTime) * 1000;
        $this->logger->info("事件处理完成: {$eventName} (耗时: {$duration}ms)");
    }
}
```

### 2. 事件追踪

```php
class EventTracer
{
    private static array $events = [];

    public static function trace(string $eventName, $callback = null): void
    {
        if ($callback) {
            // 注册监听器时记录
            self::$events[$eventName]['listeners'][] = $callback;
        } else {
            // 分发事件时记录
            self::$events[$eventName]['dispatches'][] = [
                'timestamp' => microtime(true),
                'args_count' => func_num_args() - 1
            ];
        }
    }

    public static function getTrace(): array
    {
        return self::$events;
    }

    public static function clearTrace(): void
    {
        self::$events = [];
    }
}
```

### 3. 性能监控

```php
class EventProfiler
{
    private static array $metrics = [];

    public static function profile(string $eventName, callable $callback): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        call_user_func($callback);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        self::$metrics[$eventName] = [
            'count' => (self::$metrics[$eventName]['count'] ?? 0) + 1,
            'total_time' => (self::$metrics[$eventName]['total_time'] ?? 0) + ($endTime - $startTime),
            'memory_usage' => $endMemory - $startMemory
        ];
    }

    public static function getMetrics(): array
    {
        return self::$metrics;
    }
}
```

## 最佳实践

### 1. 事件设计原则

#### 单一职责原则

```php
// ✅ 推荐：一个事件只做一件事
$eventManager->on('user.registered', $this->sendWelcomeEmail(...));
$eventManager->on('user.registered', $this->createUserProfile(...));

// ❌ 避免：一个事件做太多事
$eventManager->on('user.registered', function($user) {
    $this->sendWelcomeEmail($user);
    $this->createUserProfile($user);
    $this->updateStatistics($user);
    $this->notifyAdmin($user);
    $this->logActivity($user);
});
```

#### 事件命名规范

```php
// ✅ 推荐：使用过去时，动词在前
'user.registered'
'order.completed'
'payment.failed'

// ✅ 推荐：层级结构清晰
'user.profile.updated'
'order.payment.processed'
'system.cache.cleared'
```

### 2. 监听器设计

#### 避免异常传播

```php
// ✅ 推荐：捕获异常
$eventManager->on('user.registered', function($user) {
    try {
        $this->sendWelcomeEmail($user);
    } catch (\Exception $e) {
        // 记录错误但不影响其他监听器
        error_log("发送欢迎邮件失败: " . $e->getMessage());
    }
});

// ❌ 避免：异常影响其他监听器
$eventManager->on('user.registered', function($user) {
    $this->sendWelcomeEmail($user); // 如果这里抛异常，后续监听器不会执行
});
```

#### 保持监听器轻量

```php
// ✅ 推荐：将重量级操作异步化
$eventManager->on('user.registered', function($user) {
    // 只记录事件，将重量级操作放入队列
    JobQueue::push(new SendWelcomeEmailJob($user));
});

// ❌ 避免：在监听器中执行重量级操作
$eventManager->on('user.registered', function($user) {
    // 发送邮件（网络请求，可能很慢）
    Mail::to($user->email)->send(new WelcomeEmail());

    // 生成报告（可能很耗时）
    $this->generateUserReport($user);
});
```

### 3. 错误处理

#### 异常捕获策略

```php
class SafeEventManager extends EventManager
{
    public function dispatch(string $eventName, ...$args): void
    {
        if (!isset(self::$listeners[$eventName])) {
            return;
        }

        foreach (self::$listeners[$eventName] as $index => $callback) {
            try {
                call_user_func($callback, ...$args);
            } catch (\Exception $e) {
                // 记录错误但不影响其他监听器
                error_log("事件监听器异常 [{$eventName}][{$index}]: " . $e->getMessage());
            }
        }
    }
}
```

#### 监听器验证

```php
class ValidatingEventManager extends EventManager
{
    public function on(string $eventName, callable $callback): void
    {
        // 验证回调是否可调用
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException("回调函数不可调用");
        }

        // 验证事件名称
        if (empty($eventName)) {
            throw new \InvalidArgumentException("事件名称不能为空");
        }

        parent::on($eventName, $callback);
    }
}
```

## 扩展和自定义

### 1. 自定义事件管理器

```php
class CustomEventManager extends EventManager
{
    private array $eventHistory = [];

    public function dispatch(string $eventName, ...$args): void
    {
        // 记录事件历史
        $this->eventHistory[] = [
            'event' => $eventName,
            'args' => $args,
            'timestamp' => microtime(true)
        ];

        // 限制历史记录数量
        if (count($this->eventHistory) > 1000) {
            array_shift($this->eventHistory);
        }

        parent::dispatch($eventName, ...$args);
    }

    public function getEventHistory(): array
    {
        return $this->eventHistory;
    }
}
```

### 2. 事件调度器

```php
class EventScheduler
{
    private EventManager $eventManager;
    private array $scheduledEvents = [];

    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function schedule(string $eventName, callable $callback, int $delay = 0): void
    {
        $this->scheduledEvents[] = [
            'event' => $eventName,
            'callback' => $callback,
            'execute_at' => time() + $delay,
            'executed' => false
        ];
    }

    public function run(): void
    {
        foreach ($this->scheduledEvents as &$event) {
            if (!$event['executed'] && time() >= $event['execute_at']) {
                $this->eventManager->on($event['event'], $event['callback']);
                $this->eventManager->dispatch($event['event']);
                $event['executed'] = true;
            }
        }

        // 清理已执行的事件
        $this->scheduledEvents = array_filter(
            $this->scheduledEvents,
            fn($event) => !$event['executed']
        );
    }
}
```

### 3. 事件过滤器

```php
class EventFilter
{
    private array $filters = [];

    public function addFilter(string $eventName, callable $filter): void
    {
        $this->filters[$eventName][] = $filter;
    }

    public function filter(string $eventName, ...$args): array
    {
        $filteredArgs = $args;

        if (isset($this->filters[$eventName])) {
            foreach ($this->filters[$eventName] as $filter) {
                $filteredArgs = $filter(...$filteredArgs);
            }
        }

        return $filteredArgs;
    }
}
```

这个事件系统为 JnmPHP 框架提供了轻量级而强大的事件驱动架构，支持松耦合的组件通信和灵活的扩展机制。它是框架基础设施的重要组成部分，为应用程序的模块化和可扩展性提供了重要支撑。