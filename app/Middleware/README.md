# 中间件 (Middleware)

此目录包含 JnmPHP 框架的所有中间件类。中间件提供了处理 HTTP 请求的层次化过滤机制，支持认证、授权、日志记录等功能。

## 目录结构

```
app/Middleware/
├── AdminCheckMiddleware.php      # 管理员权限验证中间件
├── AuthMiddleware.php            # 身份认证中间件
├── LogRequestMiddleware.php      # 请求日志中间件
├── StartSessionMiddleware.php    # 会话启动中间件
├── VerifyCsrfTokenMiddleware.php # CSRF 令牌验证中间件
└── README.md                     # 本文档
```

## 中间件架构

### 中间件接口

所有中间件都必须实现 `Kernel\Middleware\MiddlewareInterface` 接口：

```php
interface MiddlewareInterface
{
    public function handle(mixed $request, Closure $next);
}
```

### 中间件管理

中间件通过 `Kernel\Middleware\MiddlewareManager` 进行统一管理：

- **全局中间件：** 对所有请求生效的中间件
- **路由中间件：** 通过别名在控制器属性中应用的中间件

## 中间件详解

### 1. LogRequestMiddleware - 请求日志中间件

**功能：** 记录所有 HTTP 请求的日志信息

**特性：**
- 自动创建日志目录
- 记录请求时间、方法、URI
- 全局中间件，对所有请求生效

**实现细节：**
```php
public function handle(mixed $request, Closure $next)
{
    $logPath = APP_ROOT . '/logs/requests.log';
    $logMessage = sprintf(
        "[%s] %s %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI']
    );
    file_put_contents($logPath, $logMessage, FILE_APPEND);
    return $next($request);
}
```

**日志格式：**
```
[2024-01-15 14:30:25] GET /users
[2024-01-15 14:30:30] POST /api/login
```

**日志文件位置：** `logs/requests.log`

---

### 2. StartSessionMiddleware - 会话启动中间件

**功能：** 启动和管理用户会话

**特性：**
- 依赖注入 SessionManager
- 支持多种会话驱动（native、database）
- 全局中间件，确保会话可用

**依赖：**
- `Kernel\Session\SessionManager` - 会话管理器

**实现细节：**
```php
public function __construct(SessionManager $session)
{
    $this->session = $session;
}

public function handle(mixed $request, Closure $next)
{
    $this->session->start();
    return $next($request);
}
```

**支持的会话驱动：**
- `native` - PHP 原生 Session（默认）
- `database` - 数据库驱动（需要 DatabaseSessionDriver）
- `redis` - Redis 驱动（预留扩展）

**配置：** 通过 `config/session.driver` 设置驱动类型

---

### 3. VerifyCsrfTokenMiddleware - CSRF 令牌验证中间件

**功能：** 防止跨站请求伪造攻击

**特性：**
- 跳过读请求（GET、HEAD、OPTIONS）
- 支持豁免 URI 配置
- 支持表单和 Header 两种令牌传递方式
- 使用 `hash_equals` 防止时序攻击

**依赖：**
- `Kernel\Session\SessionManager` - 用于存储和获取 CSRF 令牌

**配置：**
```php
protected array $except = [
    // 'api/v1/*'  // 豁免的 URI 模式
];
```

**令牌验证流程：**
1. 检查是否为读请求，是则跳过验证
2. 检查 URI 是否在豁免列表中
3. 验证请求令牌与会话令牌是否匹配

**令牌获取方式：**
1. **表单字段：** `$_POST['_token']`
2. **HTTP Header：** `X-CSRF-TOKEN`

**使用方法：**
```php
// 在控制器中应用
#[Post('/submit-form')]
#[Middleware('CSRF')]
public function submitForm()
{
    // 表单提交处理
}
```

**前端集成：**
```php
// Blade 模板中生成令牌
<input type="hidden" name="_token" value="{{ csrf_token() }}">

// 或在 AJAX 请求中使用 Header
fetch('/api/data', {
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
});
```

---

### 4. AuthMiddleware - 身份认证中间件

**功能：** 验证请求的身份认证信息

**特性：**
- 支持 Bearer Token 认证
- 简单的令牌验证机制
- 返回 401 未授权异常

**认证方式：**
```php
$expectedToken = 'Bearer my-secret-token';

if (!isset($_SERVER['HTTP_AUTHORIZATION']) ||
    $_SERVER['HTTP_AUTHORIZATION'] !== $expectedToken) {
    throw new HttpException(401, 'Unauthorized');
}
```

**使用方法：**
```php
#[Get('/protected')]
#[Middleware('auth')]
public function protectedRoute()
{
    // 需要认证的接口
}
```

**HTTP Header 要求：**
```
Authorization: Bearer my-secret-token
```

---

### 5. AdminCheckMiddleware - 管理员权限验证中间件

**功能：** 验证用户是否具有管理员权限

**特性：**
- 基于查询参数的简单权限验证
- 返回 403 禁止访问异常
- 示例实现（实际项目中应基于用户会话）

**权限验证逻辑：**
```php
$isAdmin = isset($_GET['role']) && $_GET['role'] === 'admin';

if (!$isAdmin) {
    throw new HttpException(403, 'Forbidden: You must be an administrator.');
}
```

**使用方法：**
```php
#[Get('/admin/users')]
#[Middleware('admin')]
public function adminUsers()
{
    // 需要管理员权限的接口
}
```

**访问示例：**
```
GET /admin/users?role=admin
```

## 中间件配置

### 中间件别名注册

在 `Kernel\Middleware\MiddlewareManager` 中注册路由中间件别名：

```php
protected array $routeMiddlewareAliases = [
    'auth'   => \App\Middleware\AuthMiddleware::class,
    'log'    => LogRequestMiddleware::class,
    'admin'  => \App\Middleware\AdminCheckMiddleware::class,
    'CSRF'   => VerifyCsrfTokenMiddleware::class,
];
```

### 全局中间件配置

全局中间件按顺序在每次请求中执行：

```php
protected array $globalMiddleware = [
    LogRequestMiddleware::class,      // 1. 记录请求日志
    StartSessionMiddleware::class,    // 2. 启动会话
    // VerifyCsrfTokenMiddleware::class // 3. CSRF 验证（按需应用）
];
```

## 使用方法

### 1. 应用单个中间件

```php
use Kernel\Attribute\Middleware\Middleware;

#[Get('/api/users')]
#[Middleware('auth')]
public function getUsers()
{
    // 需要身份认证
}
```

### 2. 应用多个中间件

```php
#[Post('/admin/users')]
#[Middleware('auth', 'admin')]
public function createAdminUser()
{
    // 需要身份认证和管理员权限
}
```

### 3. 在类级别应用中间件

```php
#[Middleware('auth')]
class ApiController extends BaseController
{
    // 类中所有方法都需要身份认证
}
```

### 4. 结合其他属性使用

```php
#[Post('/forms/submit')]
#[Middleware('CSRF')]
public function submitForm(#[RequestBody] FormData $data)
{
    // 需要 CSRF 保护的表单提交
}
```

## 自定义中间件

### 创建中间件模板

```php
<?php

namespace App\Middleware;

use Closure;
use Kernel\Middleware\MiddlewareInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, Closure $next)
    {
        // 前置处理逻辑
        // 可以修改 $request 或进行验证

        $response = $next($request);

        // 后置处理逻辑
        // 可以修改 $response

        return $response;
    }
}
```

### 注册自定义中间件

1. **添加别名到 MiddlewareManager：**
```php
protected array $routeMiddlewareAliases = [
    'custom' => \App\Middleware\CustomMiddleware::class,
];
```

2. **在控制器中使用：**
```php
#[Get('/protected')]
#[Middleware('custom')]
public function protectedMethod()
{
    // 应用自定义中间件
}
```

## 中间件最佳实践

### 1. 中间件职责单一

每个中间件应该专注于单一职责：
- `AuthMiddleware` - 只处理身份认证
- `CsrfMiddleware` - 只处理 CSRF 验证
- `LogMiddleware` - 只处理日志记录

### 2. 错误处理

使用适当的 HTTP 状态码：
- `401 Unauthorized` - 身份认证失败
- `403 Forbidden` - 权限不足
- `419 CSRF Token Mismatch` - CSRF 令牌错误

### 3. 性能考虑

- 全局中间件尽量轻量
- 重量级操作（数据库查询、外部 API 调用）放在路由中间件中
- 合理配置中间件执行顺序

### 4. 安全性

- 使用 `hash_equals` 比较敏感字符串
- 避免时序攻击
- 合理设置 CSRF 豁免列表
- 定期更新认证令牌

### 5. 依赖注入

中间件支持依赖注入，可以在构造函数中注入所需服务：

```php
public function __construct(LoggerInterface $logger, SessionManager $session)
{
    $this->logger = $logger;
    $this->session = $session;
}
```

## 调试和监控

### 查看中间件执行日志

1. **请求日志：** 查看 `logs/requests.log` 文件
2. **中间件异常：** 检查应用程序错误日志
3. **会话状态：** 通过 SessionManager 调试会话

### 中间件性能监控

可以在中间件中添加性能监控代码：

```php
public function handle(mixed $request, Closure $next)
{
    $startTime = microtime(true);

    $response = $next($request);

    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000; // 毫秒

    // 记录性能数据
    $this->logger->info("Middleware execution time: {$duration}ms");

    return $response;
}
```

这个中间件系统为 JnmPHP 框架提供了强大的请求处理能力，支持灵活的配置和扩展。