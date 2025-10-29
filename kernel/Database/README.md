# 数据库系统 (Database System)

此目录包含 JnmPHP 框架的数据库系统实现。数据库系统基于 Laravel Eloquent ORM，结合 PHP 8 Attributes 特性，提供了现代化的数据库操作和模型管理功能。

## 目录结构

```
kernel/Database/
├── BaseModel.php              # 基础模型类
├── DB.php                     # 数据库操作入口
├── Traits/                    # 模型特性
│   ├── HasAttributes.php     # 属性访问和处理
│   ├── HasCrud.php           # CRUD 操作扩展
│   ├── HasFillable.php       # 批量赋值处理
│   └── HasMetadata.php       # 元数据解析
└── README.md                  # 本文档
```

## 系统架构

### 核心设计理念

JnmPHP 数据库系统采用以下设计理念：

1. **属性驱动：** 使用 PHP 8 Attributes 定义模型行为
2. **元数据缓存：** 解析结果缓存，提高性能
3. **Laravel 兼容：** 基于 Laravel Eloquent，保持兼容性
4. **类型安全：** 支持强类型和自动类型转换
5. **关系映射：** 声明式的关系定义

### 架构层次

```
应用层 (Controllers)
    ↓
模型层 (Models extending BaseModel)
    ↓
特性层 (Traits)
    ↓
Eloquent ORM (Laravel)
    ↓
数据库驱动 (PDO)
```

## 核心组件详解

### 1. BaseModel - 基础模型

**功能：** 所有模型的基础类，集成各种模型特性

```php
abstract class BaseModel extends Model
{
    use HasMetadata,    // 元数据解析
        HasAttributes, // 属性处理
        HasCrud,       // CRUD 扩展
        HasFillable;   // 批量赋值
}
```

**特性：**
- **继承 Eloquent Model：** 获得完整的 ORM 功能
- **Trait 组合：** 通过 Trait 模式扩展功能
- **属性解析：** 自动解析 PHP 8 Attributes
- **关系支持：** 声明式关系定义

### 2. DB - 数据库操作入口

**功能：** 提供静态数据库操作接口

```php
class DB
{
    public static ?Capsule $capsule = null;

    public static function init(Container $container): void
    {
        self::$capsule = new Capsule($container);
        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();
    }

    public static function table(string $table): Builder
    {
        return self::$capsule->table($table);
    }

    public static function select(string $query, array $bindings = []): array
    {
        return self::$capsule->getConnection()->select($query, $bindings);
    }
}
```

**使用方法：**
```php
// 查询构建器
$users = DB::table('users')->where('active', 1)->get();

// 原生查询
$results = DB::select('SELECT * FROM users WHERE id = ?', [1]);

// 插入数据
DB::insert(['name' => 'John'], 'users');
```

### 3. Traits 系统详解

#### HasMetadata - 元数据解析

**功能：** 解析 PHP 8 Attributes，生成模型元数据

**核心方法：**
```php
public function getMetadata(): array
{
    $class = static::class;

    // 缓存机制避免重复解析
    if (isset(self::$classMetadataCache[$class])) {
        return self::$classMetadataCache[$class];
    }

    // 解析各种属性
    $metadata = [
        'primaryKey' => 'id',
        'fillable' => [],
        'mappings' => [],
        'reverseMappings' => [],
        'relations' => [],
        'casts' => [],
        'accessors' => [],
        'mutators' => [],
        'hidden' => [],
        'appends' => [],
        'rules' => []
    ];

    return self::$classMetadataCache[$class] = $metadata;
}
```

**支持的属性解析：**
- `#[TableField]` - 字段映射和配置
- `#[HasMany]` - 一对多关系
- `#[BelongsTo]` - 从属关系
- `#[BelongsToMany]` - 多对多关系
- `#[HasOne]` - 一对一关系
- `#[Accessor]` - 访问器
- `#[Mutator]` - 修改器
- `#[Validate]` - 验证规则

#### HasAttributes - 属性处理

**功能：** 动态属性访问和关系处理

**核心功能：**

1. **动态属性访问**
```php
public function __get($key)
{
    // 1. 检查是否为关联关系
    $relations = $this->getMetadata()['relations'];
    if (array_key_exists($key, $relations)) {
        // 2. 动态创建关联查询
        $relationQuery = match ($relationMeta['type']) {
            'HasMany' => $this->hasMany($config->related),
            'BelongsTo' => $this->belongsTo($config->related),
            // ... 其他关系类型
        };

        return $this->getRelations()[$key] = $result;
    }

    return parent::__get($key);
}
```

2. **访问器支持**
```php
public function getAttribute($key)
{
    $metadata = $this->getMetadata();
    $propertyName = $metadata['reverseMappings'][$key] ?? $key;

    if (array_key_exists($propertyName, $metadata['accessors'])) {
        $rawValue = parent::getAttribute($key);
        $arrays = $this->getArray();
        return $this->{$metadata['accessors'][$propertyName]}($rawValue, $arrays);
    }

    return parent::getAttribute($key);
}
```

3. **修改器支持**
```php
public function setAttribute($key, $value)
{
    $metadata = $this->getMetadata();
    $propertyName = $metadata['reverseMappings'][$key] ?? $key;

    if (array_key_exists($propertyName, $metadata['mutators'])) {
        $mutatorMethod = $metadata['mutators'][$propertyName];
        $returnedValue = $this->{$mutatorMethod}($value);
        return parent::setAttribute($key, $returnedValue);
    }

    return parent::setAttribute($key, $value);
}
```

4. **静态魔术方法**
```php
public static function __callStatic($method, $parameters)
{
    // User::_UserName() -> 'user_name'
    if (str_starts_with($method, '_')) {
        $propertyName = lcfirst(substr($method, 1));
        return $metadata['mappings'][$propertyName] ?? $propertyName;
    }

    // User::whereUserName('value') -> where('user_name', 'value')
    if (str_starts_with($method, 'where')) {
        $propertyName = lcfirst(substr($method, 5));
        $columnName = $metadata['mappings'][$propertyName] ?? $propertyName;
        $studlyColumn = Str::studly($columnName);
        return parent::__callStatic('where' . $studlyColumn, $parameters);
    }
}
```

#### HasCrud - CRUD 操作扩展

**功能：** 提供便捷的 CRUD 操作方法

**核心方法：**
```php
trait HasCrud
{
    // 根据ID获取记录
    public static function getById(int|string $id, array $columns = ['*']): ?static
    {
        return static::find($id, $columns);
    }

    // 获取所有记录
    public static function list(array $columns = ['*']): Collection
    {
        return static::all($columns);
    }

    // 分页获取记录
    public static function page(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return static::paginate($perPage, $columns);
    }

    // 快速创建记录
    public static function quickCreate(array $attributes): static
    {
        return static::create($attributes);
    }

    // 根据ID更新记录
    public static function quickUpdateById(int|string $id, array $values): bool
    {
        $model = static::find($id);
        return $model ? $model->update($values) : false;
    }

    // 根据ID删除记录
    public static function deleteById(int|string|array $ids): int
    {
        return static::destroy($ids);
    }
}
```

#### HasFillable - 批量赋值处理

**功能：** 智能处理批量赋值和字段映射

**核心功能：**

1. **智能字段映射**
```php
protected function fillableFromArray(array $attributes): array
{
    $metadata = $this->getMetadata();
    $mappings = $metadata['mappings']; // 属性名 => 列名
    $reverseMappings = $metadata['reverseMappings']; // 列名 => 属性名

    $processedAttributes = [];
    foreach ($attributes as $key => $value) {
        // 统一转换为属性名
        $propertyName = $reverseMappings[$key] ?? $key;

        if ($this->isFillable($propertyName)) {
            // 转换为列名
            $columnName = $mappings[$propertyName] ?? $propertyName;
            $processedAttributes[$columnName] = $value;
        } else {
            throw new MassAssignmentException(
                "Add [{$key}] to fillable property to allow mass assignment."
            );
        }
    }

    return $processedAttributes;
}
```

2. **可填充性检查**
```php
public function isFillable($key): bool
{
    $metadata = $this->getMetadata();
    $reverseMappings = $metadata['reverseMappings'];

    // 统一转换为属性名
    $propertyName = $reverseMappings[$key] ?? $key;

    return in_array($propertyName, $metadata['fillable']);
}
```

3. **数组转换**
```php
public function toArray(): array
{
    $attributes = parent::toArray();
    $metadata = $this->getMetadata();
    $reverseMappings = $metadata['reverseMappings'];

    $newArray = [];
    foreach ($attributes as $columnName => $value) {
        // 转换为属性名
        $propertyName = $reverseMappings[$columnName] ?? $columnName;

        // 处理访问器
        if (array_key_exists($propertyName, $metadata['accessors'])) {
            $value = $this->{$metadata['accessors'][$propertyName]}($value, $this->getArray());
        }

        $newArray[$propertyName] = $value;
    }

    return $newArray;
}
```

## 使用指南

### 1. 基础模型定义

#### 创建模型

```php
<?php

namespace App\Models;

use Kernel\Database\BaseModel;
use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\Database\HasMany;
use Kernel\Attribute\Database\BelongsTo;
use Kernel\Attribute\ModelAccessor\Accessor;
use Kernel\Attribute\ModelAccessor\Mutator;
use Kernel\Attribute\Validation\Validate;

class User extends BaseModel
{
    // 基础字段定义
    #[TableField(isPrimaryKey: true, isFillable: false, isHidden: true)]
    protected int $id;

    #[TableField(columnName: 'user_name', isFillable: true)]
    #[Validate('required|string|max:255')]
    protected string $userName;

    #[TableField(isFillable: false)]
    protected string $password;

    // 关系定义
    #[HasMany(related: Post::class)]
    protected array $posts;

    #[BelongsTo(related: Department::class)]
    protected Department $department;

    // 访问器
    #[Accessor]
    public function getUserNameAccessor(?string $value): string
    {
        return ucfirst($value);
    }

    // 修改器
    #[Mutator]
    public function setPasswordMutator(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }
}
```

### 2. 数据库操作

#### 基础 CRUD

```php
// 创建用户
$user = User::quickCreate([
    'userName' => 'john_doe',
    'password' => 'secret123'
]);

// 获取用户
$user = User::getById(1);
$users = User::list();
$users = User::page(10);

// 更新用户
User::quickUpdateById(1, ['userName' => 'jane_doe']);

// 删除用户
User::deleteById(1);
```

#### 关系操作

```php
// 获取用户的所有文章
$user = User::getById(1);
$posts = $user->posts; // 自动触发关联查询

// 获取用户的部门
$department = $user->department;

// 动态关联方法调用
$posts = $user->posts(); // 返回查询构建器
$recentPosts = $user->posts()->latest()->limit(5)->get();
```

#### 查询构建器

```php
// 静态魔术方法
$users = User::whereUserName('john')->get();
$users = User::whereUserNameAndEmail('john', 'john@example.com')->get();

// 属性名转换
$columnName = User::_UserName(); // 返回 'user_name'

// 使用 DB 类
$results = DB::table('users')->where('active', 1)->get();
$results = DB::select('SELECT * FROM users WHERE created_at > ?', [date('Y-m-d')]);
```

### 3. 高级功能

#### 类型转换

```php
class User extends BaseModel
{
    #[TableField(cast: 'datetime')]
    protected ?string $createdAt;

    #[TableField(cast: 'boolean')]
    protected bool $isActive;

    #[TableField(cast: 'json')]
    protected array $metadata;
}
```

#### 访问器和修改器

```php
class User extends BaseModel
{
    // 计算属性
    #[TableField(isAppended: true)]
    protected string $fullName;

    #[Accessor]
    public function getFullNameAccessor(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    #[Mutator]
    public function setEmailMutator(string $email): string
    {
        return strtolower($email);
    }
}
```

#### 验证规则

```php
class Product extends BaseModel
{
    #[Validate('required|string|unique:products,sku|max:50')]
    protected string $sku;

    #[Validate('required|numeric|min:0')]
    protected float $price;

    #[Validate('sometimes|integer|min:0')]
    protected int $stock;
}

// 创建时自动验证
$product = Product::quickCreate([
    'sku' => 'PROD-001',
    'price' => 99.99
]); // 验证失败会抛出异常
```

## 性能优化

### 1. 元数据缓存

```php
trait HasMetadata
{
    private static array $classMetadataCache = [];

    public function getMetadata(): array
    {
        $class = static::class;

        // 避免重复解析
        if (isset(self::$classMetadataCache[$class])) {
            return self::$classMetadataCache[$class];
        }

        // 解析并缓存
        $metadata = $this->parseMetadata();
        return self::$classMetadataCache[$class] = $metadata;
    }
}
```

### 2. 关系预加载

```php
// 预加载关联关系，避免 N+1 查询
$users = User::with('posts', 'department')->get();

// 条件预加载
$users = User::with(['posts' => function($query) {
    $query->where('published', true);
}])->get();
```

### 3. 查询优化

```php
// 选择特定字段
$users = User::select(['id', 'userName', 'email'])->get();

// 分页查询
$users = User::page(20);

// 缓存查询结果
$users = User::remember(60)->get();
```

## 最佳实践

### 1. 模型设计

#### 合理的字段映射

```php
// ✅ 推荐：使用有意义的属性名
#[TableField(columnName: 'user_name', isFillable: true)]
protected string $userName;

// ❌ 避免：使用模糊的映射
#[TableField(columnName: 'un', isFillable: true)]
protected string $userName;
```

#### 关系定义

```php
// ✅ 推荐：明确的关系配置
#[HasMany(related: Post::class, foreignKey: 'author_id', localKey: 'id')]
protected array $posts;

// ✅ 推荐：使用常量
class User extends BaseModel
{
    public const POSTS_RELATION = 'posts';

    #[HasMany(related: Post::class, foreignKey: 'author_id')]
    protected array $posts;
}
```

### 2. 查询优化

#### 避免过度查询

```php
// ❌ 避免：N+1 查询
$users = User::all();
foreach ($users as $user) {
    echo $user->posts->count(); // 每次都查询数据库
}

// ✅ 推荐：预加载关联
$users = User::with('posts')->get();
foreach ($users as $user) {
    echo $user->posts->count(); // 只查询一次数据库
}
```

#### 使用适当的查询方法

```php
// 获取单个记录
$user = User::getById(1); // 推荐
$user = User::find(1); // 也可以

// 获取多条记录
$users = User::list(); // 推荐
$users = User::all(); // 也可以

// 分页
$users = User::page(15); // 推荐
$users = User::paginate(15); // 也可以
```

### 3. 数据安全

#### 批量赋值安全

```php
// ✅ 推荐：明确指定可填充字段
#[TableField(isFillable: true)]
protected string $userName;

#[TableField(isFillable: false)]
protected string $password; // 敏感字段不可批量赋值

// ✅ 推荐：使用验证规则
#[Validate('required|string|max:255')]
protected string $userName;
```

#### 数据验证

```php
// ✅ 推荐：在模型层验证
class User extends BaseModel
{
    #[Validate('required|email|unique:users,email')]
    protected string $email;
}

// ✅ 推荐：自定义验证逻辑
#[Mutator]
public function setPasswordMutator(string $password): string
{
    if (strlen($password) < 8) {
        throw new InvalidArgumentException('密码长度不能少于8位');
    }
    return password_hash($password, PASSWORD_BCRYPT);
}
```

## 扩展和自定义

### 1. 自定义 Trait

```php
trait SoftDeletes
{
    #[TableField(isFillable: false)]
    protected ?string $deletedAt;

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function delete()
    {
        $this->deletedAt = now();
        return $this->save();
    }
}
```

### 2. 自定义属性

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class CacheKey
{
    public function __construct(public string $key) {}
}

class CacheableModel extends BaseModel
{
    public function getCacheKey(): string
    {
        $metadata = $this->getMetadata();
        return $metadata['cacheKeys'][$this->primaryKey] ?? $this->primaryKey;
    }
}
```

### 3. 自定义关系

```php
trait CustomRelations
{
    public function __call($method, $parameters)
    {
        // 处理自定义关系
        if (str_starts_with($method, 'custom')) {
            return $this->handleCustomRelation($method, $parameters);
        }

        return parent::__call($method, $parameters);
    }
}
```

## 故障排除

### 1. 常见问题

#### 属性访问错误

```php
// 问题：属性名映射错误
$user = User::getById(1);
echo $user->user_name; // 错误：应该使用属性名

// 解决：使用正确的属性名
echo $user->userName; // 正确

// 或者通过列名访问
echo $user->getAttribute('user_name');
```

#### 关系加载失败

```php
// 问题：关系属性未定义
class User extends BaseModel
{
    // 缺少 #[HasMany] 属性定义
}

// 解决：添加关系属性定义
#[HasMany(related: Post::class)]
protected array $posts;
```

### 2. 调试技巧

#### 查看元数据

```php
// 查看模型的元数据
$user = new User();
var_dump($user->getMetadata());

// 查看字段映射
$mappings = $user->getMetadata()['mappings'];
var_dump($mappings);
```

#### 查看关系

```php
// 查看关系定义
$relations = $user->getMetadata()['relations'];
var_dump($relations);

// 查看已加载的关系
var_dump($user->getRelations());
```

#### SQL 查询日志

```php
// 启用查询日志
DB::listen(function($query) {
    error_log($query->sql);
    error_log(json_encode($query->bindings));
});
```

这个数据库系统为 JnmPHP 框架提供了现代化、类型安全和易用的数据库操作能力，结合 PHP 8 Attributes 和 Laravel Eloquent 的优势，为开发者提供了优秀的开发体验。