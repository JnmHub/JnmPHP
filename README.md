# JnmPHP 框架

JnmPHP 是一个轻量级、现代化的 PHP 框架，专为快速构建高性能 API 和 Web 应用而设计。它巧妙地融合了 `illuminate` (Laravel) 的核心组件（如 Eloquent ORM、Blade 模板引擎、Validation 和 Container）与现代 PHP 8+ 的特性（尤其是 **Attributes**），提供了一个优雅且高效的开发体验。

框架的核心设计理念是"**配置即代码**"，通过 PHP Attributes（注解）来定义路由、模型关系、验证规则和中间件，极大地减少了传统配置文件，使代码更具表现力且易于维护。



## 核心特性



- **[x] 现代化 & 轻量级**: 充分利用 PHP 8.2+ 的 Attributes 特性，代码简洁明了。
- **[x] 声明式路由**: 使用 `#[Get]`, `#[Post]`, `#[RoutePrefix]` 等注解在控制器中直接定义路由。
- **[x] 强大的 ORM**: 深度集成 `illuminate/database` (Eloquent)，并在此基础上通过 `#[TableField]`、`#[HasMany]`、`#[BelongsTo]` 等注解实现了模型的全自动配置。
- **[x] 自动验证**: 可选的 `#[Validate]` 注解，结合 `#[RequestBody]` 可实现对 JSON 请求的自动验证和模型绑定。
- **[x] 依赖注入**: 基于 `illuminate/container` 实现，支持构造函数注入和方法注入。
- **[x] 中间件 (Middleware)**: 支持 `#[Middleware]` 注解，可轻松实现全局、路由组（控制器）和单个路由的中间件。
- **[x] 模板引擎**: 集成 `illuminate/view` (Blade)，提供强大的视图渲染能力。
- **[x] 服务提供者 (Service Provider)**: 采用服务提供者模式引导和注册核心服务。
- **[x] 事件 & 订阅者**: 简单的事件管理器，支持通过 `EventServiceProvider` 自动发现和注册事件订阅者。
- **[x] 强大的异常处理**: 自动捕获 `ValidationException` 和 `HttpException`，并返回标准化的 JSON 错误响应。
- **[x] 多语言支持**: 集成 `illuminate/translation`，支持 `lang` 目录下的多语言文件。
- **[x] DTO 支持**: 支持使用纯数据传输对象 (DTO) 接收请求，自动进行 JSON 绑定。
- **[x] 智能缓存**: 自动缓存路由、视图和订阅者解析结果，提升应用性能。
- **[x] 控制台命令**: 基于 `symfony/console` 提供强大的命令行工具，包含 IDE 辅助文件生成器。



## 环境要求



- PHP 8.2+
- Composer



## 快速开始





### 1. 安装



Bash

```
# 1. 克隆项目
git clone [您的仓库地址] jnmphp-project
cd jnmphp-project

# 2. 安装依赖
composer install

# 3. 创建环境变量文件
cp .env.example .env

# 4. 配置 .env 文件
# 至少需要配置数据库连接信息：
# DB_HOST=127.0.0.1
# DB_DATABASE=aaa
# DB_USERNAME=root
# DB_PASSWORD=root
# APP_DEBUG=true
# APP_TIMEZONE=Asia/Shanghai

# 5. 启动开发服务器
php -S localhost:8000
```



### 2. 目录结构



```
.
├── app/                  # 应用程序核心代码
│   ├── Controller/       # 控制器
│   ├── Middleware/       # 中间件
│   ├── Models/           # Eloquent 模型
│   ├── Providers/        # 服务提供者
│   ├── Subscribers/      # 事件订阅者
│   └── View/             # Blade 视图文件
├── cache/                # 框架缓存（路由、视图、订阅者）
├── config/               # 配置文件 (database.php, providers.php)
├── kernel/               # 框架内核代码
│   ├── Attribute/        # 所有的 PHP Attributes
│   ├── Database/         # 数据库扩展 (BaseModel 和 Traits)
│   ├── Exception/        # 异常处理
│   ├── Middleware/       # 中间件核心 (Pipeline)
│   ├── Request/          # 请求处理
│   ├── Response/         # 响应处理 (JsonResponse, ViewResponse)
│   ├── Routing/          # 路由核心 (Router, RouteCollector)
│   └── Validation/       # 验证器工厂
├── lang/                 # 多语言文件 (zh_CN, en)
├── public/               # Web 服务器入口（包含静态资源）
├── vendor/               # Composer 依赖
├── .env                  # 环境变量
├── .htaccess             # Apache 配置
└── index.php             # 应用程序入口文件
```



## 框架使用指南

## 辅助工具

listenDir 是一个由Go编写的一个小工具，可以监听某一个文件夹内的所有文件的变化，如果发生变化则执行指定的cmd命令
例：
```bash
 ./listenDir -dir "app/Models" -cmd "php jnm ide-helper:models"
```
- -dir 指定要监听的文件夹
- -cmd 需要执行的命令


### 1. 定义路由



路由是使用 Attributes 在控制器（`app/Controller/`）的方法上定义的。

- `#[RoutePrefix('/path')]`: 定义在类上，为该控制器的所有路由添加前缀。
- `#[Get('/path')]`, `#[Post('/path')]`: 定义路由路径和 HTTP 方法。
- `#[PathVariable('name')]`: 将 URL 中的参数注入到方法参数。
- `#[Middleware('alias')]`: 为路由或控制器指定中间件。

**示例: `app/Controller/admin/IndexController.php`**

PHP

```
<?php
namespace App\Controller\admin;

use App\Controller\BaseController;
use Kernel\Attribute\Http\Get;
use Kernel\Attribute\Http\PathVariable;
use Kernel\Attribute\Http\RoutePrefix;
use Kernel\Attribute\Middleware\Middleware;

#[RoutePrefix('/admin')] // 所有路由都将以 /admin 开头
#[Middleware("auth")]    // 该控制器的所有方法都需要 'auth' 中间件
class IndexController extends BaseController
{
    // 访问: GET /admin/index
    #[Get('/index')]
    #[Middleware("log")] // 附加 'log' 中间件
    public function index()
    {
        return ['message' => 'Welcome to admin index'];
    }

    // 访问: GET /admin/info/123
    #[Get('/info/{aid}')]
    public function getInfo(#[PathVariable('aid')] int $id): string
    {
        // $id 会被自动转换为 int 类型并注入
        return "Fetching admin info for ID: " . $id;
    }

    // 访问: GET /admin/user/456/order/789
    #[Get('/user/{uid}/order/{oid}')]
    public function getOrder(
        #[PathVariable('uid', '用户ID不能为空')] string $userId,
        #[PathVariable('oid', '订单ID缺失')] string $orderId
    ): string {
        // 注解中的第二个参数是参数缺失时的错误提示
        return "用户ID：{$userId}，订单ID：{$orderId}";
    }
}
```



### 2. 定义模型 (ORM)



模型是框架的特色之一。你不再需要定义 `$fillable`、`$hidden` 或编写 `posts()` 关联方法，一切都通过 Attributes 完成。

模型需继承 `Kernel\Database\BaseModel`。

**示例: `app/Models/User.php`**

PHP

```
<?php
namespace App\Models;

use Kernel\Attribute\Database\HasMany;
use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\ModelAccessor\Accessor;
use Kernel\Attribute\ModelAccessor\Mutator;
use Kernel\Database\BaseModel;

class User extends BaseModel
{
    protected $table = 'users';

    // #[TableField] 是核心注解
    #[TableField(isPrimaryKey: true, isFillable: false, isHidden: true)]
    protected int $id;

    // 自动将 'userName' 属性映射到数据库的 'name' 列
    // 并且 'name' 列是可批量赋值的 (isFillable: true 是默认值)
    #[TableField(columnName: 'name', isFillable: true)]
    protected string $userName;

    #[TableField]
    protected string $email;

    #[TableField(isFillable: false)]
    protected string $password;

    /**
     * 定义一对多关联 (User 拥有多个 Post)
     * 框架会自动处理外键 (user_id)
     */
    #[HasMany(related: Post::class)]
    protected array $posts; // 类型应为 array 或 Collection

    /**
     * 定义一个访问器 (Accessor)
     * 获取 $user->userName 时自动调用
     */
    #[Accessor]
    public function getUserNameAccessor(?string $value): string
    {
        return "ccc" . ucfirst((string) $value);
    }

    /**
     * 定义一个修改器 (Mutator)
     * 设置 $user->password = 'secret' 时自动调用
     */
    #[Mutator]
    public function setPasswordMutator(string $value): string
    {
        // 存入数据库的将是哈希后的值
        return password_hash($value, PASSWORD_BCRYPT);
    }
}
```

**其他关联注解:**

- `#[BelongsTo(related: User::class, foreignKey: 'user_id')]` (定义在 `Post` 模型中)
- `#[BelongsToMany(related: Tag::class, table: 'post_tag', ...)]` (定义在 `Post` 模型中)



### 3. 请求绑定与自动验证



这是框架最高效的功能。通过 `#[RequestBody]` 和 `#[Validate]`，框架可以自动解析传入的 JSON，根据模型注解进行验证，如果验证通过，则自动填充并注入模型实例。

**1. 在模型中定义验证规则: `app/Models/Product.php`**

PHP

```
<?php
namespace App\Models;

use Kernel\Database\BaseModel;
use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\Validation\Validate; // 引入 Validate 注解

class Product extends BaseModel
{
    #[TableField(isPrimaryKey: true, isFillable: false)]
    protected int $id;

    /**
     * 规则: 必填, 字符串, 在 products 表的 sku 字段中唯一, 最大50字符
     */
    #[TableField(columnName: 'sku', isFillable: true)]
    #[Validate('required|string|unique:products,sku|max:50')]
    protected string $sku;

    /**
     * 规则: 必填, 字符串, 最大255字符
     */
    #[TableField(isFillable: true)]
    #[Validate('required|string|max:255')]
    protected string $name;

    /**
     * 规则: 必填, 数字, 最小值 0
     */
    #[TableField(isFillable: true)]
    #[Validate('required|numeric|min:0')]
    protected float $price;
}
```

**2. 在控制器中接收模型: `app/Controller/IndexController.php`**

PHP

```
use App\Models\Product;
use Kernel\Attribute\Http\Post;
use Kernel\Attribute\Http\RequestBody;

class IndexController extends BaseController
{
    /**
     * 访问: POST /products
     * 传入 JSON: {"sku": "SKU123", "name": "新产品", "price": 99.9}
     */
    #[Post('/products')]
    public function createProduct(#[RequestBody] Product $product): Product
    {
        // 1. 框架已自动解析 JSON。
        // 2. 框架已根据 Product 上的 #[Validate] 规则完成验证。
        //    - 如果失败，将自动抛出 ValidationException 并返回 422 JSON 错误。
        // 3. 框架已自动将验证通过的数据 (sku, name, price) 填充到 $product 实例。

        // 你可以直接使用这个已填充和验证的模型
        // $product->save(); // 例如保存到数据库

        return $product; // 框架会自动将 $product 转为 JSON 响应
    }
}
```



### 4. 返回响应



- JSON 响应:

  在控制器方法中，直接返回一个数组或对象。框架会自动将其包装为成功的 JSON 响应。

  PHP

  ```
  #[Get('/user/1')]
  public function getUser()
  {
      // 框架会自动转换为: {"code": 200, "message": "success", "data": {"id": 1, "name": "..."}}
      return User::find(1);
  }
  ```

  对于自定义的错误响应，可以使用 `JsonResponse` 类（但通常由异常处理器自动完成）。

- 视图 (Blade) 响应:

  使用 BaseController 提供的 view() 辅助方法。

  **`app/Controller/IndexController.php`**

  PHP

  ```
  #[Get('/')]
  public function indexView()
  {
      $products = [
          ['name' => '产品A', 'price' => 100],
          ['name' => '产品B', 'price' => 200],
      ];
  
      // 渲染 app/View/index/index.blade.php 视图
      return $this->view('index.index', [
          'name'     => 'JnmPHP 开发者',
          'products' => $products
      ]);
  }
  ```

  **`app/View/index/index.blade.php`**

  Blade

  ```
  {{-- 继承布局 --}}
  @extends('layouts.app')
  
  @section('title', '欢迎来到首页')
  
  @section('content')
  <p>你好, {{ $name }}!</p>
  
  <h3>产品列表:</h3>
  <ul>
      @foreach($products as $product)
      <li>{{ $product['name'] }} - ￥{{ $product['price'] }}</li>
      @endforeach
  </ul>
  
  {{-- 自动使用 lang/zh_CN/validation.php 中的翻译 --}}
  <p>
      翻译测试: @lang('validation.accepted', ['attribute' => '条款'])
  </p>
  @endsection
  ```



### 5. 数据传输对象 (DTO)

除了 Eloquent 模型，框架还支持使用纯 DTO 类来接收请求体数据：

**示例 DTO 类: `app/Dto/Department.php`**

PHP

```
<?php

namespace App\Dto;

class Department
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $location = null;
}
```

**在控制器中使用 DTO:**

PHP

```
#[Post('/department')]
public function createDepartment(#[RequestBody] Department $department): Department
{
    $department->id = rand(100, 999);

    // 框架会自动将返回的对象转为 JSON
    return $department;
}
```

### 6. 控制台命令

框架基于 `symfony/console` 提供了强大的命令行工具支持。项目根目录下的 `jnm` 文件是主要的命令行入口点。

**可用的内置命令:**

- `app:hello-world [name] [--uppercase|-u]` - 输出 Hello World 消息，支持参数和选项
- `ide-helper:models` - 为模型生成 IDE 辅助文件，提供代码自动补全

**使用命令:**

```bash
# 基本用法
php jnm <command>

# 示例：
php jnm app:hello-world World
php jnm app:hello-world JnmPHP --uppercase

# 为所有模型生成 PHPDoc 注解，支持 IDE 代码提示
php jnm ide-helper:models
```

**IDE 辅助功能:**

`ide-helper:models` 命令生成的辅助文件 `app/_ide_helper_models.php` 包含：
- 所有模型的属性注解 (`@property`)
- 静态魔术方法注解 (`@method static`)
- Eloquent 关联方法注解
- 模型访问器/修改器方法注解
- 常用 Eloquent 方法提示

这将大大提升在 IDE 中使用模型的开发体验，支持完整的代码自动补全功能。

### 7. 中间件



1. 创建中间件:

   在 app/Middleware/ 目录下创建类，并实现 Kernel\Middleware\MiddlewareInterface。

   PHP

   ```
   // app/Middleware/AuthMiddleware.php
   class AuthMiddleware implements MiddlewareInterface
   {
       public function handle(mixed $request, Closure $next)
       {
           $expectedToken = 'Bearer my-secret-token';

           if (!isset($_SERVER['HTTP_AUTHORIZATION']) || $_SERVER['HTTP_AUTHORIZATION'] !== $expectedToken) {
               // 验证失败, 抛出异常, 中断请求
               throw new HttpException(401, 'Unauthorized');
           }

           // 验证通过, 继续处理请求
           return $next($request);
       }
   }
   ```

2. 注册别名:

   在 kernel/Middleware/MiddlewareManager.php 中注册别名：

   PHP

   ```
   protected array $routeMiddlewareAliases = [
       'auth' => \App\Middleware\AuthMiddleware::class,
       'log' => \App\Middleware\LogRequestMiddleware::class,
   ];
   ```

3. 使用中间件:

   在控制器类或方法上使用 #[Middleware('alias')] 注解。

   PHP

   ```
   #[RoutePrefix('/admin')]
   #[Middleware('auth')] // 应用于所有方法
   class AdminController extends BaseController
   {
       #[Get('/dashboard')] // 已受 'auth' 保护
       public function dashboard() { ... }

       #[Post('/logs/clear')]
       #[Middleware('admin')] // 额外需要 'admin' 中间件
       public function clearLogs() { ... }
   }
   ```

### 8. 高级路由功能

框架支持多种路由属性组合，提供灵活的路由定义：

**多路由绑定到同一方法:**

PHP

```
#[Get('/index'), Get('/')]
#[Middleware("log")]
public function index($aaa = null)
{
    return ['message' => 'Hello from index'];
}
```

**重复路由属性:**

PHP

```
#[Route('/users', ['GET', 'POST'])]
#[Route('/api/users', ['GET', 'POST'])]
public function handleUsers()
{
    // 处理用户相关的请求
}
```

## API 示例

以下是一些完整的 API 使用示例：

### 获取用户及其关联数据

PHP

```
#[Get('/users/{id}/posts')]
public function getUserWithPosts(int $id)
{
    $user = User::getById($id);

    // 触发关联关系加载
    $posts = $user->posts;

    return [
        'user' => $user->toArray(),
        'posts' => $posts->toArray(),
    ];
}
```

### 关联查询示例

PHP

```
#[Get('/posts/{id}/tags')]
public function getPostWithTags(int $id)
{
    $post = \App\Models\Post::getById($id);
    if (!$post) {
        return ['error' => 'Post not found'];
    }

    // 触发 BelongsToMany 关联加载
    $tags = $post->tags;

    return [
        'post' => $post->toArray(),
        'tags' => $tags->toArray()
    ];
}
```



## 应用程序生命周期



1. 请求进入 `index.php`。
2. `Dotenv` 加载 `.env` 环境变量。
3. `Application` 实例被创建。
4. `registerProviders()`: 遍历 `config/providers.php`，调用所有服务提供者的 `register` 方法，将服务（如路由、数据库、视图、事件）绑定到容器。
5. `bootProviders()`: 调用所有服务提供者的 `boot` 方法。
    - `AppServiceProvider` 注册全局异常处理器。
    - `DatabaseServiceProvider` 初始化数据库连接 (Eloquent)。
    - `EventServiceProvider` 扫描并注册所有事件订阅者。
    - `ViewServiceProvider` 启动 Blade 引擎。
6. 触发 `app.boot` 事件。
7. `Request::capture()` 捕获当前 HTTP 请求。
8. `$app->handle($request)`:
    - `Router` 被实例化（携带所有已缓存的路由）。
    - `$router->dispatch()`: 匹配当前 URI 和 Method。
    - 如果匹配成功，创建中间件 `Pipeline`（包含全局中间件和路由中间件）。
    - 请求通过中间件“洋葱”管道。
    - 如果请求到达核心，`Router` 解析控制器方法参数（`#[PathVariable]`, `#[RequestBody]`），并执行控制器方法。
    - `Router` 获取控制器返回值（数组、对象或 `ResponseInterface` 实例）。
    - 框架将返回值格式化为 `JsonResponse` 或 `ViewResponse` 并发送。
9. 触发 `app.shutdown` 事件。



## 许可证



该项目基于 [Apache License 2.0](https://www.google.com/search?q=LICENSE&authuser=1) 许可。

[![zread](https://img.shields.io/badge/Ask_Zread-_.svg?style=for-the-badge&color=00b0aa&labelColor=000000&logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQuOTYxNTYgMS42MDAxSDIuMjQxNTZDMS44ODgxIDEuNjAwMSAxLjYwMTU2IDEuODg2NjQgMS42MDE1NiAyLjI0MDFWNC45NjAxQzEuNjAxNTYgNS4zMTM1NiAxLjg4ODEgNS42MDAxIDIuMjQxNTYgNS42MDAxSDQuOTYxNTZDNS4zMTUwMiA1LjYwMDEgNS42MDE1NiA1LjMxMzU2IDUuNjAxNTYgNC45NjAxVjIuMjQwMUM1LjYwMTU2IDEuODg2NjQgNS4zMTUwMiAxLjYwMDEgNC45NjE1NiAxLjYwMDFaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00Ljk2MTU2IDEwLjM5OTlIMi4yNDE1NkMxLjg4ODEgMTAuMzk5OSAxLjYwMTU2IDEwLjY4NjQgMS42MDE1NiAxMS4wMzk5VjEzLjc1OTlDMS42MDE1NiAxNC4xMTM0IDEuODg4MSAxNC4zOTk5IDIuMjQxNTYgMTQuMzk5OUg0Ljk2MTU2QzUuMzE1MDIgMTQuMzk5OSA1LjYwMTU2IDE0LjExMzQgNS42MDE1NiAxMy43NTk5VjExLjAzOTlDNS42MDE1NiAxMC42ODY0IDUuMzE1MDIgMTAuMzk5OSA0Ljk2MTU2IDEwLjM5OTlaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik0xMy43NTg0IDEuNjAwMUgxMS4wMzg0QzEwLjY4NSAxLjYwMDEgMTAuMzk4NCAxLjg4NjY0IDEwLjM5ODQgMi4yNDAxVjQuOTYwMUMxMC4zOTg0IDUuMzEzNTYgMTAuNjg1IDUuNjAwMSAxMS4wMzg0IDUuNjAwMUgxMy43NTg0QzE0LjExMTkgNS42MDAxIDE0LjM5ODQgNS4zMTM1NiAxNC4zOTg0IDQuOTYwMVYyLjI0MDFDMTQuMzk4NCAxLjg4NjY0IDE0LjExMTkgMS42MDAxIDEzLjc1ODQgMS42MDAxWiIgZmlsbD0iI2ZmZiIvPgo8cGF0aCBkPSJNNCAxMkwxMiA0TDQgMTJaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00IDEyTDEyIDQiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4K&logoColor=ffffff)](https://zread.ai/JnmHub/JnmPHP)