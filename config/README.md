# 配置文件 (Configuration Files)

此目录包含 JnmPHP 框架的所有配置文件。配置系统基于 PHP 数组和环境变量，提供了灵活的应用程序配置管理。

## 目录结构

```
config/
├── database.php       # 数据库连接配置
├── logging.php        # 日志记录配置
├── session.php        # 会话管理配置
├── providers.php      # 服务提供者注册配置
└── README.md          # 本文档
```

## 配置系统架构

### 配置仓库 (ConfigRepository)

`Kernel\Config\ConfigRepository` 是配置系统的核心，提供以下功能：

- **自动加载：** 自动加载 config 目录下的所有 PHP 配置文件
- **点号访问：** 支持使用点号语法访问嵌套配置项
- **数组接口：** 实现 `ArrayAccess` 接口，支持数组式访问
- **默认值：** 支持为配置项设置默认值

### 环境变量支持

框架使用 `.env` 文件管理环境特定配置：

```php
// 配置文件中
'driver' => env('DB_DRIVER', 'mysql'),

// .env 文件中
DB_DRIVER=mysql
```

### 配置访问方式

```php
// 通过 ConfigRepository
$config = app('config');
$value = $config->get('database.connections.mysql.host');

// 通过辅助函数
$value = config('database.connections.mysql.host', 'default_value');

// 通过数组访问
$config = app('config');
$value = $config['database']['connections']['mysql']['host'];
```

## 配置文件详解

### 1. database.php - 数据库配置

**功能：** 配置数据库连接参数，支持多种数据库驱动。

#### 配置结构

```php
return [
    'default' => env('DB_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'driver'    => env('DB_DRIVER', 'mysql'),
            'host'      => env('DB_HOST', 'localhost'),
            'database'  => env('DB_DATABASE', 'aaa'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', 'root'),
            'charset'   => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix'    => env('DB_PREFIX', ''),
        ],
    ],
];
```

#### 配置项说明

| 配置项 | 环境变量 | 默认值 | 说明 |
|--------|----------|--------|------|
| `default` | `DB_CONNECTION` | `default` | 默认连接名称 |
| `driver` | `DB_DRIVER` | `mysql` | 数据库驱动类型 |
| `host` | `DB_HOST` | `localhost` | 数据库主机地址 |
| `database` | `DB_DATABASE` | `aaa` | 数据库名称 |
| `username` | `DB_USERNAME` | `root` | 数据库用户名 |
| `password` | `DB_PASSWORD` | `root` | 数据库密码 |
| `charset` | `DB_CHARSET` | `utf8` | 字符集 |
| `collation` | `DB_COLLATION` | `utf8_unicode_ci` | 排序规则 |
| `prefix` | `DB_PREFIX` | `''` | 表前缀 |

#### 支持的数据库驱动

- **mysql** - MySQL / MariaDB
- **pgsql** - PostgreSQL
- **sqlite** - SQLite
- **sqlsrv** - SQL Server

#### 使用示例

```php
// 获取数据库配置
$dbConfig = config('database.connections.default');

// 在模型中使用
class User extends BaseModel
{
    protected $table = 'users'; // 使用配置的数据库连接
}
```

#### 多连接配置

```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'forge'),
    ],
    'redis' => [
        'driver' => 'redis',
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
    ],
],
```

---

### 2. logging.php - 日志配置

**功能：** 配置日志记录系统，支持多种日志驱动和通道。

#### 配置结构

```php
return [
    'default' => env('APP_LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => APP_ROOT . '/logs/jnm.log',
            'level' => env('APP_LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'single' => [
            'driver' => 'single',
            'path' => APP_ROOT . '/logs/jnm.log',
            'level' => env('APP_LOG_LEVEL', 'debug'),
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => Monolog\Handler\StreamHandler::class,
            'with' => [
                'stream' => 'php://stderr',
            ],
            'level' => 'debug',
        ],
    ],
];
```

#### 日志通道详解

##### Stack 通道
- **驱动：** `stack`
- **功能：** 将日志消息堆叠到多个通道
- **用途：** 组合多个日志通道，实现日志的多重输出

##### Daily 通道
- **驱动：** `daily`
- **功能：** 按天创建日志文件，自动清理旧文件
- **配置：**
  - `path` - 日志文件路径
  - `level` - 最低日志级别
  - `days` - 日志文件保留天数

##### Single 通道
- **驱动：** `single`
- **功能：** 所有日志写入单个文件
- **用途：** 简单的日志记录，适合小型应用

##### Stderr 通道
- **驱动：** `monolog`
- **功能：** 输出到标准错误流
- **用途：** 开发环境调试，Docker 容器日志

#### 日志级别

| 级别 | 常量 | 描述 |
|------|------|------|
| DEBUG | `Monolog\Level::Debug` | 调试信息 |
| INFO | `Monolog\Level::Info` | 一般信息 |
| NOTICE | `Monolog\Level::Notice` | 注意信息 |
| WARNING | `Monolog\Level::Warning` | 警告信息 |
| ERROR | `Monolog\Level::Error` | 错误信息 |
| CRITICAL | `Monolog\Level::Critical` | 严重错误 |
| ALERT | `Monolog\Level::Alert` | 警报 |
| EMERGENCY | `Monolog\Level::Emergency` | 紧急情况 |

#### 使用示例

```php
// 在控制器或服务中使用
class MyController extends BaseController
{
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function someMethod()
    {
        $this->logger->info('用户操作', ['user_id' => 123]);
        $this->logger->error('系统错误', ['error' => $e->getMessage()]);
    }
}
```

#### 环境配置

```bash
# .env 文件
APP_LOG_CHANNEL=daily
APP_LOG_LEVEL=info
```

---

### 3. session.php - 会话配置

**功能：** 配置用户会话管理，支持多种存储驱动。

#### 配置结构

```php
return [
    'driver' => env('SESSION_DRIVER', 'native'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'cookie' => env('SESSION_COOKIE', 'jnm_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', false),
    'http_only' => true,
    'database' => [
        'connection' => env('DB_CONNECTION', 'default'),
        'table' => 'sessions',
    ],
    'redis' => [
        'connection' => env('REDIS_CONNECTION', 'default'),
        'prefix' => env('SESSION_REDIS_PREFIX', 'session:'),
        'lifetime' => env('SESSION_REDIS_LIFETIME', 1200),
    ],
];
```

#### 会话驱动

##### Native 驱动
- **说明：** 使用 PHP 原生的 session 机制
- **存储：** 服务器文件系统
- **适用：** 单服务器应用，开发环境

##### Database 驱动
- **说明：** 将会话数据存储在数据库中
- **优势：** 跨服务器共享会话，便于管理
- **表结构：**
```sql
CREATE TABLE sessions (
    id VARCHAR(64) PRIMARY KEY,
    user_id INT NULL,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL
);
```

##### Redis 驱动
- **说明：** 使用 Redis 存储会话数据
- **优势：** 高性能，支持分布式架构
- **适用：** 高并发、分布式应用

#### 配置项说明

| 配置项 | 环境变量 | 默认值 | 说明 |
|--------|----------|--------|------|
| `driver` | `SESSION_DRIVER` | `native` | 会话驱动类型 |
| `lifetime` | `SESSION_LIFETIME` | `120` | 会话有效期（分钟） |
| `cookie` | `SESSION_COOKIE` | `jnm_session` | Cookie 名称 |
| `path` | - | `/` | Cookie 作用路径 |
| `domain` | `SESSION_DOMAIN` | `null` | Cookie 作用域 |
| `secure` | `SESSION_SECURE_COOKIE` | `false` | 仅 HTTPS 传输 |
| `http_only` | - | `true` | 禁止 JS 访问 |

#### 安全配置

```bash
# 生产环境推荐配置
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.yourdomain.com
SESSION_LIFETIME=30
```

#### 使用示例

```php
// 在控制器中使用会话
class UserController extends BaseController
{
    public function login(Request $request)
    {
        // 验证用户...

        // 设置会话
        session(['user_id' => $user->id, 'user_name' => $user->name]);

        return redirect('/dashboard');
    }

    public function logout()
    {
        // 清除会话
        session()->flush();
        return redirect('/login');
    }
}
```

---

### 4. providers.php - 服务提供者配置

**功能：** 注册框架的核心服务提供者，定义服务的加载顺序。

#### 配置结构

```php
return [
    ConfigServiceProvider::class,      // 1. 配置服务（最先加载）
    AppServiceProvider::class,         // 2. 应用核心服务
    LogServiceProvider::class,         // 3. 日志服务
    EventServiceProvider::class,       // 4. 事件系统
    DatabaseServiceProvider::class,    // 5. 数据库连接
    RouteServiceProvider::class,       // 6. 路由系统
    ViewServiceProvider::class,        // 7. 视图引擎
    SessionServiceProvider::class,     // 8. 会话管理
];
```

#### 服务提供者列表

| 服务提供者 | 顺序 | 主要功能 |
|------------|------|----------|
| `ConfigServiceProvider` | 1 | 配置管理，提供配置访问服务 |
| `AppServiceProvider` | 2 | 应用核心服务（事件、异常、翻译、验证） |
| `LogServiceProvider` | 3 | 日志记录服务 |
| `EventServiceProvider` | 4 | 事件系统和订阅者管理 |
| `DatabaseServiceProvider` | 5 | 数据库连接和 Eloquent ORM |
| `RouteServiceProvider` | 6 | 路由系统和控制器加载 |
| `ViewServiceProvider` | 7 | Blade 模板引擎 |
| `SessionServiceProvider` | 8 | 会话管理服务 |

#### 加载顺序的重要性

1. **ConfigServiceProvider** 必须最先加载，为其他服务提供配置支持
2. **LogServiceProvider** 早期加载，确保日志服务可用
3. **DatabaseServiceProvider** 在路由前加载，确保模型可用
4. **SessionServiceProvider** 最后加载，依赖其他服务的初始化

#### 自定义服务提供者

```php
// 添加自定义服务提供者
return [
    // ... 现有服务提供者
    CustomServiceProvider::class,    // 自定义服务
];
```

## 环境配置

### .env 文件

`.env` 文件用于存储环境特定的配置变量：

```bash
# 应用基础配置
APP_NAME=JnmPHP
APP_DEBUG=true
APP_TIMEZONE="Asia/Shanghai"
APP_LOCALE=zh_CN

# 日志配置
APP_LOG_CHANNEL=daily
APP_LOG_LEVEL=debug

# 数据库配置
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_DATABASE=jnmphp
DB_USERNAME=root
DB_PASSWORD=password
DB_CHARSET=utf8
DB_COLLATION=utf8_unicode_ci

# 会话配置
SESSION_DRIVER=native
SESSION_LIFETIME=120
SESSION_COOKIE=jnm_session
```

### 环境变量优先级

1. **环境变量** - 最高优先级
2. **.env 文件** - 次优先级
3. **配置文件默认值** - 最低优先级

### 环境特定配置

#### 开发环境 (.env.development)
```bash
APP_DEBUG=true
APP_LOG_LEVEL=debug
SESSION_DRIVER=native
```

#### 生产环境 (.env.production)
```bash
APP_DEBUG=false
APP_LOG_LEVEL=error
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
```

## 配置最佳实践

### 1. 安全配置

```php
// database.php - 敏感信息使用环境变量
'password' => env('DB_PASSWORD'),

// session.php - 生产环境安全配置
'secure' => env('APP_ENV') === 'production',
'http_only' => true,
```

### 2. 性能优化

```php
// logging.php - 生产环境优化
'default' => env('APP_DEBUG') ? 'stack' : 'daily',
'level' => env('APP_DEBUG') ? 'debug' : 'warning',

// session.php - 高并发环境
'driver' => env('APP_ENV') === 'production' ? 'redis' : 'native',
```

### 3. 可维护性

```php
// 使用有意义的默认值
'driver' => env('DB_DRIVER', 'mysql'),

// 提供详细的配置注释
/*
|--------------------------------------------------------------------------
| 数据库主机地址
|--------------------------------------------------------------------------
|
| 数据库服务器的 IP 地址或域名
|
*/
'host' => env('DB_HOST', 'localhost'),
```

### 4. 环境隔离

```bash
# 开发环境
DB_DATABASE=jnmphp_dev

# 测试环境
DB_DATABASE=jnmphp_test

# 生产环境
DB_DATABASE=jnmphp_prod
```

## 配置验证

### 启动时验证

```php
// 在 AppServiceProvider 中添加配置验证
public function boot(): void
{
    // 验证必需的配置项
    $requiredConfigs = [
        'database.default',
        'session.driver',
        'logging.default'
    ];

    foreach ($requiredConfigs as $config) {
        if (!config()->has($config)) {
            throw new \RuntimeException("Missing required config: {$config}");
        }
    }
}
```

### 配置测试

```php
class ConfigTest extends TestCase
{
    public function testDatabaseConfig()
    {
        $this->assertNotNull(config('database.connections.default'));
        $this->assertNotEmpty(config('database.connections.default.host'));
    }
}
```

## 配置缓存

### 生产环境缓存

```bash
# 生成配置缓存（预留功能）
php jnm config:cache

# 清除配置缓存
php jnm config:clear
```

### 开发环境

```php
// 开发环境实时加载配置，无需缓存
if (env('APP_DEBUG')) {
    // 每次请求重新加载配置
    $this->refreshConfiguration();
}
```

## 故障排除

### 常见问题

#### 1. 配置文件未找到
```
错误: Failed to load configuration file
解决: 确保 config 目录存在且包含 .php 文件
```

#### 2. 环境变量未设置
```
错误: Database connection failed
解决: 检查 .env 文件中的 DB_* 配置
```

#### 3. 权限问题
```
错误: Permission denied when writing log files
解决: 确保 logs 目录有写入权限
```

### 调试技巧

```php
// 查看所有配置
dump(config()->all());

// 检查特定配置
dump(config('database'));

// 检查环境变量
dump(env('DB_HOST'));
```

这个配置系统为 JnmPHP 框架提供了灵活而强大的配置管理能力，支持环境隔离和安全配置，适用于从开发到生产的各种部署场景。