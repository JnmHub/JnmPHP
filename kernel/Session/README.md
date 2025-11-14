# 会话管理系统

Session 命名空间为 JnmPHP 框架提供了完整的会话管理功能。它采用驱动模式设计，支持多种存储后端，提供了丰富的会话操作方法和安全特性，包括 CSRF 保护和会话过期管理。

## 概述

会话管理系统是 JnmPHP 框架的核心组件之一，负责管理用户会话数据的存储、检索和生命周期。通过统一的接口设计，开发者可以轻松切换不同的存储驱动，而无需修改业务代码。

## 核心功能

- **多驱动支持**：支持原生 PHP Session、数据库存储和 Redis 存储
- **统一接口**：所有驱动实现相同的 `SessionDriverInterface` 接口
- **CSRF 保护**：内置 CSRF Token 生成和验证机制
- **会话过期**：支持全局和单个键的过期时间设置
- **安全特性**：会话 ID 重新生成、安全 Cookie 设置
- **自动管理**：自动启动会话和清理过期数据
- **跨数据库兼容**：数据库驱动支持多种数据库类型
- **调试支持**：开发环境下的表结构自动检查和创建
- **滑动过期**：Redis 驱动支持读取时自动续期

## 核心组件

### SessionManager.php

会话管理器，负责驱动选择和方法代理。

#### 主要方法

**构造方法：**
```php
public function __construct(Container $app)
```

**核心方法：**
- `start(): void` - 启动会话
- `driver(): SessionDriverInterface` - 获取驱动实例
- `__call($method, $arguments)` - 代理调用驱动方法

#### 支持的驱动

- **native**：使用 PHP 内建 Session 机制（默认）
- **database**：使用数据库存储会话数据
- **redis**：使用 Redis 存储会话数据（高性能，适合分布式环境）

### SessionDriverInterface.php

会话驱动接口，定义了所有驱动必须实现的标准方法。

#### 核心方法

**会话生命周期：**
- `start(): bool` - 启动会话
- `save(): void` - 保存会话状态
- `destroy(): void` - 销毁整个会话
- `isStarted(): bool` - 检查会话是否已启动

**会话 ID 管理：**
- `id(): ?string` - 获取当前会话 ID
- `regenerate(bool $deleteOldSession = false): void` - 重新生成会话 ID

**数据操作：**
- `all(): array` - 获取所有会话数据
- `has(string $key): bool` - 检查键是否存在
- `get(string $key, mixed $default = null): mixed` - 获取会话值
- `set(string $key, mixed $value, ?int $ttl = null): void` - 设置会话值
- `forget(string $key): void` - 删除指定键
- `clear(): void` - 清空会话数据

**CSRF 保护：**
- `token(): string` - 获取 CSRF Token
- `regenerateToken(): string` - 重新生成 CSRF Token

**过期管理：**
- `expire(string $key, int $ttl): void` - 设置键的过期时间
- `isExpired(string $key): bool` - 检查键是否过期

### NativeSessionDriver.php

原生 PHP Session 驱动，基于 PHP 内建的 Session 机制。

#### 特性

- **配置灵活**：支持完整的 Session Cookie 配置
- **安全设置**：支持 secure、httponly、samesite 等安全选项
- **过期机制**：支持单个键的过期时间设置
- **内存优化**：惰性启动，仅在需要时启动 Session

#### 配置选项

```php
$config = [
    'lifetime'   => 120,          // 生命周期（分钟）
    'path'       => '/',           // Cookie 路径
    'domain'     => '',            // Cookie 域名
    'secure'     => false,         // 仅 HTTPS
    'http_only'  => true,          // 仅 HTTP
    'same_site'  => 'Lax',         // SameSite 策略
    'cookie'     => 'jnm_session', // Cookie 名称
];
```

### DatabaseSessionDriver.php

数据库会话驱动，将会话数据存储在数据库中。

#### 特性

- **跨数据库兼容**：支持 MySQL、SQLite、PostgreSQL、SQL Server
- **自动建表**：开发环境下自动检查和创建 sessions 表
- **实时写入**：数据变更立即写入数据库
- **JSON 存储**：使用 JSON 格式存储会话数据
- **过期管理**：支持会话级别的过期时间

#### 表结构

```sql
CREATE TABLE `sessions` (
    `id`            VARCHAR(64) PRIMARY KEY,
    `user_id`       INTEGER NULL,
    `payload`       LONGTEXT,           -- JSON 格式的会话数据
    `last_activity` INTEGER,            -- 最后活动时间
    `expires_at`    INTEGER NULL        -- 过期时间（Unix 时间戳）
);
```

### RedisSessionDriver.php

Redis 会话驱动，使用 Redis 存储会话数据，适合高并发和分布式环境。

#### 特性

- **高性能**：基于内存的存储，读写速度极快
- **分布式支持**：支持多服务器共享会话数据
- **滑动过期**：支持读取时自动续期
- **数据结构**：使用 Redis Hash 存储会话数据
- **自动清理**：利用 Redis 的 TTL 机制自动清理过期数据
- **JSON 编码**：使用 JSON 格式序列化复杂数据

#### 配置选项

```php
'redis' => [
    'connection' => env('REDIS_CONNECTION', 'default'),  // Redis 连接名称
    'prefix' => env('SESSION_REDIS_PREFIX', 'session:'), // 键前缀
    'lifetime' => env('SESSION_REDIS_LIFETIME', 1200),   // 生命周期（秒）
],
```

#### Redis 数据结构

```
Key: session:{session_id}
Type: Hash
Fields:
    - user_id: "123"
    - name: "John Doe"
    - _token: "abc123..."
    - temp_data: "{\"value\":\"data\",\"expires_at\":1640995200}"
TTL: 设置为会话生命周期
```

## 使用示例

### 基本使用

```php
// 获取会话管理器实例
$session = app('session');

// 启动会话（通常在框架启动时自动完成）
$session->start();

// 设置会话数据
$session->set('user_id', 123);
$session->set('user_name', 'John Doe');

// 获取会话数据
$userId = $session->get('user_id');
$userName = $session->get('user_name', 'Guest');

// 检查键是否存在
if ($session->has('user_id')) {
    // 用户已登录
}

// 删除会话数据
$session->forget('user_name');

// 获取所有会话数据
$allData = $session->all();
```

### 带过期时间的设置

```php
// 设置 5 分钟后过期的数据
$session->set('verification_code', '123456', 300);

// 设置单独的过期时间
$session->set('temp_data', 'value');
$session->expire('temp_data', 600); // 10 分钟后过期

// 检查是否过期
if ($session->isExpired('verification_code')) {
    // 验证码已过期
}
```

### CSRF 保护

```php
// 获取 CSRF Token（自动生成）
$token = $session->token();

// 在表单中使用
echo '<input type="hidden" name="_token" value="' . $token . '">';

// 验证 CSRF Token
if ($request->input('_token') !== $session->token()) {
    // CSRF 攻击检测
    throw new CsrfTokenMismatchException();
}

// 重新生成 Token（在敏感操作后）
$newToken = $session->regenerateToken();
```

### 用户登录示例

```php
class AuthController extends BaseController
{
    public function login(Request $request): JsonResponse
    {
        $session = app('session');

        // 验证用户凭据
        $user = User::where('email', $request->input('email'))
                   ->where('password', $request->input('password'))
                   ->first();

        if ($user) {
            // 登录成功，设置会话数据
            $session->regenerate(true); // 重新生成会话 ID
            $session->set('user_id', $user->id);
            $session->set('user_name', $user->name);
            $session->set('login_time', time());

            return JsonResponse::success(['message' => '登录成功']);
        } else {
            return JsonResponse::error('用户名或密码错误');
        }
    }

    public function logout(): JsonResponse
    {
        $session = app('session');

        // 清空用户数据
        $session->forget(['user_id', 'user_name', 'login_time']);

        // 或者完全销毁会话
        $session->destroy();

        return JsonResponse::success(['message' => '退出成功']);
    }
}
```

### 中间件集成

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $session = app('session');

        // 检查用户是否已登录
        if (!$session->has('user_id')) {
            return JsonResponse::error('请先登录', 401);
        }

        // 延长会话有效期
        $session->expire('user_id', config('session.lifetime') * 60);

        return $next($request);
    }
}

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $session = app('session');

        // 对状态更改请求验证 CSRF Token
        if (in_array($request->method, ['POST', 'PUT', 'DELETE'])) {
            $token = $request->input('_token');
            if (!$token || $token !== $session->token()) {
                return JsonResponse::error('CSRF Token 验证失败', 419);
            }
        }

        return $next($request);
    }
}
```

## 配置说明

### 会话配置文件

```php
// config/session.php
return [
    // 驱动类型：native, database, redis
    'driver' => env('SESSION_DRIVER', 'native'),

    // 会话生命周期（分钟）
    'lifetime' => env('SESSION_LIFETIME', 1440),

    // Cookie 配置
    'cookie' => env('SESSION_COOKIE', 'jnm_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', false),
    'http_only' => true,
    'same_site' => 'Lax',

    // 数据库配置
    'database' => [
        'connection' => env('DB_CONNECTION', 'default'),
        'table' => 'sessions',
    ],

    // Redis 配置
    'redis' => [
        'connection' => env('REDIS_CONNECTION', 'default'),
        'prefix' => env('SESSION_REDIS_PREFIX', 'session:'),
        'lifetime' => env('SESSION_REDIS_LIFETIME', 1200),
    ],
];
```

### 环境变量配置

```env
# .env 文件
SESSION_DRIVER=native
SESSION_LIFETIME=1440
SESSION_COOKIE=jnm_session
SESSION_SECURE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=Lax

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=jnmphp
DB_USERNAME=root
DB_PASSWORD=

# Redis 配置
REDIS_CONNECTION=default
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# Redis Session 配置
SESSION_REDIS_PREFIX=session:
SESSION_REDIS_LIFETIME=1200
```

## 驱动切换

### 切换到数据库驱动

```php
// 1. 修改配置文件
// config/session.php
'driver' => 'database',

// 2. 配置数据库连接
'database' => [
    'table' => 'sessions',
    'connection' => 'mysql',
],

// 3. 运行迁移（如果需要）
// 框架会在 DEBUG 模式下自动创建表
```

### 切换到 Redis 驱动

```php
// 1. 修改环境变量
// .env 文件
SESSION_DRIVER=redis

// 2. 配置 Redis 连接
// config/session.php
'redis' => [
    'connection' => 'default',  // 对应 config/redis.php 中的连接
    'prefix' => 'session:',     // Redis 键前缀
    'lifetime' => 1200,         // Redis 生命周期（秒）
],

// 3. 确保 Redis 服务运行并可连接
```

### 自定义驱动

```php
class RedisSessionDriver implements SessionDriverInterface
{
    public function __construct(array $config = [])
    {
        // Redis 连接初始化
    }

    // 实现所有接口方法
    public function start(): bool { /* ... */ }
    public function get(string $key, mixed $default = null): mixed { /* ... */ }
    // ... 其他方法
}

// 在 SessionManager 中注册
case 'redis':
    $this->driver = new RedisSessionDriver($app);
    break;
```

## 安全最佳实践

### 1. 会话安全配置

```php
// 生产环境安全配置
return [
    'secure' => true,           // 仅 HTTPS
    'http_only' => true,        // 仅 HTTP
    'same_site' => 'Strict',    // 严格的 SameSite 策略
    'lifetime' => 30,           // 较短的生命周期
];
```

### 2. 会话固定攻击防护

```php
// 在权限变更或敏感操作后重新生成会话 ID
$session->regenerate(true); // 删除旧会话
```

### 3. 会话数据清理

```php
// 定期清理过期的会话数据
class SessionCleanupCommand
{
    public function handle(): void
    {
        // 清理数据库中的过期会话
        DB::table('sessions')
           ->where('expires_at', '<', now())
           ->delete();
    }
}
```

### 4. 敏感数据保护

```php
// 避免在会话中存储敏感信息
$session->set('user_id', $user->id);           // ✓ 好
$session->set('password', $user->password);    // ✗ 不好

// 使用过期时间限制临时数据的有效期
$session->set('reset_token', $token, 600); // 10 分钟后过期
```

## 性能优化

### 1. 原生驱动优化

```php
// 启用会话写入锁
ini_set('session.use_strict_mode', 1);

// 优化垃圾回收
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 1000);
```

### 2. 数据库驱动优化

```php
// 为会话表添加索引
Schema::table('sessions', function ($table) {
    $table->index('last_activity');
    $table->index('expires_at');
    $table->index('user_id');
});

// 使用连接池
'connection' => 'session_pool',
```

### 3. 缓存集成

```php
// 结合缓存使用会话数据
class CachedSessionManager extends SessionManager
{
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "session:{$this->id()}:{$key}";

        return Cache::remember($cacheKey, 300, function () use ($key, $default) {
            return parent::get($key, $default);
        });
    }
}
```

## 故障排除

### 常见问题

1. **会话数据丢失**
   ```php
   // 检查会话是否启动
   if (!$session->isStarted()) {
       $session->start();
   }

   // 检查 Cookie 设置
   var_dump($_COOKIE[config('session.cookie')]);
   ```

2. **数据库驱动连接失败**
   ```php
   // 检查数据库连接
   try {
       DB::connection()->getPdo();
   } catch (\Exception $e) {
       error_log("数据库连接失败: " . $e->getMessage());
   }

   // 检查表是否存在
   if (!Schema::hasTable('sessions')) {
       Schema::create('sessions', function ($table) { /* ... */ });
   }
   ```

3. **CSRF Token 不匹配**
   ```php
   // 调试 Token 生成
   error_log("Current Token: " . $session->token());
   error_log("Submitted Token: " . $request->input('_token'));

   // 检查会话状态
   if (!$session->isStarted()) {
       $session->start();
   }
   ```

4. **会话过期问题**
   ```php
   // 检查过期时间设置
   $lifetime = config('session.lifetime') * 60;
   error_log("会话生命周期: " . $lifetime . " 秒");

   // 检查单个键的过期状态
   if ($session->isExpired('user_id')) {
       error_log("用户 ID 已过期");
   }
   ```

### 调试技巧

```php
// 启用详细日志
if (DEBUG) {
    error_log("会话驱动: " . config('session.driver'));
    error_log("会话 ID: " . $session->id());
    error_log("会话数据: " . json_encode($session->all()));
}

// 在开发环境下显示会话信息
if (DEBUG && isset($_GET['debug_session'])) {
    var_dump([
        'driver' => config('session.driver'),
        'id' => $session->id(),
        'data' => $session->all(),
        'started' => $session->isStarted(),
    ]);
}
```

## 扩展指南

### 会话事件监听

```php
class SessionEventSubscriber implements EventSubscriberInterface
{
    public function subscribe(): array
    {
        return [
            'session.started' => 'onSessionStart',
            'session.destroyed' => 'onSessionDestroy',
        ];
    }

    public function onSessionStart(): void
    {
        Log::info('会话启动: ' . session_id());
    }

    public function onSessionDestroy(): void
    {
        Log::info('会话销毁: ' . session_id());
    }
}
```

### 会话存储扩展

```php
class FileSessionDriver implements SessionDriverInterface
{
    private string $savePath;

    public function __construct(array $config = [])
    {
        $this->savePath = $config['save_path'] ?? storage_path('sessions');
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $data = $this->all();
        $data[$key] = $value;

        file_put_contents(
            $this->getFilePath(),
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
    }

    private function getFilePath(): string
    {
        return $this->savePath . '/sess_' . $this->id();
    }

    // 实现其他接口方法...
}
```

### 多租户会话

```php
class TenantSessionManager extends SessionManager
{
    protected function resolveDriver(string $driver): SessionDriverInterface
    {
        $tenantId = $this->app->make('current_tenant')->id;

        switch ($driver) {
            case 'database':
                return new DatabaseSessionDriver([
                    'table' => "tenant_{$tenantId}_sessions"
                ]);
            default:
                return parent::resolveDriver($driver);
        }
    }
}
```