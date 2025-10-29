# 辅助函数系统 (Helper Functions System)

此目录包含 JnmPHP 框架的辅助函数系统。辅助函数系统提供了全局可用的便捷函数，简化了日常开发中的常见操作。

## 目录结构

```
kernel/Helpers/
├── Str.php              # 字符串处理工具类
├── functions.php         # 全局辅助函数集合
└── README.md            # 本文档
```

## 系统架构

### 设计理念

JnmPHP 辅助函数系统采用以下设计理念：

1. **便捷性：** 提供简单易用的全局函数接口
2. **一致性：** 函数命名和行为与主流框架保持一致
3. **类型安全：** 支持强类型和智能类型转换
4. **依赖注入：** 通过容器解析服务依赖
5. **功能单一：** 每个函数专注于特定功能

### 组件分类

#### 1. 容器相关函数
- `app()` - 获取服务容器实例或解析服务
- `config()` - 获取配置项

#### 2. 路径相关函数
- `base_path()` - 获取项目根目录路径

#### 3. 安全相关函数
- `e()` - HTML 字符串转义
- `csrf_token()` - 获取 CSRF 令牌
- `csrf_field()` - 生成 CSRF 隐藏域

#### 4. 会话相关函数
- `session()` - 获取会话实例或会话值

#### 5. 环境相关函数
- `env()` - 获取环境变量值

#### 6. 工具类
- `Str` - 字符串处理工具类

## 核心组件详解

### 1. Str - 字符串工具类

**功能：** 提供字符串处理相关的静态方法

#### urldecode() 方法 - URL 解码

```php
public static function urldecode($data)
{
    // 检查输入数据是否为字符串类型
    if (is_string($data)) {
        // 如果是字符串，则进行 URL 解码
        return urldecode($data);
    }

    // 如果不是字符串，则直接返回原始数据
    return $data;
}
```

**特性说明：**
- **类型安全：** 只对字符串类型进行解码
- **非字符串保持：** 非字符串数据原样返回
- **兼容性：** 兼容各种数据类型的输入

**使用示例：**
```php
// URL 字符串解码
$encoded = "Hello%20World";
$decoded = Str::urldecode($encoded); // "Hello World"

// 非字符串数据保持不变
$array = ['key' => 'value'];
$result = Str::urldecode($array); // ['key' => 'value']

// 数字保持不变
$number = 123;
$result = Str::urldecode($number); // 123
```

**应用场景：**
```php
// 路由参数解码
class Router
{
    public function dispatch($uri, $method)
    {
        // 解码 URL 参数
        $params = array_map(['Kernel\Helpers\Str', 'urldecode'], $params);
        // 处理路由...
    }
}
```

### 2. 全局辅助函数

#### app() 函数 - 服务容器访问

**功能：** 获取服务容器实例或解析指定服务

```php
function app(string $abstract = null)
{
    $container = Application::getInstance()->getContainer();
    if (is_null($abstract)) {
        return $container;
    }

    return $container->get($abstract);
}
```

**使用方式：**
```php
// 获取容器实例
$container = app();

// 解析服务实例
$logger = app(LoggerInterface::class);
$config = app(ConfigRepository::class);
$session = app(SessionManager::class);

// 在类方法中使用
class MyController
{
    public function index()
    {
        $userService = app(UserService::class);
        return $userService->getAllUsers();
    }
}
```

**特性说明：**
- **单例容器：** 基于应用单例获取容器
- **自动解析：** 支持依赖注入自动解析
- **类型安全：** 支持强类型服务解析

#### config() 函数 - 配置访问

**功能：** 获取配置仓库实例或指定配置项

```php
function config(string $key = null, mixed $default = null): mixed
{
    $configRepo = app(ConfigRepository::class);

    if (is_null($key)) {
        return $configRepo;
    }

    return $configRepo->get($key, $default);
}
```

**使用方式：**
```php
// 获取配置仓库实例
$config = config();

// 获取简单配置项
$appName = config('app.name');
$debug = config('app.debug', false);

// 获取嵌套配置项
$dbHost = config('database.connections.mysql.host');
$cachePrefix = config('cache.stores.redis.options.prefix');

// 在应用中使用
class DatabaseService
{
    public function connect()
    {
        $host = config('database.connections.mysql.host');
        $database = config('database.connections.mysql.database');
        // 连接数据库...
    }
}
```

#### base_path() 函数 - 项目路径

**功能：** 获取项目根目录的绝对路径

```php
function base_path(string $path = ''): string
{
    // APP_ROOT 是在 index.php 和 jnm 脚本中定义的
    if (empty($path)) {
        return APP_ROOT;
    }

    // 确保路径分隔符正确 (e.g., config -> /config)
    return APP_ROOT . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
}
```

**使用方式：**
```php
// 获取项目根目录
$rootPath = base_path(); // /var/www/html

// 获取子目录路径
$configPath = base_path('config'); // /var/www/html/config
$appPath = base_path('app'); // /var/www/html/app
$storagePath = base_path('storage'); // /var/www/html/storage

// 获取文件路径
$envPath = base_path('.env');
$composerPath = base_path('composer.json');

// 在服务中使用
class FileService
{
    public function store($filename, $content)
    {
        $storagePath = base_path('storage');
        file_put_contents($storagePath . '/' . $filename, $content);
    }
}
```

#### e() 函数 - HTML 转义

**功能：** 对字符串进行 HTML 转义，防止 XSS 攻击

```php
function e($value, $doubleEncode = true)
{
    if ($value === null) {
        return '';
    }

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
}
```

**使用方式：**
```php
// 基础 HTML 转义
$userInput = '<script>alert("XSS")</script>';
$safeOutput = e($userInput); // &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;

// 在模板中使用
echo '<h1>' . e($pageTitle) . '</h1>';
echo '<p>' . e($articleContent) . '</p>';

// 处理 null 值
$emptyValue = null;
$safeEmpty = e($emptyValue); // ''

// 双重编码控制
$alreadyEncoded = '&lt;test&gt;';
$noDoubleEncode = e($alreadyEncoded, false); // &lt;test&gt;
```

**安全特性：**
- **XSS 防护：** 自动转义 HTML 特殊字符
- **UTF-8 支持：** 支持 Unicode 字符
- **null 处理：** 优雅处理 null 值
- **双重编码控制：** 可选的双重编码控制

#### env() 函数 - 环境变量

**功能：** 获取环境变量值，支持智能类型转换

```php
function env(string $key, mixed $default = null): mixed
{
    // 1. 优先检查 getenv()
    $value = getenv($key);

    // 2. 如果 getenv() 失败，再尝试 $_ENV
    if ($value === false) {
        $value = $_ENV[$key] ?? false;
    }

    // 3. 如果两者都失败，返回默认值
    if ($value === false) {
        return $default;
    }

    // 4. 智能类型转换
    if (is_string($value)) {
        $lowerValue = strtolower($value);
        if ($lowerValue === 'true') {
            return true;
        }
        if ($lowerValue === 'false') {
            return false;
        }
    }

    return $value;
}
```

**使用方式：**
```php
// 布尔值环境变量
$debug = env('APP_DEBUG', false); // true/false
$cacheEnabled = env('CACHE_ENABLED', true);

// 字符串环境变量
$appName = env('APP_NAME', 'JnmPHP');
$timezone = env('APP_TIMEZONE', 'UTC');

// 数字环境变量
$port = env('APP_PORT', 8000);
$timeout = env('TIMEOUT', 30);

// 复杂配置
$databaseUrl = env('DATABASE_URL');
$apiKey = env('API_KEY');

// 在配置文件中使用
return [
    'debug' => env('APP_DEBUG', false),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
    ],
];
```

**智能转换特性：**
- **优先级机制：** 优先使用 `getenv()`，回退到 `$_ENV`
- **布尔转换：** 自动转换 "true"/"false" 字符串为布尔值
- **默认值支持：** 提供默认值避免空值错误
- **类型保持：** 非布尔值保持原始类型

#### session() 函数 - 会话访问

**功能：** 获取会话管理器实例或访问会话数据

```php
function session($key = null, $default = null)
{
    $session = app(SessionManager::class);
    if (is_null($key)) {
        return $session;
    }
    return $session->get($key, $default);
}
```

**使用方式：**
```php
// 获取会话管理器实例
$sessionManager = session();
$sessionManager->put('key', 'value');

// 获取会话值
$user = session('user');
$theme = session('theme', 'default');

// 设置会话值（通过管理器）
session()->put('user_id', 123);
session()->flash('message', '操作成功');

// 检查会话是否存在
if (session()->has('user')) {
    $user = session('user');
}

// 在控制器中使用
class UserController
{
    public function login(Request $request)
    {
        // 验证用户...

        // 存储用户信息到会话
        session()->put('user', $user);
        session()->put('last_login', now());

        return redirect('/dashboard');
    }
}
```

#### csrf_token() 函数 - CSRF 令牌

**功能：** 获取当前请求的 CSRF 令牌

```php
function csrf_token(): string
{
    return session()->token();
}
```

**使用方式：**
```php
// 在表单中使用
echo '<form method="POST" action="/submit">';
echo csrf_field(); // 生成隐藏域
echo '<input type="text" name="name">';
echo '</form>';

// 在 AJAX 请求中使用
$token = csrf_token();
fetch('/api/data', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
});

// 在 JavaScript 中使用
echo '<script>';
echo 'window.csrfToken = "' . csrf_token() . '";';
echo '</script>';
```

#### csrf_field() 函数 - CSRF 隐藏域

**功能：** 生成包含 CSRF 令牌的表单隐藏域

```php
function csrf_field(): string
{
    $token = csrf_token();
    return "<input type=\"hidden\" name=\"_token\" value=\"{$token}\">";
}
```

**使用方式：**
```php
// 在表单中直接使用
echo '<form method="POST" action="/upload">';
echo csrf_field();
echo '<input type="file" name="file">';
echo '<button type="submit">上传</button>';
echo '</form>';

// 输出结果：
// <input type="hidden" name="_token" value="abc123def456...">

// 在模板引擎中使用
<form method="POST" action="{{ route('submit') }}">
    {!! csrf_field() !!}
    <input type="text" name="title">
    <button type="submit">提交</button>
</form>
```

## 使用指南

### 1. 函数引入

#### 自动加载机制

```php
// 在应用启动时自动加载
// index.php 或 bootstrap 文件中
require_once __DIR__ . '/kernel/Helpers/functions.php';
```

#### 手动引入

```php
// 在需要时手动引入
require_once APP_ROOT . '/kernel/Helpers/functions.php';
```

### 2. 项目初始化中的使用

#### 环境配置

```php
// config/app.php
return [
    'name' => env('APP_NAME', 'JnmPHP'),
    'debug' => env('APP_DEBUG', false),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'base_path' => base_path(),
];
```

#### 服务注册

```php
// AppServiceProvider
public function register(): void
{
    // 使用辅助函数获取配置
    $this->container->singleton(ConfigRepository::class, function () {
        return new ConfigRepository(base_path('config'));
    });
}
```

### 3. 控制器中的使用

#### 典型控制器示例

```php
class UserController extends BaseController
{
    public function profile()
    {
        // 获取当前用户
        $user = session('user');

        if (!$user) {
            return redirect('/login');
        }

        // 使用配置获取用户主题
        $theme = config('user.themes.' . $user->theme, 'default');

        return $this->view('profile', [
            'user' => $user,
            'theme' => $theme,
            'csrf_token' => csrf_token()
        ]);
    }

    public function update(Request $request)
    {
        // 获取服务
        $userService = app(UserService::class);

        // 更新用户信息
        $user = $userService->update($request->all());

        // 更新会话
        session()->put('user', $user);

        return redirect('/profile');
    }
}
```

### 4. 模板中的使用

#### Blade 模板示例

```php
{{-- resources/views/layout.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>{{ e($pageTitle) }}</title>
</head>
<body>
    <header>
        @if(session('user'))
            <span>欢迎, {{ e(session('user')->name) }}</span>
            <form method="POST" action="/logout">
                {!! csrf_field() !!}
                <button type="submit">退出</button>
            </form>
        @else
            <a href="/login">登录</a>
        @endif
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>
```

### 5. 中间件中的使用

#### CSRF 中间件

```php
class CsrfMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('POST')) {
            $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

            if (!$token || $token !== csrf_token()) {
                throw new HttpException(419, 'CSRF token mismatch');
            }
        }

        return $next($request);
    }
}
```

#### 认证中间件

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function handle($request, Closure $next)
    {
        if (!session('user')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
```

## 最佳实践

### 1. 环境变量使用

#### 配置分离

```php
// ✅ 推荐：使用环境变量配置不同环境
return [
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
    ],
];

// ❌ 避免：硬编码配置
return [
    'database' => [
        'host' => 'localhost', // 硬编码
        'password' => 'secret', // 敏感信息硬编码
    ],
];
```

#### 类型安全

```php
// ✅ 推荐：提供合适的默认值和类型
$debug = env('APP_DEBUG', false); // 布尔默认值
$timeout = env('TIMEOUT', 30); // 数字默认值

// ❌ 避免：不确定的类型
$value = env('SOME_VAR'); // 可能是字符串、布尔值或 null
```

### 2. 安全实践

#### HTML 转义

```php
// ✅ 推荐：始终转义用户输入
echo '<h1>' . e($userInput) . '</h1>';

// ✅ 推荐：在模板中使用转义
{!! e($content) !!}

// ❌ 避免：直接输出用户输入
echo '<h1>' . $userInput . '</h1>'; // XSS 风险
```

#### CSRF 保护

```php
// ✅ 推荐：所有表单都包含 CSRF 令牌
echo '<form method="POST" action="/submit">';
{!! csrf_field() !!}
echo '<input type="text" name="data">';
echo '</form>';

// ✅ 推荐：AJAX 请求包含 CSRF 令牌
fetch('/api/data', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrf_token()
    }
});

// ❌ 避免：没有 CSRF 保护的表单
echo '<form method="POST" action="/submit">';
echo '<input type="text" name="data">';
echo '</form>';
```

### 3. 服务访问

#### 依赖注入优先

```php
// ✅ 推荐：使用依赖注入
class UserController
{
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
}

// ✅ 可接受：使用辅助函数
class UserController
{
    public function index()
    {
        $userService = app(UserService::class);
        return $userService->getAll();
    }
}

// ❌ 避免：手动实例化
class UserController
{
    public function index()
    {
        $userService = new UserService(); // 失去依赖注入的优势
        return $userService->getAll();
    }
}
```

### 4. 配置访问

#### 配置缓存

```php
// ✅ 推荐：在应用启动时缓存配置
class AppServiceProvider
{
    public function boot(): void
    {
        // 预加载常用配置
        $this->appConfig = config('app');
        $this->dbConfig = config('database');
    }
}

// ✅ 可接受：按需访问配置
$timeout = config('api.timeout', 30);

// ❌ 避免：频繁访问相同配置
for ($i = 0; $i < 1000; $i++) {
    $timeout = config('api.timeout', 30); // 重复解析
}
```

### 5. 会话使用

#### 会话数据组织

```php
// ✅ 推荐：结构化会话数据
session()->put('user.id', $userId);
session()->put('user.name', $userName);
session()->put('preferences.theme', $theme);

// ✅ 推荐：使用对象存储复杂数据
session()->put('user', $userObject);

// ❌ 避免：扁平化会话数据
session()->put('user_id', $userId);
session()->put('user_name', $userName);
session()->put('theme', $theme);
```

## 性能优化

### 1. 函数调用优化

#### 减少重复调用

```php
// ✅ 推荐：缓存常用值
$config = config();
$base = base_path();

for ($i = 0; $i < 100; $i++) {
    $path = $base . '/cache/file_' . $i . '.txt';
    $debug = $config['app']['debug'];
}

// ❌ 避免：重复调用辅助函数
for ($i = 0; $i < 100; $i++) {
    $path = base_path() . '/cache/file_' . $i . '.txt';
    $debug = config('app.debug');
}
```

### 2. 容器解析优化

#### 服务缓存

```php
// ✅ 推荐：缓存服务实例
class MyClass
{
    private $userService;

    public function __construct()
    {
        $this->userService = app(UserService::class);
    }

    public function method1()
    {
        return $this->userService->doSomething();
    }

    public function method2()
    {
        return $this->userService->doOtherThing();
    }
}

// ❌ 避免：重复解析服务
class MyClass
{
    public function method1()
    {
        $userService = app(UserService::class); // 每次都解析
        return $userService->doSomething();
    }

    public function method2()
    {
        $userService = app(UserService::class); // 每次都解析
        return $userService->doOtherThing();
    }
}
```

## 扩展和自定义

### 1. 添加新的辅助函数

#### 扩展 functions.php

```php
// 在 functions.php 文件末尾添加
if (!function_exists('cache')) {
    /**
     * 获取缓存实例或缓存值
     */
    function cache($key = null, $default = null)
    {
        $cache = app(CacheManager::class);

        if (is_null($key)) {
            return $cache;
        }

        return $cache->get($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * 渲染视图
     */
    function view($template, $data = [])
    {
        return app(ViewFactory::class)->make($template, $data);
    }
}
```

### 2. 扩展 Str 工具类

#### 添加新方法

```php
class Str
{
    /**
     * 驼峰转下划线
     */
    public static function snake($value, $delimiter = '_')
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return $value;
    }

    /**
     * 下划线转驼峰
     */
    public static function camel($value)
    {
        return lcfirst(static::studly($value));
    }

    /**
     * 首字母大写
     */
    public static function studly($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return str_replace(' ', '', $value);
    }
}
```

### 3. 自定义函数库

#### 创建专用函数库

```php
// kernel/Helpers/ArrayHelper.php
class ArrayHelper
{
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    public static function undot($array)
    {
        $result = [];

        foreach ($array as $key => $value) {
            static::set($result, $key, $value);
        }

        return $result;
    }

    protected static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}
```

## 故障排除

### 1. 常见问题

#### 函数未定义

```php
// 问题：函数未定义错误
Call to undefined function config()

// 解决：确保 functions.php 已加载
require_once APP_ROOT . '/kernel/Helpers/functions.php';

// 或检查自动加载配置
// composer.json 中添加：
// "autoload": {
//     "files": [
//         "kernel/Helpers/functions.php"
//     ]
// }
```

#### 环境变量获取失败

```php
// 问题：环境变量获取不到
$debug = env('APP_DEBUG'); // 返回 null

// 解决：检查环境变量来源
// 1. 检查 .env 文件是否存在
// 2. 检查 getenv() 和 $_ENV
var_dump(getenv('APP_DEBUG'));
var_dump($_ENV['APP_DEBUG'] ?? null);

// 3. 提供默认值
$debug = env('APP_DEBUG', false);
```

#### 容器解析失败

```php
// 问题：服务解析失败
$service = app('unknown.service'); // 抛出异常

// 解决：检查服务绑定
if (app()->bound('service.name')) {
    $service = app('service.name');
} else {
    $service = new DefaultService();
}
```

### 2. 调试技巧

#### 函数调用追踪

```php
// 创建调试版本的辅助函数
function debug_app($abstract = null)
{
    echo "解析服务: " . ($abstract ?? 'container') . "\n";
    $result = app($abstract);
    echo "解析结果: " . get_class($result) . "\n";
    return $result;
}
```

#### 配置来源检查

```php
// 创建配置调试函数
function debug_config($key = null)
{
    if ($key) {
        $value = config($key);
        echo "配置项 [{$key}]: " . var_export($value, true) . "\n";
    } else {
        echo "所有配置:\n";
        var_dump(config()->all());
    }
}
```

这个辅助函数系统为 JnmPHP 框架提供了便捷的全局函数接口，简化了常见开发操作，提高了开发效率和代码可读性。通过合理使用这些辅助函数，开发者可以更专注于业务逻辑的实现。