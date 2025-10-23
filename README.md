# JnmPHP Framework

**JnmPHP** 是一个现代化、轻量级的 PHP 框架，专为构建高性能 API 和 Web 应用程序而设计。框架充分利用 PHP 8+ Attributes（注解）特性，结合 Laravel 核心组件（Eloquent ORM、Blade 模板、验证器、容器），提供声明式、优雅且高效开发体验。

## ✨ 核心特性

### 🚀 现代化架构
- **PHP 8.2+ Required**: 充分利用现代 PHP 特性
- **Attribute-Driven**: 基于注解的声明式配置，减少样板代码
- **零配置启动**: 开箱即用的开发体验
- **高性能**: 智能缓存系统和优化的请求处理流程

### 🛠 开发特性
- **声明式路由**: 使用 `#[Get]`、`#[Post]`、`#[RoutePrefix]` 等注解直接在控制器定义路由
- **智能 ORM**: 增强的 Eloquent 模型，支持 `#[TableField]`、关系注解自动化配置
- **自动验证**: `#[Validate]` + `#[RequestBody]` 实现请求验证和模型绑定
- **依赖注入**: 基于 Laravel Container 的完整 DI 支持
- **中间件系统**: 洋葱模型中间件管道，支持注解配置

### 🎯 企业级功能
- **多数据库驱动**: MySQL、Session 驱动支持
- **模板引擎**: 完整的 Blade 模板支持
- **国际化**: 完整的多语言支持系统
- **事件系统**: 应用生命周期事件管理
- **异常处理**: 统一错误处理和标准化 JSON 响应

### ⚡ 性能优化
- **智能缓存**: 路由、视图、事件订阅者自动缓存
- **延迟加载**: JSON 数据解析和关系查询按需加载
- **编译模板**: Blade 模板预编译提升渲染性能

## 📋 系统要求

- **PHP 8.2+**
- **Composer**
- **MySQL/MariaDB** (可选，用于数据库功能)

## 🚀 快速开始

### 1. 安装框架

```bash
# 克隆项目
git clone [项目地址] jnmphp-project
cd jnmphp-project

# 安装依赖
composer install

# 配置环境
cp .env.example .env
```

### 2. 环境配置

编辑 `.env` 文件，配置必要的环境变量：

```bash
# 基础应用配置
APP_NAME=JnmPHP
APP_DEBUG=true
APP_TIMEZONE="Asia/Shanghai"
APP_LOCALE=zh_CN

# 数据库配置（如需使用数据库功能）
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# 日志配置
APP_LOG_CHANNEL=daily
APP_LOG_LEVEL=debug

# 会话配置
SESSION_DRIVER=native
SESSION_LIFETIME=120
```

### 3. 启动开发服务器

```bash
# 使用 PHP 内置服务器
php -S localhost:8000

# 或使用您喜欢的 Web 服务器指向项目根目录
# 应用入口文件: index.php
```

### 4. 访问应用

打开浏览器访问 `http://localhost:8000`，即可看到应用主页。

## 📁 项目结构

```
jnmphp-project/
├── app/                          # 应用层代码
│   ├── Controller/               # HTTP 控制器
│   │   └── admin/               # 管理后台控制器
│   ├── Models/                   # Eloquent 模型
│   ├── Middleware/               # 自定义中间件
│   ├── Providers/                # 应用服务提供者
│   ├── Subscribers/              # 事件订阅者
│   ├── View/                     # Blade 视图文件
│   │   ├── index/               # 页面视图
│   │   └── layouts/             # 布局模板
│   ├── Dto/                      # 数据传输对象
│   └── Console/                  # 控制台命令
├── kernel/                       # 框架核心代码
│   ├── Attribute/                # PHP Attributes 定义
│   │   ├── Database/             # 数据库相关注解
│   │   ├── Http/                 # HTTP 路由注解
│   │   ├── Middleware/           # 中间件注解
│   │   ├── ModelAccessor/        # 模型访问器注解
│   │   └── Validation/           # 验证注解
│   ├── Database/                 # 数据库扩展层
│   ├── Events/                   # 事件系统
│   ├── Middleware/               # 中间件核心
│   ├── Request/                  # HTTP 请求处理
│   ├── Response/                 # HTTP 响应处理
│   ├── Routing/                  # 路由系统
│   ├── Session/                  # 会话管理
│   ├── Validation/               # 验证器
│   └── Helpers/                  # 全局辅助函数
├── config/                       # 应用配置
│   └── providers.php             # 服务提供者注册
├── cache/                        # 框架缓存
│   ├── routes.php               # 路由缓存
│   ├── subscribers.php          # 事件订阅者缓存
│   └── views/                   # 视图编译缓存
├── lang/                         # 多语言文件
│   ├── en/                      # 英文语言包
│   └── zh_CN/                   # 中文简体语言包
├── logs/                         # 应用日志
├── public/                       # 静态资源
├── .env                          # 环境配置
├── .env.example                  # 环境配置示例
├── index.php                     # 应用入口
├── jnm                           # 命令行工具
└── README.md                     # 项目文档
```

## 🎯 核心概念

### 1. 控制器与路由

框架使用 PHP Attributes 直接在控制器中定义路由：

```php
<?php
namespace App\Controller;

use App\Controller\BaseController;
use Kernel\Attribute\Http\{Get, Post, RoutePrefix};
use Kernel\Attribute\Http\PathVariable;
use Kernel\Attribute\Http\RequestBody;
use Kernel\Attribute\Middleware\Middleware;

#[RoutePrefix('/api/v1')]    // 控制器级别路由前缀
class UserController extends BaseController
{
    // GET /api/v1/users/{id}
    #[Get('/users/{id}')]
    public function getUser(#[PathVariable('id')] int $id): array
    {
        return ['id' => $id, 'name' => 'User ' . $id];
    }

    // POST /api/v1/users
    #[Post('/users')]
    #[Middleware('auth')]  // 单个路由中间件
    public function createUser(#[RequestBody] UserCreateDto $dto): array
    {
        // 自动验证和绑定 DTO
        return $dto;
    }
}
```

**路由注解类型**：
- `#[Get(path)]` - GET 路由
- `#[Post(path)]` - POST 路由
- `#[Put(path)]` - PUT 路由
- `#[Delete(path)]` - DELETE 路由
- `#[Route(path, methods)]` - 通用路由定义
- `#[RoutePrefix(path)]` - 控制器级别前缀
- `#[PathVariable(name)]` - URL 参数绑定
- `#[RequestBody]` - JSON 请求体绑定
- `#[Middleware('alias')]` - 中间件配置

### 2. 模型与数据库

基于 Eloquent ORM，使用 Attributes 进行配置：

```php
<?php
namespace App\Models;

use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\Database\{HasMany, BelongsTo};
use Kernel\Attribute\ModelAccessor\{Accessor, Mutator};
use Kernel\Attribute\Validation\Validate;
use Kernel\Database\BaseModel;

class User extends BaseModel
{
    protected $table = 'users';

    // 主键字段配置
    #[TableField(isPrimaryKey: true, isFillable: false, isHidden: true)]
    protected int $id;

    // 可填充字段，自定义映射到数据库列
    #[TableField(columnName: 'username', isFillable: true)]
    #[Validate('required|string|max:50')]
    protected string $userName;

    // 密码字段，隐藏在 JSON 输出中
    #[TableField(isFillable: false, isHidden: true)]
    #[Validate('required|string|min:8')]
    protected string $password;

    // 一对多关系：User has many Posts
    #[HasMany(related: Post::class)]
    protected array $posts;

    // 访问器：获取时自动处理
    #[Accessor]
    public function getUserNameAccessor(?string $value): string
    {
        return ucfirst($value ?? '');
    }

    // 修改器：设置时自动处理
    #[Mutator]
    public function setPasswordMutator(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }
}
```

**支持的数据库注解**：
- `#[TableField]` - 字段配置（列名、类型、验证规则等）
- `#[HasOne]` / `#[HasMany]` - 一对/一对多关系
- `#[BelongsTo]` - 从属关系
- `#[BelongsToMany]` - 多对多关系
- `#[HasManyThrough]` - 远程一对多关系
- `#[MorphMany]` / `#[MorphTo]` - 多态关系
- `#[Accessor]` / `#[Mutator]` - 属性访问器/修改器

### 3. 中间件系统

创建和使用中间件保护路由：

```php
// app/Middleware/AuthMiddleware.php
<?php
namespace App\Middleware;

use Kernel\Middleware\MiddlewareInterface;
use Kernel\Exception\HttpException;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(mixed $request, \Closure $next): mixed
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$this->validateToken($token)) {
            throw new HttpException(401, 'Unauthorized');
        }

        return $next($request);
    }

    private function validateToken(string $token): bool
    {
        return $token === 'Bearer valid-token';
    }
}

// 注册中间件别名
// kernel/Middleware/MiddlewareManager.php
protected array $routeMiddlewareAliases = [
    'auth' => \App\Middleware\AuthMiddleware::class,
    'admin' => \App\Middleware\AdminMiddleware::class,
];
```

### 4. 响应处理

框架支持多种响应类型：

```php
<?php
use App\Controller\BaseController;
use Kernel\Response\JsonResponse;
use Kernel\Response\ViewResponse;

class ResponseController extends BaseController
{
    // 自动 JSON 响应
    #[Get('/api/data')]
    public function getApiData(): array
    {
        return ['status' => 'success', 'data' => [1, 2, 3]];
        // 自动转换为: {"code": 200, "message": "success", "data": ...}
    }

    // 手动 JSON 响应
    #[Get('/api/error')]
    public function getError(): JsonResponse
    {
        return JsonResponse::error('Something went wrong', 400);
        // 返回: {"code": 400, "message": "Something went wrong", "data": null}
    }

    // 视图响应
    #[Get('/page')]
    public function getPage(): ViewResponse
    {
        return $this->view('welcome', [
            'title' => 'Welcome Page',
            'users' => User::all()
        ]);
    }
}
```

### 5. 视图与模板

使用 Blade 模板引擎：

```php
// app/View/layouts/app.blade.php (布局模板)
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'JnmPHP App')</title>
    <meta charset="utf-8">
</head>
<body>
    <header>
        <h1>My Application</h1>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} My Company</p>
    </footer>
</body>
</html>

// app/View/index/index.blade.php (页面模板)
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard">
    <h2>Welcome, {{ $username }}!</h2>

    @if($users->count() > 0)
        <ul>
            @foreach($users as $user)
                <li>{{ $user->userName }}</li>
            @endforeach
        </ul>
    @else
        <p>@lang('messages.no_users_found')</p>
    @endif
</div>
@endsection
```

### 6. 控制台命令

框架提供强大的 CLI 工具：

```bash
# 查看可用命令
php jnm list

# 生成 IDE 辅助文件（模型代码补全）
php jnm ide-helper:models

# 示例命令
php jnm app:hello-world "JnmPHP" --uppercase
```

## 🔧 高级功能

### 1. 事件系统

```php
// 触发事件
EventManager::dispatch('user.registered', $user);

// 创建事件订阅者
// app/Subscribers/UserEventSubscriber.php
<?php
namespace App\Subscribers;

use Kernel\Events\SubscriberInterface;
use App\Models\User;

class UserEventSubscriber implements SubscriberInterface
{
    public function subscribe(): array
    {
        return [
            'user.registered' => 'handleUserRegistered',
            'user.login' => 'handleUserLogin',
        ];
    }

    public function handleUserRegistered(User $user): void
    {
        // 发送欢迎邮件等
        error_log("New user registered: " . $user->email);
    }
}
```

### 2. 会话管理

```php
// 使用会话
session(['user_id' => $user->id]);

// 获取会话数据
$userId = session('user_id');

// 闪存消息
session()->flash('success', '操作成功！');

// 配置会话驱动
// .env 文件
SESSION_DRIVER=database  # 使用数据库存储会话
```

### 3. 缓存管理

框架自动缓存以下内容：
- **路由缓存**: `cache/routes.php` - 编译后的路由定义
- **视图缓存**: `cache/views/` - 编译后的 Blade 模板
- **订阅者缓存**: `cache/subscribers.php` - 事件订阅者映射

**手动清除缓存**：
- 修改控制器后，删除 `cache/routes.php` 重新生成
- 修改视图后，删除 `cache/views/` 重新编译
- 修改订阅者后，删除 `cache/subscribers.php` 重新注册

### 4. 多语言支持

```php
// lang/zh_CN/messages.php
return [
    'welcome' => '欢迎',
    'no_users_found' => '未找到用户',
    'validation.required' => ':attribute 是必填项',
];

// 在视图中使用
@lang('messages.welcome')

// 在代码中使用
__('messages.welcome', ['name' => 'John'])
```

## 🔒 安全功能

### 1. CSRF 保护

```php
// 启用 CSRF 中间件
#[Middleware('csrf')]
class SecureController extends BaseController
{
    #[Post('/process-form')]
    public function processForm(#[RequestBody] FormData $data): array
    {
        // 受 CSRF 保护的表单处理
    }
}

// 在 Blade 中包含 CSRF 字段
<form method="POST" action="/process-form">
    {!! csrf_field() !!}
    <!-- 表单字段 -->
</form>
```

### 2. 输入验证

```php
class Product extends BaseModel
{
    #[TableField(isFillable: true)]
    #[Validate('required|string|max:255')]
    protected string $name;

    #[TableField(isFillable: true)]
    #[Validate('required|numeric|min:0')]
    protected float $price;

    #[TableField(isFillable: true)]
    #[Validate('email|unique:users,email')]
    protected string $email;
}
```

## 🌐 API 开发示例

### RESTful API 示例

```php
<?php
namespace App\Controller\Api;

use App\Controller\BaseController;
use App\Models\User;
use Kernel\Attribute\Http\{Get, Post, Put, Delete};
use Kernel\Attribute\Http\RequestBody;
use Kernel\Attribute\Http\PathVariable;
use Kernel\Attribute\Middleware\Middleware;

#[RoutePrefix('/api/users')]
#[Middleware('api.auth')]
class UserController extends BaseController
{
    #[Get('/')]
    public function index(): array
    {
        return User::with(['posts', 'profile'])->paginate(15);
    }

    #[Get('/{id}')]
    public function show(#[PathVariable('id')] int $id): array
    {
        $user = User::with(['posts', 'profile'])->find($id);
        if (!$user) {
            return JsonResponse::error('User not found', 404);
        }
        return $user;
    }

    #[Post('/')]
    #[Middleware('admin')]
    public function store(#[RequestBody] UserCreateRequest $request): array
    {
        $user = User::create($request->validated());
        return JsonResponse::success($user, 201);
    }

    #[Put('/{id}')]
    public function update(
        #[PathVariable('id')] int $id,
        #[RequestBody] UserUpdateRequest $request
    ): array {
        $user = User::findOrFail($id);
        $user->update($request->validated());
        return $user;
    }

    #[Delete('/{id}')]
    #[Middleware('admin')]
    public function destroy(#[PathVariable('id')] int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();
        return JsonResponse::success(null, 204);
    }
}
```

## 🛠 开发工具

### 1. IDE 辅助

```bash
# 为模型生成 IDE 代码补全文件
php jnm ide-helper:models

# 实时监听模型文件变化，自动更新 IDE 辅助
./listenDir-darwin-arm64 -dir "app/Models" -cmd "php jnm ide-helper:models"
```

### 2. 调试工具

```php
// 启用调试模式 (.env)
APP_DEBUG=true

// 在代码中调试
error_log('Debug message: ' . json_encode($data));

// 查看应用日志
tail -f logs/app-2024-01-01.log
```

### 3. 文件监听工具

`listenDir` 是一个 Go 编写的文件监听工具，支持实时响应文件变化：

```bash
# 选择对应系统版本：
# listenDir-darwin-amd64  - macOS Intel
# listenDir-darwin-arm64  - macOS Apple Silicon
# listenDir-linux-amd64   - Linux
# listenDir-windows-amd64.exe - Windows

# 监听模型文件变化并自动生成 IDE 辅助
./listenDir-darwin-arm64 -dir "app/Models" -cmd "php jnm ide-helper:models"

# 监听视图变化并清除缓存
./listenDir-darwin-arm64 -dir "app/View" -cmd "rm -rf cache/views/*"
```

## 🏗 部署指南

### 1. Web 服务器配置

#### Nginx 配置
```nginx
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
```

#### Apache 配置
```apache
# 项目已包含 .htaccess 文件
```

### 2. 生产环境配置

```bash
# 1. 关闭调试模式
APP_DEBUG=false

# 2. 优化 Composer 自动加载
composer install --no-dev --optimize-autoloader

# 3. 清除开发缓存
rm -rf cache/*

# 4. 设置文件权限
chmod -R 755 storage/
chmod -R 755 logs/
chmod -R 755 cache/
```

## 🤝 贡献指南

欢迎贡献代码！请遵循以下步骤：

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/amazing-feature`)
3. 提交更改 (`git commit -m 'Add amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 开启 Pull Request

## 📚 详细文档

- **[数据库模型关系注解文档](kernel/Attribute/Database/README.md)** - 详细的数据库关联关系注解使用指南
- **[服务提供者配置](config/providers.md)** - 服务提供者配置说明
- **[中间件开发指南](kernel/Middleware/README.md)** - 中间件开发和使用
- **[事件系统文档](kernel/Events/README.md)** - 事件和订阅者系统

## 📄 许可证

本项目基于 [Apache License 2.0](LICENSE) 许可证开源。

## 🙋‍♂️ 支持

如有问题或建议，请通过以下方式联系：

- 提交 [Issue](https://github.com/your-repo/jnmphp/issues)
- 发送邮件至：[105626@qq.com]

---

**JnmPHP** - 让 PHP 开发更简单、更现代！ ✨

[![ZRead](https://img.shields.io/badge/Ask_Zread-_.svg?style=for-the-badge&color=00b0aa&labelColor=000000&logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQuOTYxNTYgMS42MDAxSDIuMjQxNTZDMS44ODgxIDEuNjAwMSAxLjYwMTU2IDEuODg2NjQgMS42MDE1NiAyLjI0MDFWNC45NjAxQzEuNjAxNTYgNS4zMTM1NiAxLjg4ODEgNS42MDAxIDIuMjQxNTYgNS42MDAxSDQuOTYxNTZDNS4zMTUwMiA1LjYwMDEgNS42MDE1NiA1LjMxMzU2IDUuNjAxNTYgNC45NjAxVjIuMjQwMUM1LjYwMTU2IDEuODg2NjQgNS4zMTUwMiAxLjYwMDEgNC45NjE1NiAxLjYwMDFaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00Ljk2MTU2IDEwLjM5OTlIMi4yNDE1NkMxLjg4ODEgMTAuMzk5OSAxLjYwMTU2IDEwLjY4NjQgMS42MDE1NiAxMS4wMzk5VjEzLjc1OTlDMS42MDE1NiAxNC4xMTM0IDEuODg4MSAxNC4zOTk5IDIuMjQxNTYgMTQuMzk5OUg0Ljk2MTU2QzUuMzE1MDIgMTQuMzk5OSA1LjYwMTU2IDE0LjExMzQgNS42MDE1NiAxMy43NTk5VjExLjAzOTlDNS42MDE1NiAxMC42ODY0IDUuMzE1MDIgMTAuMzk5OSA0Ljk2MTU2IDEwLjM5OTlaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik0xMy43NTg0IDEuNjAwMUgxMS4wMzg0QzEwLjY4NSAxLjYwMDEgMTAuMzk4NCAxLjg4NjY0IDEwLjM5ODQgMi4yNDAxVjQuOTYwMUMxMC4zOTg0IDUuMzEzNTYgMTAuNjg1IDUuNjAwMSAxMS4wMzg0IDUuNjAwMUgxMy43NTg0QzE0LjExMTkgNS42MDAxIDE0LjM5ODQgNS4zMTM1NiAxNC4zOTg0IDQuOTYwMVYyLjI0MDFDMTQuMzk4NCAxLjg4NjY0IDE0LjExMTkgMS42MDAxIDEzLjc1ODQgMS42MDAxWiIgZmlsbD0iI2ZmZiIvPgo8cGF0aCBkPSJNNCAxMkwxMiA0TDQgMTJaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00IDEyTDEyIDQiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4K&logoColor=ffffff)](https://zread.ai/JnmHub/JnmPHP)