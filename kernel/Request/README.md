# 请求处理器

Request 命名空间为 JnmPHP 框架提供了 HTTP 请求处理功能。它提供了提取、处理传入 HTTP 请求数据的基础工具，支持多种输入格式，包括 JSON、表单数据、URL 参数等。

## 概述

请求处理系统旨在为开发人员提供轻量级而高效的工具来处理传入的 HTTP 请求。它采用惰性加载策略，仅在需要时解析 JSON 数据，并提供对请求数据的统一访问接口。

## 核心功能

- **多格式支持**：处理 JSON、表单数据和 URL 参数
- **惰性 JSON 解析**：仅在访问时解析 JSON 请求体，提高性能
- **请求头访问**：提供 HTTP 头部的便捷访问方法
- **数据优先级**：JSON > POST > GET 的数据获取优先级
- **健壮性设计**：使用只读属性和默认值增强稳定性
- **跨平台兼容**：支持不同服务器的请求头获取方式

## 核心类

### Request.php

主要的 Request 类提供了基础的 HTTP 请求数据访问接口。

#### 属性

所有属性都是只读的（readonly），确保请求数据在创建后不会被意外修改：

- `$uri` (string): 请求 URI
- `$method` (string): HTTP 请求方法
- `$headers` (array): 所有请求头（键名转为小写）
- `$get` (array): GET 参数
- `$post` (array): POST 数据
- `$json` (array|null): JSON 数据（私有属性，惰性加载）

#### 主要方法

**静态创建方法：**
- `capture(): static` - 创建包含当前 HTTP 请求信息的实例

**输入数据访问：**
- `input(string $key, mixed $default = null): mixed` - 获取输入值（优先级：JSON > POST > GET）

**JSON 处理：**
- `json(): array` - 获取请求体中的 JSON 数据（惰性加载）

**头部访问：**
- `header(string $key, mixed $default = null): mixed` - 获取单个请求头值

**私有方法：**
- `getAllHeaders(): array` - 获取所有请求头（兼容不同服务器环境）
- `parseJsonBody(): array` - 实际执行 JSON 解析的私有方法

## 使用示例

### 基本输入访问

```php
// 创建请求实例
$request = Request::capture();

// 获取输入值（优先级：JSON > POST > GET）
$name = $request->input('name');

// 获取带默认值的输入
$email = $request->input('email', 'default@example.com');

// 直接访问 GET、POST 参数
$userId = $request->get['user_id'] ?? null;
$postData = $request->post;
```

### JSON 请求处理

```php
// 获取 JSON 数据（惰性加载，仅在首次调用时解析）
$jsonData = $request->json();

// 获取特定 JSON 字段
$username = $request->json()['username'] ?? null;

// 使用 input 方法自动获取 JSON 数据
$username = $request->input('username'); // 会自动从 JSON、POST、GET 中查找
```

### 头部访问

```php
// 获取特定头部（不区分大小写）
$contentType = $request->header('Content-Type');
$userAgent = $request->header('User-Agent');

// 获取带默认值的头部
$authHeader = $request->header('Authorization', '');

// 访问所有头部
$allHeaders = $request->headers;
```

### 请求方法和 URI 信息

```php
// 获取 HTTP 方法
$method = $request->method; // GET, POST, PUT, 等

// 检查请求方法
if ($request->method === 'POST') {
    // 处理 POST 请求
}

// 获取 URI
$uri = $request->uri;
```

### 完整请求处理示例

```php
class UserController extends BaseController
{
    #[Post('/api/users')]
    public function createUser(): JsonResponse
    {
        $request = Request::capture();

        // 检查内容类型
        $contentType = $request->header('Content-Type');

        // 获取用户数据
        $userData = $request->json(); // 对于 JSON API
        // 或者
        $userData = $request->input('user'); // 自动从 JSON/POST/GET 获取

        // 处理业务逻辑
        $user = User::create($userData);

        return JsonResponse::success($user);
    }

    #[Get('/api/users/{id}')]
    public function getUser($id): JsonResponse
    {
        $request = Request::capture();

        // 获取查询参数
        $fields = $request->input('fields', '*');
        $include = $request->input('include', '');

        // 处理业务逻辑
        $user = User::select($fields)->find($id);

        if (!$user) {
            return JsonResponse::error('用户不存在', 404);
        }

        return JsonResponse::success($user);
    }
}
```

## 技术特性

### 惰性 JSON 解析

Request 类采用惰性加载策略，仅在首次调用 `json()` 方法时才解析 JSON 数据：

```php
// 此时 JSON 数据尚未解析
$request = Request::capture();

// 首次调用时才进行 JSON 解析
$jsonData = $request->json();

// 后续调用直接返回已解析的数据
$cachedData = $request->json();
```

### 数据优先级机制

`input()` 方法按照以下优先级获取数据：
1. **JSON 数据**（最高优先级）
2. **POST 数据**
3. **GET 数据**（最低优先级）

```php
// 如果请求中同时包含以下数据：
// GET: ?name=John
// POST: name=Jane
// JSON: {"name": "Bob"}

$result = $request->input('name'); // 返回 "Bob"（来自 JSON）
```

### 跨平台兼容性

请求头获取方法兼容不同的服务器环境：

```php
private function getAllHeaders(): array
{
    // 优先使用内置函数
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if ($headers !== false) {
            return array_change_key_case($headers);
        }
    }

    // 备用方案：手动解析 $_SERVER
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (str_starts_with($name, 'HTTP_')) {
            $headerName = str_replace('_', '-', strtolower(substr($name, 5)));
            $headers[$headerName] = $value;
        }
    }
    return array_change_key_case($headers);
}
```

### 错误处理

JSON 解析失败时会抛出异常：

```php
try {
    $jsonData = $request->json();
} catch (\RuntimeException $e) {
    // JSON 格式错误处理
    error_log('JSON 解析错误: ' . $e->getMessage());
    $jsonData = [];
}
```

## 安全考虑

- **输入验证**：始终验证用户输入的有效性
- **JSON 解析安全**：仅解析 Content-Type 为 application/json 的请求
- **头部安全性**：请求头键名统一转换为小写防止大小写混淆
- **异常处理**：JSON 解析错误时抛出异常，避免处理损坏的数据

## 性能优化

- **惰性加载**：JSON 数据仅在需要时解析，避免不必要的性能开销
- **只读属性**：使用 readonly 属性确保数据不被意外修改
- **内存效率**：直接使用 PHP 超全局变量，避免数据复制
- **缓存机制**：解析后的 JSON 数据被缓存，避免重复解析

## 最佳实践

1. **使用静态方法创建**：通过 `Request::capture()` 创建实例
2. **优先使用 input()**：利用数据优先级机制自动获取数据
3. **错误处理**：为 JSON 解析添加适当的异常处理
4. **类型安全**：使用类型声明提高代码安全性
5. **性能考虑**：避免在不需要时调用 `json()` 方法

## 使用限制

当前版本的 Request 类具有以下限制：

- 不支持文件上传处理
- 没有内置的输入验证功能
- 不提供 URL 参数的单独解析
- 没有客户端 IP 和 User Agent 的便捷方法
- 不支持 Cookie 访问

如需这些功能，需要扩展 Request 类或使用其他组件。

## 故障排除

常见问题和解决方案：

- **JSON 数据为空**：确保请求具有正确的 `Content-Type: application/json` 头部
- **请求头获取失败**：检查服务器环境，某些环境下 `getallheaders()` 可能不可用
- **数据优先级问题**：理解 JSON > POST > GET 的优先级顺序
- **编码问题**：JSON 解析自动处理 UTF-8 编码
- **属性访问错误**：所有属性都是只读的，尝试修改会抛出错误