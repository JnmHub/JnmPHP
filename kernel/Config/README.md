# 配置系统 (Configuration System)

此目录包含 JnmPHP 框架的核心配置系统。`ConfigRepository` 类提供了统一的配置管理接口，支持多种访问方式和灵活的配置操作。

## 目录结构

```
kernel/Config/
├── ConfigRepository.php    # 配置仓库核心类
└── README.md               # 本文档
```

## 核心组件

### ConfigRepository - 配置仓库

`ConfigRepository` 是配置系统的核心类，提供以下主要功能：

- **自动加载：** 自动扫描并加载配置目录下的所有 PHP 配置文件
- **点号访问：** 支持使用点号语法访问嵌套配置项
- **数组接口：** 实现 `ArrayAccess` 接口，支持数组式访问
- **默认值支持：** 为配置项提供默认值机制
- **动态设置：** 支持运行时动态设置配置值

## 功能详解

### 1. 配置文件加载

#### 自动发现机制

```php
// 构造函数自动加载配置目录
$config = new ConfigRepository('/path/to/config');

// 自动加载的文件：
// - config/database.php → $config['database']
// - config/app.php → $config['app']
// - config/session.php → $config['session']
```

#### 加载实现

```php
protected function loadConfigurations(string $path): void
{
    if (!is_dir($path)) {
        return; // 目录不存在时静默返回
    }

    foreach (glob($path . '/*.php') as $file) {
        $key = basename($file, '.php'); // 文件名作为配置键
        $this->config[$key] = require $file; // 加载配置数组
    }
}
```

#### 配置文件格式

```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host' => 'localhost',
            'database' => 'myapp',
            'username' => 'root',
            'password' => 'password',
        ],
    ],
];
```

### 2. 配置访问方法

#### get() 方法 - 获取配置值

```php
// 基础访问
$value = $config->get('database.default');

// 嵌套访问（点号语法）
$host = $config->get('database.connections.mysql.host');

// 带默认值
$debug = $config->get('app.debug', false);

// 不存在的配置返回默认值
$unknown = $config->get('unknown.key', 'default_value');
```

#### 实现原理

```php
public function get(string $key, mixed $default = null): mixed
{
    // 简单键名直接访问
    if (!str_contains($key, '.')) {
        return $this->config[$key] ?? $default;
    }

    // 复杂键名分段访问
    $segments = explode('.', $key);
    $data = $this->config;

    foreach ($segments as $segment) {
        if (!is_array($data) || !array_key_exists($segment, $data)) {
            return $default;
        }
        $data = $data[$segment];
    }

    return $data;
}
```

#### has() 方法 - 检查配置存在

```php
// 检查配置项是否存在
if ($config->has('database.default')) {
    // 配置存在
}

// 检查嵌套配置
if ($config->has('database.connections.mysql.host')) {
    // 嵌套配置存在
}

// 不存在的配置
$exists = $config->has('unknown.key'); // false
```

### 3. 配置设置方法

#### set() 方法 - 设置配置值

```php
// 设置简单配置
$config->set('app.debug', true);

// 设置嵌套配置
$config->set('database.connections.mysql.host', 'new-host');

// 设置新配置项
$config->set('custom.new_option', 'value');
```

#### 实现原理

```php
protected function setDotValue(string $key, mixed $value): void
{
    $segments = explode('.', $key);
    $data =& $this->config; // 引用传递

    foreach ($segments as $segment) {
        // 如果不存在或不是数组，创建新数组
        if (!isset($data[$segment]) || !is_array($data[$segment])) {
            $data[$segment] = [];
        }
        $data =& $data[$segment]; // 移动到下一层级
    }

    $data = $value; // 设置最终值
}
```

### 4. 数组接口支持

#### ArrayAccess 接口实现

```php
// 数组式访问
$config = new ConfigRepository('/path/to/config');

// 读取配置
$database = $config['database']; // 等同于 $config->get('database')
$host = $config['database.connections.mysql.host'];

// 设置配置
$config['app.debug'] = true; // 等同于 $config->set('app.debug', true)

// 检查存在
isset($config['database']); // 等同于 $config->has('database')

// 删除配置
unset($config['app.debug']); // 设置为 null
```

#### 接口方法实现

```php
// 判断配置项是否存在
public function offsetExists(mixed $offset): bool
{
    return $this->has((string)$offset);
}

// 获取配置项值
public function offsetGet(mixed $offset): mixed
{
    return $this->get((string)$offset);
}

// 设置配置项值
public function offsetSet(mixed $offset, mixed $value): void
{
    $this->set((string)$offset, $value);
}

// 删除配置项
public function offsetUnset(mixed $offset): void
{
    $this->set((string)$offset, null);
}
```

## 使用指南

### 1. 基础使用

#### 创建配置仓库

```php
use Kernel\Config\ConfigRepository;

// 创建配置实例
$config = new ConfigRepository(APP_ROOT . '/config');

// 或通过服务容器获取
$config = app('config');
```

#### 访问配置

```php
// 方法调用方式
$dbHost = $config->get('database.connections.mysql.host');

// 数组访问方式
$dbHost = $config['database']['connections']['mysql']['host'];

// 辅助函数方式
$dbHost = config('database.connections.mysql.host');
```

### 2. 嵌套配置访问

#### 多级嵌套

```php
// 配置文件结构
return [
    'cache' => [
        'stores' => [
            'redis' => [
                'client' => 'phpredis',
                'options' => [
                    'prefix' => 'cache:',
                ],
            ],
        ],
    ],
];

// 访问深层嵌套配置
$prefix = $config->get('cache.stores.redis.options.prefix');
$prefix = $config['cache.stores.redis.options.prefix'];
```

#### 安全访问

```php
// 使用默认值避免错误
$timeout = $config->get('api.timeout', 30);

// 检查配置存在后再访问
if ($config->has('api.base_url')) {
    $baseUrl = $config->get('api.base_url');
}
```

### 3. 动态配置

#### 运行时设置

```php
// 根据环境动态设置配置
if (app()->environment('local')) {
    $config->set('app.debug', true);
    $config->set('logging.level', 'debug');
}

// 用户自定义配置
$config->set('user.preferences.theme', 'dark');
```

#### 配置覆盖

```php
// 覆盖默认配置
$defaultConfig = $config->get('features', []);
$userConfig = $config->get('user.features', []);

$mergedConfig = array_merge($defaultConfig, $userConfig);
$config->set('features.active', $mergedConfig);
```

### 4. 配置验证

#### 必需配置检查

```php
// 检查必需的配置项
$requiredConfigs = [
    'database.default',
    'app.name',
    'session.driver'
];

foreach ($requiredConfigs as $configKey) {
    if (!$config->has($configKey)) {
        throw new \RuntimeException("Missing required configuration: {$configKey}");
    }
}
```

#### 配置格式验证

```php
// 验证配置值格式
$databaseConfig = $config->get('database.connections.mysql');

if (!is_array($databaseConfig) || !isset($databaseConfig['host'])) {
    throw new \InvalidArgumentException('Invalid database configuration');
}
```

## 系统集成

### 1. 服务容器集成

#### ConfigServiceProvider

```php
// app/Providers/ConfigServiceProvider.php
public function register(): void
{
    $this->container->singleton(ConfigRepository::class, function () {
        return new ConfigRepository(base_path('config'));
    });

    $this->container->bind('config', function ($container) {
        return $container->make(ConfigRepository::class);
    });
}
```

#### 依赖注入

```php
class MyService
{
    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    public function getDatabaseConfig(): array
    {
        return $this->config->get('database.connections.default');
    }
}
```

### 2. 辅助函数

#### config() 函数

```php
// 全局辅助函数
if (!function_exists('config')) {
    function config(string $key = null, mixed $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        return app('config')->get($key, $default);
    }
}

// 使用示例
$dbConfig = config('database');
$host = config('database.connections.mysql.host', 'localhost');
```

### 3. 环境变量集成

#### 配置文件中使用环境变量

```php
// config/database.php
return [
    'host' => env('DB_HOST', 'localhost'),
    'database' => env('DB_DATABASE', 'myapp'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
];
```

#### 配置与环境变量的结合

```php
// 在配置仓库中处理环境变量
class ConfigRepository
{
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->getRaw($key, $default);

        // 如果值是环境变量引用，进行解析
        if (is_string($value) && str_starts_with($value, 'env(')) {
            return $this->parseEnvValue($value, $default);
        }

        return $value;
    }
}
```

## 性能优化

### 1. 配置缓存

#### 开发环境

```php
// 开发环境每次重新加载配置
if (env('APP_DEBUG')) {
    $config = new ConfigRepository(APP_ROOT . '/config');
}
```

#### 生产环境

```php
// 生产环境使用缓存配置
$cacheFile = APP_ROOT . '/cache/config.php';

if (file_exists($cacheFile)) {
    $configData = require $cacheFile;
    $config = new ConfigRepository($configData);
} else {
    $config = new ConfigRepository(APP_ROOT . '/config');
    // 生成缓存文件
    file_put_contents($cacheFile, '<?php return ' . var_export($config->all(), true) . ';');
}
```

### 2. 延迟加载

#### 按需加载配置

```php
class LazyConfigRepository extends ConfigRepository
{
    private array $loadedFiles = [];

    protected function loadConfiguration(string $file): void
    {
        $key = basename($file, '.php');

        if (!isset($this->loadedFiles[$key])) {
            $this->config[$key] = require $file;
            $this->loadedFiles[$key] = true;
        }
    }
}
```

### 3. 配置预编译

#### 配置文件优化

```php
// 预处理配置文件，减少运行时计算
return [
    'app_url' => rtrim(env('APP_URL', 'http://localhost'), '/'),
    'debug_mode' => env('APP_DEBUG', false),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    // 预计算常用配置
    'asset_url' => env('APP_URL') . '/assets',
];
```

## 最佳实践

### 1. 配置文件组织

#### 按功能分组

```php
// config/database.php - 数据库相关配置
// config/cache.php - 缓存相关配置
// config/logging.php - 日志相关配置
// config/services.php - 第三方服务配置
```

#### 环境特定配置

```php
// config/local/database.php - 本地环境配置
// config/production/database.php - 生产环境配置
// config/testing/database.php - 测试环境配置
```

### 2. 配置命名规范

#### 键名约定

```php
// 使用点号分隔的层级结构
'database.connections.mysql.host'
'cache.stores.redis.prefix'
'session.lifetime'

// 使用有意义的名称
'app.name' // 而不是 'app.n'
'security.encryption_key' // 而不是 'security.key'
```

### 3. 默认值设置

#### 合理的默认值

```php
// 提供安全的默认值
'debug' => env('APP_DEBUG', false), // 生产环境默认关闭调试
'key_length' => env('APP_KEY_LENGTH', 32), // 安全的密钥长度

// 提供开发友好的默认值
'log_level' => env('APP_LOG_LEVEL', 'info'), // 适中的日志级别
'cache_ttl' => env('CACHE_TTL', 3600), // 合理的缓存时间
```

### 4. 配置验证

#### 启动时验证

```php
// 在应用启动时验证关键配置
class ConfigValidator
{
    public function validate(ConfigRepository $config): void
    {
        $this->validateDatabase($config);
        $this->validateSecurity($config);
        $this->validatePaths($config);
    }

    private function validateDatabase(ConfigRepository $config): void
    {
        $driver = $config->get('database.default');
        if (!$driver) {
            throw new \RuntimeException('Database driver is required');
        }

        $connection = $config->get("database.connections.{$driver}");
        if (!$connection || !isset($connection['host'])) {
            throw new \RuntimeException('Database connection configuration is invalid');
        }
    }
}
```

## 调试和监控

### 1. 配置调试

#### 查看所有配置

```php
// 开发环境：输出所有配置
if (env('APP_DEBUG')) {
    var_dump($config->all());
}

// 查看特定配置组
$databaseConfig = $config->get('database');
var_dump($databaseConfig);
```

#### 配置来源追踪

```php
class TraceableConfigRepository extends ConfigRepository
{
    private array $sources = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $value = parent::get($key, $default);
        $this->sources[$key] = $this->determineSource($key);
        return $value;
    }

    public function getSource(string $key): string
    {
        return $this->sources[$key] ?? 'unknown';
    }
}
```

### 2. 配置变更监控

#### 配置变更日志

```php
class LoggingConfigRepository extends ConfigRepository
{
    private LoggerInterface $logger;

    public function set(string $key, mixed $value): void
    {
        $oldValue = $this->get($key);
        parent::set($key, $value);

        $this->logger->info('Configuration changed', [
            'key' => $key,
            'old_value' => $oldValue,
            'new_value' => $value,
        ]);
    }
}
```

## 扩展和自定义

### 1. 自定义配置仓库

#### 添加新功能

```php
class ExtendedConfigRepository extends ConfigRepository
{
    // 支持配置回调
    public function onChange(string $key, callable $callback): void
    {
        // 实现配置变更回调
    }

    // 支持配置分组
    public function group(string $prefix): self
    {
        return new GroupedConfigRepository($this, $prefix);
    }

    // 支持配置热重载
    public function reload(): void
    {
        $this->loadConfigurations($this->configPath);
    }
}
```

### 2. 配置加载器扩展

#### 支持多种配置格式

```php
class MultiFormatConfigRepository extends ConfigRepository
{
    protected function loadConfigurations(string $path): void
    {
        // 加载 PHP 文件
        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }

        // 加载 JSON 文件
        foreach (glob($path . '/*.json') as $file) {
            $key = basename($file, '.json');
            $this->config[$key] = json_decode(file_get_contents($file), true);
        }

        // 加载 YAML 文件（如果支持）
        foreach (glob($path . '/*.yml') as $file) {
            $key = basename($file, '.yml');
            $this->config[$key] = yaml_parse_file($file);
        }
    }
}
```

这个配置系统为 JnmPHP 框架提供了强大而灵活的配置管理能力，支持多种访问方式和扩展机制，是框架基础设施的重要组成部分。