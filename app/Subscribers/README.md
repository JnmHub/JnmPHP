# 事件订阅者 (Event Subscribers)

此目录包含 JnmPHP 框架的所有事件订阅者类。订阅者基于事件驱动架构，提供了解耦的应用程序组件通信机制。

## 目录结构

```
app/Subscribers/
├── Cors/                           # CORS 跨域处理订阅者
│   └── Subscriber.php
├── DatabaseLogger/                 # 数据库日志订阅者
│   └── Subscriber.php
├── Demo/                           # 演示订阅者
│   └── Subscriber.php
├── SubscriberCollector/            # 订阅者收集器
│   └── Subscriber.php
└── README.md                       # 本文档
```

## 事件系统架构

### 核心组件

#### 1. EventManager - 事件管理器
- **单例模式：** 全局唯一的事件管理实例
- **事件注册：** `on(eventName, callback)` - 注册事件监听器
- **事件分发：** `dispatch(eventName, ...args)` - 触发事件

#### 2. AbstractSubscriber - 抽象订阅者
- **依赖注入：** 自动注入 EventManager 实例
- **生命周期钩子：** beforeSubscribe、subscribe、afterSubscribe
- **辅助方法：** 提供 `events()` 方法便捷访问事件管理器

#### 3. SubscriberCollector - 订阅者收集器
- **自动发现：** 扫描 Subscribers 目录下的所有订阅者
- **缓存机制：** 生产环境使用缓存提高性能
- **约定优于配置：** 按目录结构自动组织订阅者

### 订阅者发现机制

```php
// 目录结构约定
app/Subscribers/
├── ModuleName/
│   └── Subscriber.php  // 类名: App\Subscribers\ModuleName\Subscriber
```

### 缓存系统

- **缓存文件：** `cache/subscribers.php`
- **开发环境：** 实时扫描，无缓存
- **生产环境：** 使用缓存提高启动性能

## 订阅者详解

### 1. CORS 跨域处理订阅者

**命名空间：** `App\Subscribers\Cors\Subscriber`

**功能：** 处理跨域资源共享（CORS）请求，支持前端跨域访问

#### 监听事件
- `router.before_dispatch` - 路由分发前触发

#### 实现特性
```php
public function subscribe(): void
{
    $this->events()->on('router.before_dispatch', function() {
        $request = Request::capture();

        // 设置 CORS 响应头
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: DNT, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Range, Authorization');
        header('Access-Control-Allow-Credentials: true');

        // 处理 OPTIONS 预检请求
        if ($request->method == 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    });
}
```

#### CORS 配置说明
- **允许源：** `*` (允许所有源，生产环境建议指定具体域名)
- **允许方法：** GET, POST, PUT, PATCH, DELETE, OPTIONS
- **允许头部：** 常用的 HTTP 请求头
- **凭证支持：** 允许携带 Cookies 等凭证信息

#### 使用场景
- **前后端分离：** 前端应用和后端 API 部署在不同域名
- **移动端 API：** 移动应用调用后端接口
- **第三方集成：** 其他网站集成你的 API 服务

#### 生产环境建议
```php
// 生产环境应该指定具体域名
header('Access-Control-Allow-Origin: https://yourdomain.com');
```

---

### 2. 数据库日志订阅者

**命名空间：** `App\Subscribers\DatabaseLogger\Subscriber`

**功能：** 监听数据库操作和应用程序事件，记录相关日志

#### 监听事件
- `app.boots` - 应用启动完成时触发

#### 实现特性
```php
class Subscriber extends AbstractSubscriber
{
    private const SLOW_QUERY_THRESHOLD = 100; // 慢查询阈值（毫秒）

    public function subscribe(): void
    {
        $this->events()->on('app.boots', function() {
            $this->log("应用启动完成");
            $this->events()->dispatch('DatabaseLogger');
        });
    }

    public function beforeSubscribe(): void
    {
        // 确保日志目录存在
        $logDir = APP_ROOT . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    private function log(mixed $message): void
    {
        echo $message; // 简单输出，实际应用中应该写入日志文件
    }
}
```

#### 预留功能
```php
// 注释掉的代码展示了数据库查询监听的预留功能
// DB::connection()->listen(function (QueryExecuted $query) {
//     if ($query->time > self::SLOW_QUERY_THRESHOLD) {
//         // 记录慢查询
//     }
// });
```

#### 扩展建议
- **慢查询监控：** 监控执行时间超过阈值的查询
- **SQL 日志：** 记录所有 SQL 执行语句
- **性能分析：** 统计数据库操作性能指标

---

### 3. 演示订阅者

**命名空间：** `App\Subscribers\Demo\Subscriber`

**功能：** 提供订阅者开发的基本示例和模板

#### 监听事件
- `app.bosot` - 演示事件（注意：拼写错误，应该是 `app.boot`）

#### 实现特性
```php
public function subscribe(): void
{
    $this->events()->on('app.bosot', function() {
        $this->log("演示订阅者被触发");
    });
}

public function beforeSubscribe(): void
{
    // 确保日志目录存在
    $logDir = APP_ROOT . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
}
```

#### 学习要点
- **基础结构：** 展示订阅者的基本结构
- **生命周期钩子：** 演示 `beforeSubscribe` 的使用
- **事件监听：** 展示如何监听自定义事件
- **日志记录：** 展示简单的日志输出方式

---

### 4. 订阅者收集器

**命名空间：** `App\Subscribers\SubscriberCollector\Subscriber`

**功能：** 监听订阅者收集事件，动态重新收集订阅者

#### 监听事件
- `SubscriberCollect` - 手动触发订阅者收集

#### 实现特性
```php
public function subscribe(): void
{
    $this->events()->on('SubscriberCollect', function() {
        SubscriberCollector::collect();
    });
}
```

#### 使用场景
- **热重载：** 开发时动态加载新的订阅者
- **缓存更新：** 生产环境手动更新订阅者缓存
- **调试模式：** 调试时强制重新扫描订阅者

#### 触发方式
```php
// 在代码中手动触发
event_manager()->dispatch('SubscriberCollect');
```

## 事件系统详解

### 事件生命周期

```php
// 1. 应用启动
AppServiceProvider -> EventServiceProvider -> 订阅者加载

// 2. 订阅者生命周期
beforeSubscribe() -> subscribe() -> afterSubscribe()

// 3. 请求处理
router.before_dispatch -> 控制器执行 -> 响应输出

// 4. 应用关闭
app.shutdown -> 清理操作
```

### 事件类型

#### 系统事件
- `app.boots` - 应用启动完成
- `app.shutdown` - 应用关闭
- `router.before_dispatch` - 路由分发前
- `router.after_dispatch` - 路由分发后

#### 自定义事件
```php
// 触发自定义事件
$this->events()->dispatch('user.registered', $user);

// 监听自定义事件
$this->events()->on('user.registered', function($user) {
    // 处理用户注册事件
});
```

### 事件参数传递

```php
// 触发事件时传递参数
$this->events()->dispatch('order.created', $order, $user);

// 监听器接收参数
$this->events()->on('order.created', function($order, $user) {
    // 处理订单创建事件
});
```

## 开发指南

### 创建新订阅者

#### 1. 创建目录结构
```bash
mkdir app/Subscribers/MyModule
touch app/Subscribers/MyModule/Subscriber.php
```

#### 2. 实现订阅者类
```php
<?php

namespace App\Subscribers\MyModule;

use Kernel\Subscribers\AbstractSubscriber;

class Subscriber extends AbstractSubscriber
{
    public function subscribe(): void
    {
        // 监听应用启动事件
        $this->events()->on('app.boots', function() {
            $this->log('MyModule 订阅者已加载');
        });

        // 监听用户注册事件
        $this->events()->on('user.registered', function($user) {
            $this->logUserRegistration($user);
        });
    }

    public function beforeSubscribe(): void
    {
        // 前置准备工作
        $this->ensureLogDirectory();
    }

    public function afterSubscribe(): void
    {
        // 后置清理工作
        $this->log('MyModule 订阅者注册完成');
    }

    private function logUserRegistration($user): void
    {
        // 记录用户注册日志
        $this->log("新用户注册: {$user->email}");
    }

    private function ensureLogDirectory(): void
    {
        $logDir = APP_ROOT . '/logs/mymodule';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = APP_ROOT . '/logs/mymodule/events.log';
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}
```

#### 3. 自动发现
订阅者会自动被 `SubscriberCollector` 发现并加载，无需手动注册。

### 最佳实践

#### 1. 订阅者设计原则
- **单一职责：** 每个订阅者专注于特定功能领域
- **松耦合：** 通过事件解耦，避免直接依赖
- **幂等性：** 多次执行应该产生相同结果
- **错误处理：** 妥善处理异常，避免影响其他订阅者

#### 2. 性能考虑
```php
// ✅ 推荐：延迟加载重量级操作
public function subscribe(): void
{
    $this->events()->on('heavy.task', function() {
        // 只在需要时执行重量级操作
        $this->performHeavyOperation();
    });
}

// ❌ 避免：在 subscribe 中执行重量级操作
public function subscribe(): void
{
    // 不要在这里执行数据库查询或文件操作
    $this->performHeavyOperation(); // 错误
}
```

#### 3. 错误处理
```php
public function subscribe(): void
{
    $this->events()->on('user.action', function($user) {
        try {
            $this->processUserAction($user);
        } catch (\Exception $e) {
            // 记录错误但不抛出异常
            $this->logError("处理用户操作失败: " . $e->getMessage());
        }
    });
}
```

#### 4. 配置驱动
```php
public function subscribe(): void
{
    // 根据配置决定是否启用功能
    if (config('mymodule.enabled', false)) {
        $this->events()->on('user.login', function($user) {
            $this->handleUserLogin($user);
        });
    }
}
```

### 调试技巧

#### 1. 事件监听调试
```php
public function subscribe(): void
{
    if (env('APP_DEBUG')) {
        $this->events()->on('debug.event', function() {
            $this->log('调试事件被触发');
        });
    }
}
```

#### 2. 订阅者状态检查
```php
// 在控制器中检查订阅者是否正常工作
event_manager()->dispatch('debug.event');
```

#### 3. 日志记录
```php
private function log(string $message): void
{
    if (env('APP_DEBUG')) {
        error_log("[Subscriber] $message");
    }
}
```

## 性能优化

### 缓存管理

#### 开发环境
```php
// 自动禁用缓存，实时扫描订阅者
SubscriberCollector::run(); // 每次都重新扫描
```

#### 生产环境
```php
// 使用缓存提高性能
$subscribers = require APP_ROOT . '/cache/subscribers.php';
```

#### 手动更新缓存
```php
// 触发缓存重新生成
event_manager()->dispatch('SubscriberCollect');
```

### 内存优化

```php
// ✅ 推荐：使用闭包而不是对象方法
$this->events()->on('simple.event', function() {
    echo "简单事件处理";
});

// ✅ 推荐：避免在闭包中引用大量数据
$this->events()->on('data.process', function($data) {
    $id = $data['id']; // 只提取需要的字段
    $this->processById($id);
});
```

## 安全考虑

### 1. 输入验证
```php
public function subscribe(): void
{
    $this->events()->on('user.input', function($input) {
        // 验证输入数据
        if (!$this->validateInput($input)) {
            throw new \InvalidArgumentException('无效输入');
        }
    });
}
```

### 2. 权限检查
```php
public function subscribe(): void
{
    $this->events()->on('admin.action', function($user, $action) {
        if (!$user->isAdmin()) {
            throw new \UnauthorizedException('权限不足');
        }
    });
}
```

### 3. 敏感信息保护
```php
private function logUserAction($user, $action): void
{
    // 避免记录敏感信息
    $logData = [
        'user_id' => $user->id,
        'action' => $action,
        'timestamp' => time()
        // 不要记录密码、token 等敏感信息
    ];

    $this->log(json_encode($logData));
}
```

## 测试策略

### 单元测试
```php
class MySubscriberTest extends TestCase
{
    public function testSubscribeMethod()
    {
        $subscriber = new \App\Subscribers\MyModule\Subscriber(
            $this->createMock(EventManager::class)
        );

        // 测试订阅逻辑
        $this->assertTrue($subscriber->subscribe());
    }
}
```

### 集成测试
```php
public function testEventHandling()
{
    // 触发事件
    event_manager()->dispatch('test.event', $testData);

    // 验证事件处理结果
    $this->assertLogFileContains('测试事件已处理');
}
```

这个事件订阅者系统为 JnmPHP 框架提供了强大的事件驱动架构，支持松耦合的组件通信和灵活的功能扩展。