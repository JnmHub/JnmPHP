# 路由系统

Routing 命名空间为 JnmPHP 框架提供了强大而灵活的路由功能。它采用基于 PHP 8+ Attributes 的路由定义方式，支持参数注入、中间件、请求验证等现代化功能，无需额外的路由配置文件。

## 概述

路由系统是 JnmPHP 框架的核心组件之一，负责将传入的 HTTP 请求映射到相应的控制器方法。通过使用 PHP Attributes，路由定义与控制器代码紧密结合，提供了更好的代码可维护性和类型安全性。

## 核心功能

- **Attribute 驱动**：使用 PHP 8+ Attributes 定义路由，无需配置文件
- **自动路由收集**：扫描控制器目录自动收集路由定义
- **路由缓存**：生产环境支持路由缓存以提高性能
- **参数自动注入**：支持路径变量、请求体、请求对象的自动注入
- **中间件支持**：全局、类级别、方法级别的中间件支持
- **请求验证**：集成验证系统，支持自动请求验证
- **类型转换**：自动类型转换，支持 int、float、bool 等基本类型
- **路由冲突检测**：自动检测并报告重复路由定义
- **智能响应处理**：自动处理不同类型的响应输出

## 核心组件

### Router.php

路由调度器，负责处理 HTTP 请求并分发到相应的控制器方法。

#### 主要方法

**构造方法：**
```php
public function __construct(array $routes)
```

**核心方法：**
- `dispatch(string $uri, string $method, $request): void` - 路由分发的主要方法

#### 核心功能

**路由匹配：**
- 支持 HTTP 方法匹配
- 支持路径参数匹配（正则表达式）
- URI 标准化处理（清理多余斜杠、移除查询参数）

**参数解析：**
- 提取命名捕获组作为路由参数
- 自动 URL 解码处理
- 支持默认值参数

**依赖注入：**
- `#[PathVariable]` - 路径参数注入
- `#[RequestBody]` - 请求体数据注入和验证
- `Request` - 请求对象自动注入

**中间件管道：**
- 组合全局中间件和路由中间件
- 使用 Pipeline 模式执行中间件链

**响应处理：**
- 自动识别 `ResponseInterface` 实现类
- 智能包装普通返回值为 JSON 响应

### RouteCollector.php

路由收集器，负责扫描控制器并收集路由定义。

#### 主要方法

**静态方法：**
- `run(): array` - 运行路由收集过程
- `collectRoutes(): array` - 扫描控制器并收集路由

#### 核心功能

**自动扫描：**
- 递归扫描 `app/Controller` 目录
- 支持多级子目录结构
- 自动转换为类命名空间

**Attribute 解析：**
- `#[RoutePrefix]` - 控制器级别路由前缀
- `#[Route]` - 方法定义的路由
- `#[Middleware]` - 中间件定义

**路由缓存：**
- 生产环境自动使用缓存
- 开发环境实时扫描控制器
- 自动生成缓存文件

**冲突检测：**
- 检测重复的路由定义
- 提供详细的冲突信息
- 防止路由覆盖问题

## 使用示例

### 基本路由定义

```php
#[RoutePrefix('/api/users')]
class UserController extends BaseController
{
    #[Get('')]
    public function index(): JsonResponse
    {
        $users = User::all();
        return JsonResponse::success($users);
    }

    #[Get('/{id}')]
    public function show(#[PathVariable('id')] int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return JsonResponse::error('用户不存在', 404);
        }
        return JsonResponse::success($user);
    }
}
```

### 请求体验证

```php
#[RoutePrefix('/api/users')]
class UserController extends BaseController
{
    #[Post('')]
    public function store(
        #[RequestBody] UserCreateRequest $request
    ): JsonResponse {
        // $request 已经过验证和填充
        $user = User::create($request->toArray());
        return JsonResponse::success($user, 201);
    }

    #[Put('/{id}')]
    public function update(
        #[PathVariable('id')] int $id,
        #[RequestBody] UserUpdateRequest $request
    ): JsonResponse {
        $user = User::find($id);
        $user->update($request->toArray());
        return JsonResponse::success($user);
    }
}
```

### 中间件使用

```php
#[RoutePrefix('/api/admin')]
#[Middleware(['auth', 'admin'])]  // 类级别中间件
class AdminController extends BaseController
{
    #[Get('/dashboard')]
    #[Middleware(['permission:view_dashboard'])]  // 方法级别中间件
    public function dashboard(): JsonResponse
    {
        return JsonResponse::success([
            'users_count' => User::count(),
            'orders_count' => Order::count()
        ]);
    }

    #[Post('/users')]
    #[Middleware(['permission:create_user'])]
    public function createUser(
        #[RequestBody] UserCreateRequest $request
    ): JsonResponse {
        $user = User::create($request->toArray());
        return JsonResponse::success($user, 201);
    }
}
```

### 复杂参数处理

```php
#[RoutePrefix('/api/reports')]
class ReportController extends BaseController
{
    #[Get('/{type}/download')]
    public function downloadReport(
        #[PathVariable('type')] string $type,
        Request $request,
        #[PathVariable('missingParamMessage' => '必须指定报告类型')] string $format = 'pdf'
    ): FileResponse {
        // 获取查询参数
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // 处理业务逻辑
        $filePath = $this->generateReport($type, $format, $startDate, $endDate);

        return new FileResponse($filePath, "report-{$type}." . $format);
    }
}
```

## 请求处理流程

### 1. 路由收集阶段

```php
// RouteCollector::run() 执行流程
1. 检查是否存在路由缓存文件
2. 如果存在缓存且在生产环境 → 直接返回缓存
3. 否则扫描控制器目录
4. 解析每个控制器类的 Attributes
5. 合并路由前缀和方法路由
6. 解析中间件定义
7. 检测路由冲突
8. 生成路由缓存文件
9. 返回路由数组
```

### 2. 请求分发阶段

```php
// Router::dispatch() 执行流程
1. 标准化 URI（清理斜杠、移除查询参数）
2. 触发 router.before_dispatch 事件
3. 遍历路由数组寻找匹配项
4. 匹配 HTTP 方法和路径模式
5. 提取路径参数
6. 实例化控制器
7. 解析方法参数和依赖注入
8. 执行验证（如果存在 RequestBody）
9. 构建中间件管道
10. 执行中间件链和控制器方法
11. 处理响应输出
12. 触发 controller.after_execute 事件
```

## 中间件系统

### 中间件类型

1. **全局中间件**：在 `MiddlewareManager` 中注册，对所有请求生效
2. **类级别中间件**：在控制器类上定义，对该类所有方法生效
3. **方法级别中间件**：在控制器方法上定义，仅对该方法生效

### 中间件执行顺序

```php
// 中间件执行顺序（从外到内）
全局中间件 → 类级别中间件 → 方法级别中间件 → 控制器方法

// 响应返回顺序（从内到外）
控制器方法 → 方法级别中间件 → 类级别中间件 → 全局中间件
```

### 中间件定义

```php
// 中间件类实现
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $request->header('Authorization');

        if (!$this->validateToken($token)) {
            return JsonResponse::error('未授权', 401);
        }

        return $next($request);
    }
}

// 在控制器中使用
#[Middleware(['auth'])]
class UserController extends BaseController
{
    // 方法实现
}
```

## 参数注入详解

### PathVariable 注入

```php
#[Get('/users/{id}/posts/{postId}')]
public function getUserPost(
    #[PathVariable('id')] int $userId,
    #[PathVariable('postId')] int $postId,
    string $format = 'json'  // 默认参数
): JsonResponse {
    // $userId 和 $postId 自动从 URL 路径中提取并类型转换
}
```

### RequestBody 注入

```php
#[Post('/users')]
public function createUser(
    #[RequestBody] UserCreateRequest $request
): JsonResponse {
    // 自动从 JSON 请求体中提取数据
    // 根据 UserCreateRequest 中的验证规则进行验证
    // 验证通过后填充到 $request 对象中
}
```

### Request 对象注入

```php
#[Post('/upload')]
public function uploadFile(Request $request): JsonResponse
{
    // 直接注入 Request 对象
    $file = $request->input('file');
    $description = $request->input('description');

    return JsonResponse::success(['message' => '上传成功']);
}
```

## 性能优化

### 路由缓存

```php
// 生产环境自动启用缓存
if (!DEBUG && file_exists(APP_ROOT . '/cache/routes.php')) {
    return require APP_ROOT . '/cache/routes.php';
}

// 缓存文件格式示例
<?php return [
    [
        'path' => '/api/users',
        'preg_path' => '#^/api/users$#',
        'methods' => ['GET'],
        'controller' => 'App\\Controller\\UserController',
        'action' => 'index',
        'middlewares' => ['auth']
    ],
    // ...
];
```

### 正则表达式预编译

```php
// 路由定义
#[Get('/users/{id}/posts/{postId}')]

// 自动转换为预编译正则
$preg_path = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]*)', '/users/{id}/posts/{postId}');
// 结果：#^/users/(?P<id>[^/]*)/posts/(?P<postId>[^/]*)$#
```

## 事件系统集成

### 路由相关事件

```php
// 路由分发前事件
EventManager::getInstance()->dispatch('router.before_dispatch', $uri, $method);

// 控制器执行前事件
EventManager::getInstance()->dispatch('controller.before_execute', $controller, $action, $args);

// 控制器执行后事件
EventManager::getInstance()->dispatch('controller.after_execute', $response);
```

### 事件监听器示例

```php
class RouteLoggingListener implements EventSubscriberInterface
{
    public function subscribe(): array
    {
        return [
            'router.before_dispatch' => 'logRouteAccess',
            'controller.after_execute' => 'logResponse',
        ];
    }

    public function logRouteAccess(string $uri, string $method): void
    {
        Log::info("路由访问: {$method} {$uri}");
    }

    public function logResponse($response): void
    {
        Log::info("响应生成: " . get_class($response));
    }
}
```

## 错误处理

### 常见错误类型

1. **404 Not Found**：路由未匹配
2. **400 Bad Request**：缺少必需参数
3. **405 Method Not Allowed**：HTTP 方法不匹配
4. **422 Unprocessable Entity**：请求验证失败
5. **500 Internal Server Error**：服务器内部错误

### 错误响应格式

```php
// 路由未匹配
throw new HttpException(404, "404 Not Found");

// 缺少参数
throw new HttpException(400, "缺少参数：id");

// 验证失败（通过验证系统自动处理）
// 自动返回 422 状态码和详细错误信息
```

## 最佳实践

### 1. 路由设计原则

- 使用 RESTful 风格的 URL 设计
- 保持 URL 简洁且语义化
- 合理使用路由前缀组织功能模块
- 避免过于复杂的路由参数

### 2. 控制器组织

```php
// 好的实践
#[RoutePrefix('/api/v1/users')]
class UserController extends BaseController
{
    #[Get('')]                    // GET /api/v1/users
    public function index(): JsonResponse { }

    #[Post('')]                   // POST /api/v1/users
    public function store(): JsonResponse { }

    #[Get('/{id}')]              // GET /api/v1/users/123
    public function show(int $id): JsonResponse { }

    #[Put('/{id}')]              // PUT /api/v1/users/123
    public function update(int $id): JsonResponse { }

    #[Delete('/{id}')]           // DELETE /api/v1/users/123
    public function destroy(int $id): JsonResponse { }
}
```

### 3. 参数验证

```php
// 在专门的请求类中定义验证规则
class UserCreateRequest extends BaseModel
{
    #[TableField('user_name')]
    #[Validate(['required', 'string', 'max:50'])]
    public string $userName;

    #[TableField('email')]
    #[Validate(['required', 'email', 'unique:users'])]
    public string $email;
}

// 在控制器中使用
#[Post('/users')]
public function store(#[RequestBody] UserCreateRequest $request): JsonResponse
{
    // $request 已经验证和填充
    $user = User::create($request->toArray());
    return JsonResponse::success($user, 201);
}
```

### 4. 中间件使用

```php
// 合理的中间件分层
#[RoutePrefix('/api/v1')]
#[Middleware(['api.version'])]           // API 版本检查
class ApiController extends BaseController
{
    #[RoutePrefix('/admin')]
    #[Middleware(['auth', 'admin'])]      // 认证 + 权限检查
    class AdminController extends BaseController
    {
        #[Get('/users')]
        #[Middleware(['permission:list_users'])]  // 细粒度权限
        public function listUsers(): JsonResponse { }
    }
}
```

## 扩展指南

### 自定义路由 Attribute

```php
#[Attribute(Attribute::TARGET_METHOD)]
class ApiResource extends Route
{
    public function __construct(string $resource)
    {
        $this->methods = ['GET', 'POST', 'PUT', 'DELETE'];
        $this->path = "/{$resource}/{id?}";
    }
}

// 使用示例
#[ApiResource('users')]
class UserController extends BaseController
{
    // 自动生成所有 RESTful 路由
}
```

### 自定义参数注入

```php
#[Attribute(Attribute::TARGET_PARAMETER)]
class CurrentUser
{
    public function __construct(public string $missingParamMessage = '用户未登录') {}
}

// 在 Router 中添加处理逻辑
// 扩展参数解析逻辑以支持自定义 Attribute
```

## 故障排除

### 常见问题

1. **路由不生效**
   - 检查控制器是否在正确的目录
   - 确认使用了正确的 Attribute
   - 清除路由缓存重新生成

2. **参数注入失败**
   - 检查参数名称是否匹配
   - 确认使用了正确的 Attribute
   - 验证参数类型是否正确

3. **中间件不执行**
   - 检查中间件类是否实现正确接口
   - 确认中间件别名配置正确
   - 验证中间件注册顺序

4. **缓存问题**
   - 开发环境设置 DEBUG=true
   - 手动删除 cache/routes.php 文件
   - 检查缓存目录权限

### 调试技巧

```php
// 启用详细错误信息
// 在开发环境显示路由匹配过程
if (DEBUG) {
    var_dump($this->routes);
    var_dump($uri, $method);
}

// 添加日志记录
Log::debug("路由匹配: {$method} {$uri}");
Log::debug("匹配结果: " . ($matched ? '成功' : '失败'));
```

## 配置说明

### 环境配置

- `DEBUG`：控制是否使用路由缓存
- `APP_ROOT`：应用程序根目录
- `cache/routes.php`：路由缓存文件路径

### 中间件配置

中间件别名和全局中间件在 `MiddlewareManager` 中配置：

```php
// config/middleware.php
return [
    'global' => [
        'cors',
        'request.logging'
    ],
    'aliases' => [
        'auth' => App\Middleware\AuthMiddleware::class,
        'admin' => App\Middleware\AdminMiddleware::class,
        'cors' => App\Middleware\CorsMiddleware::class,
    ],
];
```