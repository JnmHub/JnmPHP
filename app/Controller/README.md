# 控制器 (Controllers)

此目录包含 JnmPHP 框架的所有控制器类。控制器基于 PHP 8 Attributes 特性，实现了现代化的路由定义和参数绑定。

## 目录结构

```
app/Controller/
├── BaseController.php           # 基础控制器类
├── IndexController.php          # 主控制器
├── admin/                       # 管理后台控制器目录
│   └── IndexController.php      # 管理后台控制器
└── README.md                    # 本文档
```

## 核心组件

### BaseController 基础控制器

提供控制器的基础功能和通用方法：

- **依赖注入：** 自动注入 `LoggerInterface` 日志接口
- **视图响应：** `view()` 方法创建 Blade 模板响应
- **文件下载：** `file()` 方法创建文件下载响应

```php
class BaseController
{
    protected LoggerInterface $logger;

    protected function view(string $view, array $data = []): ViewResponse
    protected function file(string $filePath, ?string $downloadName = null): FileResponse
}
```

## 路由属性系统

JnmPHP 使用 PHP 8 Attributes 定义路由，支持现代化的路由配置：

### 基础路由属性

| 属性 | 作用 | 示例 |
|------|------|------|
| `#[RoutePrefix('/path')]` | 设置控制器路由前缀 | `#[RoutePrefix('/admin')]` |
| `#[Get('/path')]` | 定义 GET 路由 | `#[Get('/users/{id}')]` |
| `#[Post('/path')]` | 定义 POST 路由 | `#[Post('/users')]` |
| `#[Middleware('alias')]` | 应用中间件 | `#[Middleware('auth')]` |

### 参数绑定属性

| 属性 | 作用 | 示例 |
|------|------|------|
| `#[PathVariable('name')]` | URL路径参数绑定 | `#[PathVariable('id')]` |
| `#[RequestBody]` | JSON请求体绑定 | `#[RequestBody] User $user` |

## 主控制器 (IndexController)

### 路由前缀
- **前缀：** `/` (根路径)

### API 路由详解

#### 1. 页面展示路由

| 方法 | 路径 | 功能 | 中间件 |
|------|------|------|---------|
| GET | `/` | 主页展示 | CSRF |
| GET | `/index` | 索引页面 | log |

**示例代码：**
```php
#[Get('/')]
#[Middleware('CSRF')]
public function indexView()
{
    return $this->view('index.index', [
        'name' => 'JnmPHP 开发者',
        'products' => $products
    ]);
}
```

#### 2. 参数获取路由

| 方法 | 路径 | 功能 | 参数 |
|------|------|------|------|
| GET | `/info/{aid}` | 获取信息 | `aid` (路径参数) |
| GET | `/user/{id}` | 获取用户 | `id` (路径参数), `extra` (默认值) |
| GET | `/user/{uid}/order/{oid}` | 获取用户订单 | `uid`, `oid` (多路径参数) |

**示例代码：**
```php
#[Get('/user/{uid}/order/{oid}')]
public function getOrder(
    #[PathVariable('uid', '用户ID不能为空')] string $userId,
    #[PathVariable('oid', '订单ID缺失')] string $orderId
): string {
    return "用户ID：{$userId}，订单ID：{$orderId}";
}
```

#### 3. 数据操作路由

| 方法 | 路径 | 功能 | 请求体类型 |
|------|------|------|-----------|
| POST | `/submit-data` | 提交表单数据 | 表单数据 |
| POST | `/users` | 创建用户 | User 模型 |
| POST | `/products` | 创建产品 | Product 模型 |
| POST | `/department` | 创建部门 | Department DTO |
| POST | `/dto_demo` | DTO 示例 | Department DTO |

**示例代码：**
```php
#[Post('/users')]
public function createUser(#[RequestBody] User $user): User
{
    $user->save();
    return $user;  // 自动转为 JSON
}
```

#### 4. 关联数据查询路由

| 方法 | 路径 | 功能 | 模型关联 |
|------|------|------|----------|
| GET | `/{id}/posts` | 获取用户文章 | User → Posts (HasMany) |
| GET | `/posts/{id}` | 获取文章作者 | Post → User (BelongsTo) |
| GET | `/posts/{id}/tags` | 获取文章标签 | Post → Tags (BelongsToMany) |
| GET | `/postsa/tags` | 获取预加载关联 | User with('posts') |

**示例代码：**
```php
#[Get('/{id}/posts')]
public function getUserWithPosts(int $id)
{
    $user = User::getById($id);
    $posts = $user->posts;  // 触发 HasMany 关联
    return [
        'user' => $user->toArray(),
        'posts' => $posts->toArray(),
    ];
}
```

## 管理后台控制器 (admin/IndexController)

### 路由前缀
- **前缀：** `/admin`

### 功能说明

管理后台控制器提供与主控制器类似的 API，但具有以下特点：

1. **统一前缀：** 所有路由都以 `/admin` 开头
2. **独立命名空间：** `App\Controller\admin`
3. **相同功能：** 提供与主控制器相同的 API 功能
4. **权限控制：** 可配置不同的中间件进行权限管理

**路由示例：**
- `GET /admin/` - 管理后台首页
- `GET /admin/index` - 管理索引
- `GET /admin/info/{aid}` - 获取管理信息
- `POST /admin/users` - 创建管理员用户

## 控制器开发指南

### 1. 创建新控制器

```php
<?php

namespace App\Controller;

use App\Controller\BaseController;
use Kernel\Attribute\Http\Get;
use Kernel\Attribute\Http\RoutePrefix;
use Kernel\Attribute\Middleware\Middleware;

#[RoutePrefix('/api')]
class ApiController extends BaseController
{
    #[Get('/test')]
    #[Middleware('auth')]
    public function test()
    {
        return $this->view('api.test', ['message' => 'Hello API']);
    }
}
```

### 2. 路由属性使用

#### 路由前缀
```php
#[RoutePrefix('/api/v1')]  // 所有方法路由前缀为 /api/v1
class ApiController extends BaseController
```

#### HTTP 方法路由
```php
#[Get('/users')]        // GET /api/v1/users
#[Post('/users')]       // POST /api/v1/users
// 支持多个路由指向同一方法
#[Get('/products'), Get('/items')]
```

#### 中间件应用
```php
// 单个中间件
#[Middleware('auth')]

// 多个中间件
#[Middleware('auth', 'log')]

// 也可应用在类上（影响所有方法）
#[Middleware('api')]
```

### 3. 参数绑定

#### 路径参数绑定
```php
#[Get('/users/{id}')]
public function getUser(#[PathVariable('id')] int $id)
{
    // 自动将 URL 中的 {id} 转换为 int 类型
}
```

#### 请求体绑定
```php
#[Post('/users')]
public function createUser(#[RequestBody] User $user)
{
    // 自动将 JSON 请求体转换为 User 对象
    // 并进行验证（如果模型定义了验证规则）
}
```

#### DTO 对象绑定
```php
#[Post('/departments')]
public function createDepartment(#[RequestBody] Department $department)
{
    // 自动转换为 DTO 对象
    return $department;  // 自动序列化为 JSON
}
```

### 4. 响应类型

#### 视图响应
```php
return $this->view('users.index', ['users' => $users]);
// 对应视图文件：app/View/users/index.blade.php
```

#### JSON 响应
```php
// 返回数组或对象，自动转为 JSON
return ['status' => 'success', 'data' => $user];

// 返回模型对象，自动调用 toArray()
return $user;
```

#### 文件下载响应
```php
return $this->file('/path/to/file.pdf', 'download.pdf');
```

### 5. 错误处理

```php
use Kernel\Exception\HttpException;

#[Get('/users/{id}')]
public function getUser(int $id)
{
    $user = User::find($id);
    if (!$user) {
        throw new HttpException(404, '用户不存在');
    }
    return $user;
}
```

## 模型和 DTO 集成

### 模型绑定示例
控制器可以直接接收模型对象作为参数，框架会自动：

1. **验证请求：** 根据模型定义的验证规则验证请求数据
2. **填充数据：** 使用 `fill()` 方法填充模型属性
3. **类型转换：** 自动进行数据类型转换

### DTO 绑定示例
对于数据传输对象（DTO），框架会：

1. **属性映射：** 自动将 JSON 字段映射到 DTO 属性
2. **类型安全：** 保持强类型检查
3. **序列化：** 自动将 DTO 对象序列化为 JSON 响应

## 最佳实践

1. **单一职责：** 每个控制器专注于特定功能领域
2. **RESTful 设计：** 遵循 REST API 设计原则
3. **输入验证：** 使用模型的验证属性进行数据验证
4. **错误处理：** 合理使用异常处理机制
5. **中间件：** 使用中间件处理横切关注点（认证、日志等）
6. **响应格式：** 保持 API 响应格式的一致性