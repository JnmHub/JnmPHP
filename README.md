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
│   ├── Models/                   # Eloquent 模型
│   ├── Middleware/               # 自定义中间件
│   ├── Providers/                # 应用服务提供者
│   ├── Subscribers/              # 事件订阅者
│   ├── View/                     # Blade 视图文件
│   └── Console/                  # 控制台命令
├── kernel/                       # 框架核心代码
│   ├── Attribute/                # PHP Attributes 定义
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
├── cache/                        # 框架缓存
├── lang/                         # 多语言文件
├── logs/                         # 应用日志
├── public/                       # 静态资源
├── .env                          # 环境配置
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

**详细文档**：📖 [路由系统使用指南](kernel/Routing/README.md)

### 2. 模型与数据库

基于 Eloquent ORM，使用 Attributes 进行配置：

```php
<?php
namespace App\Models;

use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\Database\{HasMany, BelongsTo};
use Kernel\Attribute\Validation\Validate;
use Kernel\Database\BaseModel;

class User extends BaseModel
{
    // 主键字段配置
    #[TableField(isPrimaryKey: true, isFillable: false, isHidden: true)]
    protected int $id;

    // 可填充字段，自定义映射到数据库列
    #[TableField(columnName: 'username', isFillable: true)]
    #[Validate('required|string|max:50')]
    protected string $userName;

    // 一对多关系：User has many Posts
    #[HasMany(related: Post::class)]
    protected array $posts;
}
```

**详细文档**：📖 [数据库模型系统指南](kernel/Database/README.md)

### 3. 中间件系统

创建和使用中间件保护路由：

```php
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
}
```

**详细文档**：📖 [中间件开发指南](kernel/Middleware/README.md)

### 4. 请求与响应处理

框架支持多种响应类型：

```php
<?php
use Kernel\Response\JsonResponse;
use Kernel\Response\ViewResponse;

class ResponseController extends BaseController
{
    // 自动 JSON 响应
    #[Get('/api/data')]
    public function getApiData(): array
    {
        return ['status' => 'success', 'data' => [1, 2, 3]];
    }

    // 手动 JSON 响应
    #[Get('/api/error')]
    public function getError(): JsonResponse
    {
        return JsonResponse::error('Something went wrong', 400);
    }

    // 视图响应
    #[Get('/page')]
    public function getPage(): ViewResponse
    {
        return $this->view('welcome', ['title' => 'Welcome Page']);
    }
}
```

**详细文档**：📖 [请求处理指南](kernel/Request/README.md) | 📖 [响应处理指南](kernel/Response/README.md)

### 5. 会话管理

```php
// 使用会话
session(['user_id' => $user->id]);

// 获取会话数据
$userId = session('user_id');

// 闪存消息
session()->flash('success', '操作成功！');
```

**详细文档**：📖 [会话管理系统指南](kernel/Session/README.md)

### 6. 验证系统

```php
class Product extends BaseModel
{
    #[TableField(isFillable: true)]
    #[Validate('required|string|max:255')]
    protected string $name;

    #[TableField(isFillable: true)]
    #[Validate('required|numeric|min:0')]
    protected float $price;
}
```

**详细文档**：📖 [验证系统使用指南](kernel/Validation/README.md)

## 📚 详细文档导航

### 🔧 核心组件文档

| 组件 | 功能描述 | 详细文档 |
|------|----------|----------|
| **路由系统** | 基于注解的声明式路由定义和自动分发 | 📖 [路由系统指南](kernel/Routing/README.md) |
| **请求处理** | HTTP 请求数据的提取、解析和验证 | 📖 [请求处理指南](kernel/Request/README.md) |
| **响应处理** | 多种响应类型的统一处理和输出 | 📖 [响应处理指南](kernel/Response/README.md) |
| **数据库模型** | 基于 Eloquent 的增强 ORM 系统 | 📖 [数据库模型系统](kernel/Database/README.md) |
| **会话管理** | 多驱动的会话存储和管理系统 | 📖 [会话管理系统](kernel/Session/README.md) |
| **验证系统** | 基于 Laravel 的数据验证框架 | 📖 [验证系统指南](kernel/Validation/README.md) |
| **中间件系统** | 洋葱模型的请求处理管道 | 📖 [中间件开发指南](kernel/Middleware/README.md) |
| **事件系统** | 应用生命周期事件管理 | 📖 [事件系统文档](kernel/Events/README.md) |
| **事件订阅者** | 基于目录约定的事件订阅者管理 | 📖 [事件订阅者指南](kernel/Subscribers/README.md) |

### 🎯 属性注解文档

| 注解类型 | 功能描述 | 详细文档 |
|----------|----------|----------|
| **HTTP 注解** | 路由定义和请求参数绑定 | 📖 [HTTP 注解文档](kernel/Attribute/Http/README.md) |
| **数据库注解** | 模型字段和关系定义 | 📖 [数据库注解文档](kernel/Attribute/Database/README.md) |
| **中间件注解** | 中间件配置和应用 | 📖 [中间件注解文档](kernel/Attribute/Middleware/README.md) |
| **验证注解** | 字段验证规则定义 | 📖 [验证注解文档](kernel/Attribute/Validation/README.md) |
| **模型访问器** | 属性访问器和修改器定义 | 📖 [模型访问器注解文档](kernel/Attribute/ModelAccessor/README.md) |

### 📖 应用层文档

| 模块 | 功能描述 | 详细文档 |
|------|----------|----------|
| **控制器** | HTTP 请求处理和业务逻辑 | 📖 [控制器开发指南](app/Controller/README.md) |
| **模型** | 数据模型和业务规则 | 📖 [模型开发指南](app/Models/README.md) |
| **中间件** | 自定义中间件开发 | 📖 [中间件开发指南](app/Middleware/README.md) |
| **服务提供者** | 服务注册和配置 | 📖 [服务提供者配置](app/Providers/README.md) |
| **事件订阅者** | 事件监听和处理 | 📖 [事件订阅者开发](app/Subscribers/README.md) |

### ⚙️ 配置和工具文档

| 配置/工具 | 功能描述 | 详细文档 |
|-----------|----------|----------|
| **服务配置** | 框架服务提供者配置 | 📖 [服务提供者配置](config/README.md) |
| **命令行工具** | CLI 工具和使用方法 | 📖 [CLI 工具文档](README.md#命令行工具) |

## 🛠 开发工具

### 命令行工具

```bash
# 查看可用命令
php jnm list

# 生成 IDE 辅助文件（模型代码补全）
php jnm ide-helper:models

# 示例命令
php jnm app:hello-world "JnmPHP" --uppercase
```

### IDE 辅助

```bash
# 为模型生成 IDE 代码补全文件
php jnm ide-helper:models

# 实时监听模型文件变化，自动更新 IDE 辅助
./listenDir-darwin-arm64 -dir "app/Models" -cmd "php jnm ide-helper:models"
```

## 🏗 部署指南

### Web 服务器配置

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

### 生产环境配置

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

## 🌟 快速上手示例

### 创建 RESTful API

```php
<?php
namespace App\Controller\Api;

use Kernel\Attribute\Http\{Get, Post, Put, Delete};
use Kernel\Attribute\Http\PathVariable;
use Kernel\Attribute\Http\RequestBody;
use Kernel\Attribute\Middleware\Middleware;

#[RoutePrefix('/api/users')]
#[Middleware('auth')]
class UserController extends BaseController
{
    #[Get('/')]
    public function index(): array
    {
        return User::paginate(15);
    }

    #[Get('/{id}')]
    public function show(#[PathVariable('id')] int $id): array
    {
        return User::findOrFail($id);
    }

    #[Post('/')]
    public function store(#[RequestBody] UserCreateRequest $request): array
    {
        return User::create($request->validated());
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
    public function destroy(#[PathVariable('id')] int $id): JsonResponse
    {
        User::findOrFail($id)->delete();
        return JsonResponse::success(null, 204);
    }
}
```

### 创建数据模型

```php
<?php
namespace App\Models;

use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\Database\{HasMany, BelongsTo};
use Kernel\Attribute\Validation\Validate;
use Kernel\Database\BaseModel;

class Post extends BaseModel
{
    #[TableField(isPrimaryKey: true, isFillable: false, isHidden: true)]
    protected int $id;

    #[TableField(isFillable: true)]
    #[Validate('required|string|max:255')]
    protected string $title;

    #[TableField(isFillable: true)]
    #[Validate('required|string')]
    protected string $content;

    #[TableField(isFillable: true)]
    #[Validate('required|exists:users,id')]
    protected int $authorId;

    #[BelongsTo(related: User::class, foreignKey: 'authorId')]
    protected User $author;

    #[HasMany(related: Comment::class)]
    protected array $comments;
}
```

## 🤝 贡献指南

欢迎贡献代码！请遵循以下步骤：

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/amazing-feature`)
3. 提交更改 (`git commit -m 'Add amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 开启 Pull Request

## 📄 许可证

本项目基于 [Apache License 2.0](LICENSE) 许可证开源。

## 🙋‍♂️ 支持

如有问题或建议，请通过以下方式联系：

- 提交 [Issue](https://github.com/your-repo/jnmphp/issues)
- 发送邮件至：[105626@qq.com]

---

**JnmPHP** - 让 PHP 开发更简单、更现代！ ✨

[![ZRead](https://img.shields.io/badge/Ask_Zread-_.svg?style=for-the-badge&color=00b0aa&labelColor=000000&logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQuOTYxNTYgMS42MDAxSDIuMjQxNTZDMS44ODgxIDEuNjAwMSAxLjYwMTU2IDEuODg2NjQgMS42MDE1NiAyLjI0MDFWNC45NjAxQzEuNjAxNTYgNS4zMTM1NiAxLjg4ODEgNS42MDAxIDIuMjQxNTYgNS42MDAxSDQuOTYxNTZDNS4zMTUwMiA1LjYwMDEgNS42MDE1NiA1LjMxMzU2IDUuNjAxNTYgNC45NjAxVjIuMjQwMUM1LjYwMTU2IDEuODg2NjQgNS4zMTUwMiAxLjYwMDEgNC45NjE1NiAxLjYwMDFaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00Ljk2MTU2IDEwLjM5OTlIMi4yNDE1NkMxLjg4ODEgMTAuMzk5OSAxLjYwMTU2IDEwLjY4NjQgMS42MDE1NiAxMS4wMzk5VjEzLjc1OTlDMS42MDE1NiAxNC4xMTM0IDEuODg4MSAxNC4zOTk5IDIuMjQxNTYgMTQuMzk5OUg0Ljk2MTU2QzUuMzE1MDIgMTQuMzk5OSA1LjYwMTU2IDE0LjExMzQgNS42MDE1NiAxMy43NTk5VjExLjAzOTlDNS42MDE1NiAxMC42ODY0IDUuMzE1MDIgMTAuMzk5OSA0Ljk2MTU2IDEwLjM5OTlaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik0xMy43NTg0IDEuNjAwMUgxMS4wMzg0QzEwLjY4NSAxLjYwMDEgMTAuMzk4NCAxLjg4NjY0IDEwLjM5ODQgMi4yNDAxVjQuOTYwMUMxMC4zOTg0IDUuMzEzNTYgMTAuNjg1IDUuNjAwMSAxMS4wMzg0IDUuNjAwMUgxMy43NTg0QzE0LjExMTkgNS42MDAxIDE0LjM5ODQgNS4zMTM1NiAxNC4zOTg0IDQuOTYwMVYyLjI0MDFDMTQuMzk4NCAxLjg4NjY0IDE0LjExMTkgMS42MDAxIDEzLjc1ODQgMS42MDAxWiIgZmlsbD0iI2ZmZiIvPgo8cGF0aCBkPSJNNCAxMkwxMiA0TDQgMTJaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00IDEyTDEyIDQiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4K&logoColor=ffffff)](https://zread.ai/JnmHub/JnmPHP)