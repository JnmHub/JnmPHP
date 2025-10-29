# 响应处理器

Response 命名空间为 JnmPHP 框架提供了全面的 HTTP 响应处理功能。它包含了多种响应类型，支持 JSON API、视图渲染、文件下载等常见的 Web 应用响应需求，采用统一的接口设计便于扩展和使用。

## 概述

响应处理系统旨在为开发人员提供简单而强大的工具来生成各种类型的 HTTP 响应。通过实现统一的 ResponseInterface 接口，所有响应类型都具备一致的调用方式，同时支持框架的依赖注入和服务容器特性。

## 核心功能

- **统一接口设计**：所有响应类都实现 ResponseInterface 接口
- **JSON API 响应**：提供标准化的 JSON API 响应格式
- **视图渲染**：集成 Blade 模板引擎进行视图渲染
- **文件下载**：支持安全的文件下载功能
- **HTTP 头部管理**：自动设置正确的 Content-Type 和其他必要头部
- **错误处理**：内置异常处理机制
- **编码支持**：正确处理中文字符和文件名

## 核心组件

### ResponseInterface.php

响应接口定义了所有响应类型必须实现的基本方法。

```php
interface ResponseInterface
{
    /**
     * 将响应发送到客户端
     */
    public function send(): void;
}
```

### JsonResponse.php

JSON 响应类，专门用于处理 API 接口的 JSON 格式响应。

#### 属性

- `code` (int): HTTP 状态码，默认 200
- `message` (string): 响应消息，默认 'success'
- `data` (mixed): 响应数据，默认 null

#### 主要方法

**构造方法：**
```php
public function __construct(int $code = 200, string $message = 'success', mixed $data = null)
```

**核心方法：**
- `send(): void` - 输出 JSON 响应到客户端
- `success(mixed $data = null): static` - 静态方法创建成功响应
- `error(string $message, int $code = 400, mixed $data = null): static` - 静态方法创建错误响应

#### 特性

- 自动设置 `Content-Type: application/json; charset=utf-8` 头部
- 使用 `JSON_UNESCAPED_UNICODE` 支持中文字符
- 使用 `JSON_PRETTY_PRINT` 提供格式化的 JSON 输出
- 检查头部是否已发送，防止重复设置

### ViewResponse.php

视图响应类，用于渲染 Blade 模板并返回 HTML 内容。

#### 属性

- `template` (string): 模板路径
- `data` (array): 传递给模板的数据

#### 主要方法

**构造方法：**
```php
public function __construct($template, $data = [])
```

**核心方法：**
- `render(): string` - 使用 Blade 引擎渲染视图
- `send(): void` - 输出渲染后的 HTML 内容

#### 特性

- 集成 Laravel Blade 模板引擎
- 从服务容器获取视图工厂实例
- 自动转换模板路径格式（`index/index` → `index.index`）
- 支持模板数据传递

### FileResponse.php

文件响应类，用于处理文件下载功能。

#### 属性

- `filePath` (string): 服务器上的文件绝对路径
- `downloadName` (string|null): 客户端下载时显示的文件名

#### 主要方法

**构造方法：**
```php
public function __construct(string $filePath, ?string $downloadName = null)
```

**核心方法：**
- `send(): void` - 输出文件到客户端进行下载

#### 特性

- 文件存在性验证，自动抛出 404 异常
- 设置正确的下载头部信息
- 支持中文文件名的正确编码
- 内存高效的文件传输（使用 readfile）
- 清理输出缓冲区防止干扰

## 使用示例

### JSON 响应

```php
// 基本用法
$response = new JsonResponse(200, '操作成功', ['user_id' => 123]);
$response->send();

// 快速成功响应
JsonResponse::success(['user' => $userData])->send();

// 快速错误响应
JsonResponse::error('用户不存在', 404)->send();

// 带数据的错误响应
JsonResponse::error('验证失败', 422, ['errors' => $validationErrors])->send();
```

### 视图响应

```php
// 基本视图渲染
$view = new ViewResponse('user.profile', ['user' => $user]);
$view->send();

// 在控制器中使用
public function showProfile($id): ViewResponse
{
    $user = User::find($id);
    return new ViewResponse('user.profile', ['user' => $user]);
}
```

### 文件下载

```php
// 基本文件下载
$file = new FileResponse('/path/to/file.pdf');
$file->send();

// 自定义下载文件名
$file = new FileResponse('/path/to/document.pdf', '用户手册.pdf');
$file->send();

// 在控制器中使用
public function downloadReport($filename): FileResponse
{
    $filePath = storage_path("reports/{$filename}");
    return new FileResponse($filePath);
}
```

## 与其他组件的集成

### 控制器集成

```php
class UserController extends BaseController
{
    #[Get('/users/{id}')]
    public function show($id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return JsonResponse::error('用户不存在', 404);
        }

        return JsonResponse::success($user);
    }

    #[Get('/users/{id}/profile')]
    public function profile($id): ViewResponse
    {
        $user = User::find($id);
        return new ViewResponse('user.profile', ['user' => $user]);
    }

    #[Get('/users/{id}/avatar')]
    public function downloadAvatar($id): FileResponse
    {
        $user = User::find($id);
        $avatarPath = storage_path("avatars/{$user->avatar}");
        return new FileResponse($avatarPath);
    }
}
```

### 中间件集成

响应可以由中间件包装或修改：

```php
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        // 如果是 JSON 响应，添加 CORS 头部
        if ($response instanceof JsonResponse) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        }

        return $response;
    }
}
```

## HTTP 状态码

框架常用的 HTTP 状态码：

- **200 OK**: 请求成功
- **201 Created**: 资源创建成功
- **400 Bad Request**: 请求参数错误
- **401 Unauthorized**: 未授权
- **403 Forbidden**: 禁止访问
- **404 Not Found**: 资源不存在
- **422 Unprocessable Entity**: 验证失败
- **500 Internal Server Error**: 服务器内部错误

## 安全考虑

### JSON 响应安全

- 敏感数据过滤：确保不暴露密码等敏感信息
- XSS 防护：对用户输入进行适当编码
- CSRF 保护：对状态更改操作使用 CSRF 令牌

### 文件下载安全

- 路径验证：确保文件路径在允许的目录内
- 文件类型检查：限制可下载的文件类型
- 权限控制：实现用户权限验证
- 文件大小限制：防止大文件下载造成服务器压力

## 性能优化

### JSON 响应优化

- 数据精简：只返回必要的数据字段
- 数据压缩：启用 gzip 压缩减少传输大小
- 缓存策略：对不经常变化的数据实施缓存

### 视图渲染优化

- 模板缓存：启用 Blade 模板缓存
- 数据预加载：使用 Eloquent 预加载减少数据库查询
- 视图片段缓存：对重复使用的视图片段进行缓存

### 文件下载优化

- 流式传输：大文件使用流式传输减少内存占用
- 断点续传：支持 HTTP Range 请求实现断点续传
- CDN 分发：静态文件使用 CDN 分发

## 最佳实践

1. **统一响应格式**：在整个应用中保持一致的响应格式
2. **正确状态码**：使用符合 HTTP 标准的状态码
3. **错误处理**：为所有可能的错误情况提供适当的响应
4. **数据验证**：在响应前验证数据的完整性和正确性
5. **性能监控**：监控响应时间和资源使用情况

## 扩展指南

### 创建自定义响应类型

```php
class XmlResponse implements ResponseInterface
{
    private array $data;
    private int $statusCode;

    public function __construct(array $data, int $statusCode = 200)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            http_response_code($this->statusCode);
        }

        $xml = new SimpleXMLElement('<response/>');
        array_walk_recursive($this->data, function ($value, $key) use ($xml) {
            $xml->addChild($key, $value);
        });

        echo $xml->asXML();
    }
}
```

## 故障排除

常见问题和解决方案：

- **JSON 中文字符显示异常**：确保使用 `JSON_UNESCAPED_UNICODE` 标志
- **文件下载失败**：检查文件路径和权限设置
- **视图渲染错误**：验证模板路径和数据格式
- **响应头部重复设置**：使用 `headers_sent()` 检查头部状态
- **内存不足**：大文件下载时使用流式传输

## 配置说明

响应行为可以通过以下方式配置：

- JSON 编码选项在 JsonResponse 类中设置
- 视图路径在配置文件中定义
- 文件下载大小限制在 php.ini 中设置
- 响应压缩通过 Web 服务器配置