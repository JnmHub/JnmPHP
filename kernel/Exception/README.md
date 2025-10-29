# 异常系统 (Exception System)

此目录包含 JnmPHP 框架的异常处理系统。异常系统提供了统一的错误处理机制，支持不同类型的异常处理和响应格式。

## 目录结构

```
kernel/Exception/
├── Handler.php           # 异常处理器核心类
├── HttpException.php      # HTTP 异常类
├── BaseException.php     # 基础异常类
└── README.md             # 本文档
```

## 系统架构

### 设计理念

JnmPHP 异常系统采用以下设计理念：

1. **统一处理：** 所有异常通过统一的处理器处理
2. **分层处理：** 不同类型的异常有不同的处理策略
3. **环境适配：** 开发和生产环境使用不同的错误展示方式
4. **日志记录：** 所有异常都会记录到日志系统
5. **响应格式化：** 支持多种响应格式（HTML、JSON）

### 异常层次结构

```
Throwable (PHP 内置)
    ├── Exception
    │   ├── BaseException (框架基础异常)
    │   │   └── HttpException (HTTP 异常)
    │   └── 自定义业务异常
    └── Error (PHP 错误)
```

## 核心组件详解

### 1. Handler - 异常处理器

**功能：** 统一处理所有 PHP 错误和异常

#### 核心方法

##### handleError() - 处理 PHP 错误

```php
public function handleError($severity, $message, $file, $line): void
{
    if (!(error_reporting() & $severity)) {
        return;
    }

    // 将 PHP Error 转换为异常
    throw new ErrorException($message, 0, $severity, $file, $line);
}
```

**特性说明：**
- **错误级别过滤：** 只处理当前错误报告级别包含的错误
- **错误转换：** 将 PHP 错误转换为 ErrorException 异常
- **统一处理：** 转换后的异常会被 handleException 统一处理

##### handleException() - 统一异常处理

```php
public function handleException(Throwable $e): void
{
    // 1. 清理输出缓冲区
    if (ob_get_level()) {
        ob_end_clean();
    }

    // 2. 记录日志
    if (!$this->shouldntReport($e)) {
        $this->logger->error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    // 3. 根据异常类型渲染响应
    if ($e instanceof HttpException) {
        $this->handleHttpException($e);
        return;
    }

    if ($e instanceof ValidationException) {
        $this->handleValidationException($e);
        return;
    }

    // 4. 默认处理 500 错误
    $this->handleServerError($e);
}
```

**处理流程：**
1. **缓冲区清理** - 清理之前的输出，避免错误页面混乱
2. **日志记录** - 记录异常详细信息（除了特定异常）
3. **异常分类** - 根据异常类型选择不同的处理策略
4. **响应渲染** - 生成适当的错误响应

#### 依赖注入

```php
public function __construct(LoggerInterface $logger)
{
    $this->logger = $logger;
}
```

**设计目的：**
- **日志集成：** 自动注入日志服务进行错误记录
- **依赖解耦：** 通过依赖注入使用日志服务

### 2. HttpException - HTTP 异常

**功能：** 处理 HTTP 相关的异常，支持自定义状态码和响应头

#### 构造函数

```php
public function __construct(
    int $statusCode,        // HTTP 状态码
    string $message = "",   // 错误信息
    array $headers = [],     // 自定义响应头
    Throwable $previous = null  // 前一个异常
) {
    $this->statusCode = $statusCode;
    $this->headers = $headers;
    parent::__construct($message, 0, $previous);
}
```

**参数说明：**
- `statusCode` - HTTP 状态码（如 404、500、403）
- `message` - 错误信息
- `headers` - 自定义 HTTP 响应头
- `previous` - 前一个异常（异常链）

**使用示例：**
```php
// 抛出 404 异常
throw new HttpException(404, 'Page not found');

// 抛出带自定义头的异常
throw new HttpException(
    401,
    'Unauthorized',
    ['WWW-Authenticate' => 'Bearer realm="API"']
);

// 抛出带异常链的异常
throw new HttpException(
    500,
    'Database connection failed',
    [],
    new \PDOException('Connection failed')
);
```

#### 属性

```php
public int $statusCode;    // HTTP 状态码
public array $headers;        // 响应头数组
```

### 3. BaseException - 基础异常

**功能：** 框架所有自定义异常的基类

```php
class BaseException extends Exception
{
    // 你可以在这里添加所有自定义异常共有的属性或方法
    // 例如，可以强制所有子类都定义一个错误码
}
```

**设计目的：**
- **统一接口：** 为所有框架异常提供统一的基础功能
- **扩展性：** 便于添加全局异常行为
- **标识性：** 区分框架异常和 PHP 内置异常

## 使用指南

### 1. 异常处理器注册

#### 在服务提供者中注册

```php
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册异常处理器为单例
        $this->container->singleton(Handler::class, function () {
            return new Handler(
                $this->container->make(LoggerInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // 注册错误和异常处理器
        $handler = $this->container->make(Handler::class);
        set_error_handler([$handler, 'handleError']);
        set_exception_handler([$handler, 'handleException']);
    }
}
```

#### 手动注册

```php
// 创建处理器实例
$logger = app(LoggerInterface::class);
$handler = new Handler($logger);

// 注册处理器
set_error_handler([$handler, 'handleError']);
set_exception_handler([$handler, 'handleException']);
```

### 2. HTTP 异常使用

#### 抛出 HTTP 异常

```php
class UserController extends BaseController
{
    public function show(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            throw new HttpException(404, 'User not found');
        }

        return $this->view('users.show', ['user' => $user]);
    }

    public function update(Request $request, int $id)
    {
        $user = User::find($id);

        if (!$user) {
            throw new HttpException(404, 'User not found');
        }

        // 检查权限
        if (!$this->canUpdate($user)) {
            throw new HttpException(403, 'Permission denied');
        }

        // 更新逻辑
        $user->update($request->all());

        return $this->view('users.show', ['user' => $user]);
    }
}
```

#### API 接口中的使用

```php
class ApiController extends BaseController
{
    public function delete(int $id)
    {
        try {
            $user = User::findOrFail($id);

            // 检查权限
            $this->authorize('delete', $user);

            $user->delete();

            return JsonResponse::success(null, 'User deleted successfully');

        } catch (HttpException $e) {
            // HTTP 异常会被自动处理
            throw $e;
        } catch (\Exception $e) {
            // 其他异常转换为 HTTP 异常
            throw new HttpException(500, 'Internal server error');
        }
    }
}
```

### 3. 自定义异常

#### 创建业务异常

```php
class UserNotFoundException extends BaseException
{
    public function __construct(int $userId)
    {
        parent::__construct("User with ID {$userId} not found");
    }
}

class InsufficientPermissionException extends BaseException
{
    public function __construct(string $permission)
    {
        parent::__construct("Insufficient permission: {$permission}");
    }
}

// 使用自定义异常
class UserService
{
    public function getUser(int $id): User
    {
        $user = User::find($id);

        if (!$user) {
            throw new UserNotFoundException($id);
        }

        return $user;
    }
}
```

#### 集成 HTTP 异常

```php
class UserNotFoundException extends HttpException
{
    public function __construct(int $userId)
    {
        parent::__construct(404, "User with ID {$userId} not found");
    }
}

class InsufficientPermissionException extends HttpException
{
    public function __construct(string $permission)
    {
        parent::__construct(403, "Insufficient permission: {$permission}");
    }
}
```

### 4. 异常处理中间件

#### 创建异常处理中间件

```php
class ExceptionHandlingMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (HttpException $e) {
            // HTTP 异常直接传递给处理器
            throw $e;
        } catch (\Exception $e) {
            // 其他异常转换为 HTTP 异常
            if (env('APP_DEBUG')) {
                throw $e;
            } else {
                throw new HttpException(500, 'Internal server error');
            }
        }
    }
}
```

## 响应格式

### 1. HTTP 异常响应

#### 开发环境响应

```php
// HTTP 异常会设置状态码和响应头
http_response_code($e->statusCode);
foreach ($e->headers as $name => $value) {
    header($name . ': ' . $value);
}

// 返回 JSON 格式错误信息
JsonResponse::error($e->getMessage(), $e->statusCode)->send();
```

#### 生产环境响应

```php
// 简洁的生产环境错误页面
private static function renderProdError(): void
{
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '    <meta charset="UTF-8">';
    echo '    <title>Error</title>';
    echo '    <style>';
    echo '        body { text-align: center; padding: 150px; }';
    echo '        h1 { font-size: 48px; color: #555; }';
    echo '    </style>';
    echo '</head>';
    echo '<body>';
    echo '    <h1>Server Error</h1>';
    echo '    <p>We are sorry, but something went wrong.</p>';
    echo '</body>';
    echo '</html>';
}
```

### 2. 开发环境详细错误页面

```php
private static function renderDevError(Throwable $e, int $code): void
{
    // 包含详细错误信息的 HTML 页面
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '    <title>Framework Error</title>';
    echo '    <style>';
    echo '        .container { max-width: 800px; margin: 0 auto; background: #fff; }';
    echo '        .stack-trace { background: #eee; padding: 15px; }';
    echo '    </style>';
    echo '</head>';
    echo '<body>';
    echo '    <div class="container">';
    echo '        <h1>Oops! Something went wrong.</h1>';
    echo '        <p><strong>Type:</strong> ' . get_class($e) . '</p>';
    echo '        <p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '        <p><strong>File:</strong> ' . $e->getFile() . '</p>';
    echo '        <p><strong>Line:</strong> ' . $e->getLine() . '</p>';
    echo '        <div class="stack-trace">' . nl2br(htmlspecialchars($e->getTraceAsString())) . '</div>';
    echo '    </div>';
    echo '</body>';
    echo '</html>';
}
```

### 3. JSON 响应

```php
private static function renderJsonError(Throwable $e, int $code): void
{
    JsonResponse::error(
        message: $e->getMessage(),
        code: $code
    )->send();
}

// 使用示例
$exception = new HttpException(404, 'Not Found');
self::renderJsonError($exception, 404);

// 输出：{"message":"Not Found","code":404}
```

### 4. 验证异常响应

```php
if ($e instanceof ValidationException) {
    http_response_code(422);
    JsonResponse::error('Validation Failed', 422, $e->errors())->send();
    return;
}

// 验证异常返回格式
{
    "message": "Validation Failed",
    "code": 422,
    "data": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

## 配置和集成

### 1. 环境配置

#### 错误报告级别

```php
// .env 文件
APP_DEBUG=true
APP_LOG_LEVEL=debug
ERROR_REPORTING=E_ALL
```

#### 调试模式控制

```php
// 在 index.php 或 bootstrap 中
define('DEBUG', env('APP_DEBUG', false));

// Handler 中使用调试模式
if (defined('DEBUG') && DEBUG) {
    self::renderDevError($e, 500);
} else {
    self::renderProdError();
}
```

### 2. 日志配置

#### 日志记录策略

```php
protected function shouldntReport(Throwable $e): bool
{
    // 这些异常不记录到日志（避免日志噪音）
    return $e instanceof HttpException || $e instanceof ValidationException;
}

public function handleException(Throwable $e): void
{
    if (!$this->shouldntReport($e)) {
        $this->logger->error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
```

#### 日志格式

```php
// 典见的日志格式示例
[2024-01-15 14:30:25] production.ERROR: User not found
{
    "exception": "App\\Exceptions\\UserNotFoundException",
    "file": "/app/Services/UserService.php",
    "line": 25,
    "trace": "#0 /app/Services/UserService.php(25): ..."
}
```

### 3. 响应格式配置

#### API 响应格式

```php
// 在 API 控制器中
class ApiController extends BaseController
{
    public function handleException(\Exception $e)
    {
        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->statusCode,
                    'message' => $e->getMessage(),
                ]
            ], $e->statusCode);
        }

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 500,
                'message' => 'Internal server error'
            ]
        ], 500);
    }
}
```

## 最佳实践

### 1. 异常设计原则

#### 使用适当的异常类型

```php
// ✅ 推荐：使用 HTTP 异常表示 HTTP 层面的错误
throw new HttpException(404, 'Resource not found');

// ✅ 推荐：使用业务异常表示业务规则错误
throw new UserNotFoundException($userId);

// ✅ 推荐：使用验证异常表示输入验证错误
throw new ValidationException('Invalid input data');

// ❌ 避免：使用通用异常处理所有错误
throw new \Exception('Something went wrong');
```

#### 提供有意义的错误信息

```php
// ✅ 推荐：具体的错误信息
throw new HttpException(404, "User with ID {$userId} not found");

// ✅ 推荐：包含上下文信息
throw new InsufficientPermissionException(
    "User {$user->id} cannot perform action: {$action}"
);

// ❌ 避免：模糊的错误信息
throw new HttpException(400, 'Bad request');
throw new BaseException('Error occurred');
```

### 2. 异常链的使用

#### 保持异常链完整

```php
try {
    // 数据库操作
    $this->database->beginTransaction();
    $this->processData($data);
    $this->database->commit();
} catch (\PDOException $e) {
    $this->database->rollBack();

    // 保持原始异常信息
    throw new HttpException(
        500,
        'Database operation failed',
        [],
        $e  // 保留原始异常
    );
}
```

#### 异常信息层次

```php
try {
    // 外部操作
    $this->externalServiceCall();
} catch (\Exception $e) {
    // 包装为更具体的异常
    throw new ServiceException(
        'External service call failed',
        500,
        null,
        $e  // 保留原始异常用于调试
    );
}
```

### 3. 性能考虑

#### 避免异常滥用

```php
// ✅ 推荐：使用返回值表示可预期的错误
public function getUser(int $id): ?User
{
    return User::find($id);
}

// ❌ 避免：对可预期情况使用异常
public function getUser(int $id): User
{
    $user = User::find($id);
    if (!$user) {
        throw new HttpException(404, 'User not found');
    }
    return $user;
}
```

#### 异常处理性能

```php
// ✅ 推荐：避免在异常处理中进行重量级操作
class ExceptionHandler
{
    public function handleException(Throwable $e): void
    {
        // 只记录必要信息
        $this->logger->error($e->getMessage());

        // 快速返回，避免在异常处理中进行重量级操作
        $this->renderErrorResponse($e);
    }
}
```

### 4. 安全考虑

#### 避免信息泄露

```php
// ✅ 推荐：生产环境中隐藏敏感信息
private static function renderProdError(): void
{
    // 不显示详细的错误信息
    echo 'Server Error';

    // 记录到日志文件供调试
    error_log($e->getMessage());
}

// ❌ 避免：在生产环境中显示敏感信息
private static function renderDevError(Throwable $e): void
{
    echo 'Database password: ' . $dbPassword; // 危险！
    echo 'API key: ' . $apiKey; // 危险！
}
```

#### 输入验证

```php
// ✅ 推荐：在异常处理前验证输入
public function handleException(Throwable $e): void
{
    // 验证异常消息，防止 XSS
    $message = $this->sanitizeMessage($e->getMessage());

    // 验证文件路径，防止路径遍历
    $file = $this->validateFilePath($e->getFile());

    $this->renderError([
        'message' => $message,
        'file' => $file,
        'line' => $e->getLine()
    ]);
}
```

## 扩展和自定义

### 1. 自定义异常处理器

```php
class CustomExceptionHandler extends Handler
{
    private array $customHandlers = [];

    public function addCustomHandler(string $exceptionClass, callable $handler): void
    {
        $this->customHandlers[$exceptionClass] = $handler;
    }

    public function handleException(Throwable $e): void
    {
        // 检查是否有自定义处理器
        $exceptionClass = get_class($e);

        if (isset($this->customHandlers[$exceptionClass])) {
            $handler = $this->customHandlers[$exceptionClass];
            $handler($e);
            return;
        }

        // 调用父类处理
        parent::handleException($e);
    }
}
```

### 2. 异常监听器

```php
class ExceptionListener
{
    private LoggerInterface $logger;
    private array $listeners = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function addListener(callable $listener): void
    {
        $this->listeners[] = $listener;
    }

    public function listen(Throwable $e): void
    {
        foreach ($this->listeners as $listener) {
            try {
                $listener($e);
            } catch (\Exception $listenerException) {
                $this->logger->error('Exception listener failed', [
                    'listener_error' => $listenerException->getMessage(),
                    'original_error' => $e->getMessage()
                ]);
            }
        }
    }
}
```

### 3. 异常报告

```php
class ExceptionReporter
{
    private LoggerInterface $logger;
    private array $channels = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function report(Throwable $e): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'context' => $this->getContext()
        ];

        // 发送到多个渠道
        $this->sendToChannels($report);
    }

    private function sendToChannels(array $report): void
    {
        foreach ($this->channels as $channel) {
            $channel->send($report);
        }
    }
}
```

这个异常系统为 JnmPHP 框架提供了统一而强大的异常处理能力，支持多种异常类型和响应格式，是框架稳定性和可维护性的重要保障。通过合理的异常处理，可以提供更好的用户体验和调试能力。