# 属性系统 (Attribute System)

此目录包含 JnmPHP 框架的所有 PHP 8 属性类。属性系统是框架的核心特性，提供了现代化的元数据标注和功能配置方式，替代了传统的配置文件和注解方式。

## 目录结构

```
kernel/Attribute/
├── Database/                        # 数据库相关属性
│   ├── BelongsTo.php               # 从属关系属性
│   ├── BelongsToMany.php           # 多对多关系属性
│   ├── HasMany.php                 # 一对多关系属性
│   ├── HasManyThrough.php          # 远程一对多关系属性
│   ├── HasOne.php                  # 一对一关系属性
│   ├── MorphMany.php               # 多态一对多关系属性
│   ├── MorphTo.php                 # 多态归属关系属性
│   └── TableField.php              # 数据库字段属性
├── Http/                           # HTTP 相关属性
│   ├── Get.php                     # GET 路由属性
│   ├── Post.php                    # POST 路由属性
│   ├── PathVariable.php            # 路径变量属性
│   ├── RequestBody.php             # 请求体绑定属性
│   ├── Route.php                   # 基础路由属性
│   └── RoutePrefix.php             # 路由前缀属性
├── Middleware/                     # 中间件相关属性
│   └── Middleware.php              # 中间件应用属性
├── ModelAccessor/                  # 模型访问器相关属性
│   ├── Accessor.php                # 访问器属性
│   └── Mutator.php                 # 修改器属性
├── Validation/                     # 验证相关属性
│   └── Validate.php                # 验证规则属性
└── README.md                       # 本文档
```

## 属性系统架构

### PHP 8 属性特性

JnmPHP 框架充分利用 PHP 8 的属性（Attributes）特性：

```php
#[Attribute(Attribute::TARGET_CLASS)]
class RoutePrefix
{
    // 属性实现
}
```

### 属性目标类型

| 目标类型 | 常量 | 说明 |
|----------|------|------|
| `TARGET_CLASS` | 1 | 可用于类 |
| `TARGET_METHOD` | 2 | 可用于方法 |
| `TARGET_PROPERTY` | 4 | 可用于属性 |
| `TARGET_PARAMETER` | 8 | 可用于参数 |
| `IS_REPEATABLE` | 64 | 可重复使用 |

### 属性继承层次

```
Route (基础路由)
├── Get (GET 路由)
└── Post (POST 路由)
```

## 属性分类详解

### 1. 数据库属性 (Database)

#### TableField - 数据库字段映射

**功能：** 定义模型属性与数据库字段的映射关系和行为。

```php
#[TableField(
    columnName: 'user_name',    // 数据库列名
    isPrimaryKey: false,         // 是否主键
    isFillable: true,           // 是否可批量赋值
    cast: 'string',             // 类型转换
    isHidden: false,            // 序列化时是否隐藏
    isAppended: false           // 是否追加到序列化结果
)]
protected string $userName;
```

**参数说明：**
- `columnName` - 数据库中的列名，null 时使用属性名
- `isPrimaryKey` - 是否为主键字段
- `isFillable` - 是否允许批量赋值
- `cast` - 自动类型转换类型
- `isHidden` - 序列化时是否隐藏
- `isAppended` - 是否追加计算属性

**使用示例：**
```php
class User extends BaseModel
{
    #[TableField(isPrimaryKey: true, isFillable: false, isHidden: true)]
    protected int $id;

    #[TableField(columnName: 'user_name', isFillable: true)]
    protected string $userName;

    #[TableField(isFillable: false)]
    protected string $password;

    #[TableField(cast: 'datetime')]
    protected ?string $createdAt;

    #[TableField(isAppended: true)]
    protected string $fullName;
}
```

---

#### 关系属性

##### HasMany - 一对多关系

```php
#[HasMany(
    related: Post::class,        // 关联模型类名
    foreignKey: 'user_id',       // 外键字段名
    localKey: 'id'               // 本地主键名
)]
protected array $posts;
```

##### BelongsTo - 从属关系

```php
#[BelongsTo(
    related: User::class,        // 关联模型类名
    foreignKey: 'user_id',       // 外键字段名
    ownerKey: 'id'               // 关联模型主键名
)]
protected User $user;
```

##### BelongsToMany - 多对多关系

```php
#[BelongsToMany(
    related: Tag::class,         // 关联模型类名
    table: 'post_tag',           // 中间表名
    foreignPivotKey: 'post_id',  // 中间表外键
    relatedPivotKey: 'tag_id'    // 关联表外键
)]
protected array $tags;
```

##### HasOne - 一对一关系

```php
#[HasOne(
    related: Profile::class,     // 关联模型类名
    foreignKey: 'user_id',       // 外键字段名
    localKey: 'id'               // 本地主键名
)]
protected Profile $profile;
```

##### HasManyThrough - 远程一对多关系

```php
#[HasManyThrough(
    related: Post::class,        // 最终关联模型
    through: Country::class,     // 中间模型
    firstKey: 'country_id',      // 中间模型外键
    secondKey: 'user_id',        // 最终模型外键
    localKey: 'id',              // 起始模型主键
    secondLocalKey: 'id'         // 中间模型主键
)]
protected array $posts;
```

##### MorphMany - 多态一对多关系

```php
#[MorphMany(
    related: Comment::class,     // 关联模型类名
    name: 'commentable',         // 关联名称
    type: 'commentable_type',    // 类型字段名
    id: 'commentable_id',        // ID 字段名
    localKey: 'id'               // 本地主键
)]
protected array $comments;
```

##### MorphTo - 多态归属关系

```php
#[MorphTo(
    name: 'commentable',         // 关联名称
    type: 'commentable_type',    // 类型字段名
    id: 'commentable_id',        // ID 字段名
    ownerKey: 'id'               // 关联模型主键
)]
protected Model $commentable;
```

---

### 2. HTTP 属性

#### Route - 基础路由属性

**功能：** 定义 HTTP 路由的基础属性。

```php
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        public string $path,          // 路由路径
        public array $methods = ['GET'] // HTTP 方法
    ) {}
}
```

#### Get - GET 路由

```php
#[Get('/users')]
public function getUsers()
{
    // GET /users
}
```

#### Post - POST 路由

```php
#[Post('/users')]
public function createUser()
{
    // POST /users
}
```

#### RoutePrefix - 路由前缀

```php
#[RoutePrefix('/api/v1')]
class UserController
{
    #[Get('/users')]
    public function getUsers()
    {
        // GET /api/v1/users
    }
}
```

#### PathVariable - 路径变量

```php
#[Get('/users/{id}')]
public function getUser(#[PathVariable('id', '用户ID不能为空')] int $id)
{
    // 自动将 URL 中的 {id} 转换为 int 类型
}
```

#### RequestBody - 请求体绑定

```php
#[Post('/users')]
public function createUser(#[RequestBody] User $user)
{
    // 自动将 JSON 请求体转换为 User 对象
    $user->save();
    return $user;
}
```

---

### 3. 中间件属性

#### Middleware - 中间件应用

**功能：** 在控制器方法或类上应用中间件。

```php
// 应用单个中间件
#[Middleware('auth')]
#[Get('/protected')]
public function protectedMethod()
{
    // 需要认证的方法
}

// 应用多个中间件
#[Middleware('auth', 'admin')]
#[Post('/admin/users')]
public function createAdminUser()
{
    // 需要认证和管理员权限
}

// 在类级别应用
#[Middleware('log')]
class ApiController extends BaseController
{
    // 类中所有方法都应用日志中间件
}
```

**参数支持：**
- 中间件类名：`#[Middleware(AuthMiddleware::class)]`
- 中间件别名：`#[Middleware('auth')]`
- 混合使用：`#[Middleware('auth', AdminMiddleware::class)]`

---

### 4. 模型访问器属性

#### Accessor - 访问器

**功能：** 标记方法为模型属性的访问器。

```php
#[Accessor]
public function getUserNameAccessor(?string $value): string
{
    return ucfirst($value); // 获取时自动调用
}

// 使用
$user->userName; // 自动调用访问器
```

#### Mutator - 修改器

**功能：** 标记方法为模型属性的修改器。

```php
#[Mutator]
public function setPasswordMutator(string $value): string
{
    return password_hash($value, PASSWORD_BCRYPT); // 设置时自动调用
}

// 使用
$user->password = 'secret'; // 自动调用修改器进行哈希
```

**命名规则：**
- 访问器：`getFieldNameAccessor()`
- 修改器：`setFieldNameMutator()`

---

### 5. 验证属性

#### Validate - 验证规则

**功能：** 为模型属性定义验证规则。

```php
class Product extends BaseModel
{
    #[Validate('required|string|unique:products,sku|max:50')]
    protected string $sku;

    #[Validate('required|string|max:255')]
    protected string $name;

    #[Validate('required|numeric|min:0')]
    protected float $price;

    #[Validate('sometimes|integer|min:0')]
    protected int $stock;
}
```

**验证规则示例：**
- `required` - 必填
- `string` - 字符串类型
- `email` - 邮箱格式
- `max:255` - 最大长度
- `unique:table,column` - 唯一性验证
- `numeric` - 数字类型
- `min:0` - 最小值
- `sometimes` - 可选字段

## 使用指南

### 属性应用位置

| 属性类型 | 应用位置 | 示例 |
|----------|----------|------|
| `TableField` | 类属性 | `#[TableField] protected $name;` |
| `Validate` | 类属性 | `#[Validate('required')] protected $email;` |
| `RoutePrefix` | 类 | `#[RoutePrefix('/api')] class Controller` |
| `Get/Post` | 方法 | `#[Get('/users')] public function index()` |
| `Middleware` | 类/方法 | `#[Middleware('auth')] public function profile()` |
| `PathVariable` | 参数 | `public function show(#[PathVariable('id')] int $id)` |
| `RequestBody` | 参数 | `public function store(#[RequestBody] User $user)` |
| `Accessor/Mutator` | 方法 | `#[Accessor] public function getNameAccessor()` |

### 组合使用示例

#### 完整的控制器示例

```php
#[RoutePrefix('/api/v1')]
#[Middleware('api')]
class UserController extends BaseController
{
    #[Get('/users')]
    #[Middleware('cache')]
    public function index()
    {
        // GET /api/v1/users
        // 应用 api 和 cache 中间件
    }

    #[Post('/users')]
    #[Middleware('validation')]
    public function store(#[RequestBody] User $user)
    {
        // POST /api/v1/users
        // 自动验证和绑定 User 对象
    }

    #[Get('/users/{id}')]
    public function show(#[PathVariable('id')] int $id)
    {
        // GET /api/v1/users/{id}
        // 自动转换路径参数类型
    }
}
```

#### 完整的模型示例

```php
class User extends BaseModel
{
    #[TableField(isPrimaryKey: true, isFillable: false, isHidden: true)]
    protected int $id;

    #[TableField(columnName: 'user_name', isFillable: true)]
    #[Validate('required|string|max:255')]
    protected string $userName;

    #[TableField(isFillable: false)]
    protected string $password;

    #[TableField(cast: 'datetime')]
    protected ?string $lastLoginAt;

    #[HasMany(related: Post::class)]
    protected array $posts;

    #[Accessor]
    public function getUserNameAccessor(?string $value): string
    {
        return ucfirst($value);
    }

    #[Mutator]
    public function setPasswordMutator(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }
}
```

## 属性系统优势

### 1. 代码即配置
- **声明式：** 属性直接声明在代码中，无需额外配置文件
- **类型安全：** PHP 提供类型检查和 IDE 支持
- **可读性：** 代码和配置在同一位置，易于理解

### 2. 性能优化
- **编译时处理：** 属性在类加载时处理，运行时开销小
- **缓存友好：** 属性信息可以缓存，避免重复解析
- **延迟加载：** 按需处理属性，提高启动性能

### 3. 开发体验
- **IDE 支持：** 现代 IDE 完全支持 PHP 8 属性
- **自动补全：** 属性参数提供智能提示
- **重构友好：** 重构工具可以正确处理属性引用

### 4. 扩展性
- **可组合：** 多个属性可以组合使用
- **可继承：** 属性定义可以继承和扩展
- **自定义：** 可以轻松创建自定义属性

## 高级特性

### 1. 重复属性

```php
#[Get('/users')]
#[Get('/user-list')]
public function getUsers()
{
    // 一个方法对应多个路由
}
```

### 2. 条件属性

```php
#[Get('/users')]
#[Middleware(env('APP_DEBUG') ? 'debug' : 'cache')]
public function getUsers()
{
    // 根据环境变量应用不同中间件
}
```

### 3. 动态属性值

```php
class DynamicController extends BaseController
{
    #[Get('/' . $this->getRoutePrefix())]
    public function index()
    {
        // 动态路由路径（示例）
    }

    private function getRoutePrefix(): string
    {
        return config('api.prefix', 'api');
    }
}
```

## 最佳实践

### 1. 属性组织
```php
// ✅ 推荐：相关属性组织在一起
#[Validate('required|string|max:255')]
#[TableField(columnName: 'user_name')]
protected string $userName;

// ✅ 推荐：路由属性在方法上方
#[Get('/users/{id}')]
#[Middleware('auth')]
public function show(#[PathVariable('id')] int $id)
```

### 2. 参数命名
```php
// ✅ 推荐：使用有意义的参数名
#[HasMany(
    related: Post::class,
    foreignKey: 'author_id',  // 明确的外键名
    localKey: 'id'           // 明确的主键名
)]

// ❌ 避免：使用模糊的参数名
#[HasMany('App\Models\Post', null, null)]
```

### 3. 验证规则
```php
// ✅ 推荐：详细的验证规则
#[Validate('required|string|email|max:255|unique:users,email')]
protected string $email;

// ✅ 推荐：条件验证
#[Validate('sometimes|image|max:2048')] // 有时才需要验证
protected ?UploadedFile $avatar;
```

### 4. 安全考虑
```php
// ✅ 推荐：敏感字段不可填充
#[TableField(isFillable: false, isHidden: true)]
protected string $password;

// ✅ 推荐：API 路由使用认证中间件
#[Post('/api/users')]
#[Middleware('auth:api')]
public function apiCreateUser()
```

## 性能考虑

### 1. 属性缓存
框架会缓存解析的属性信息以提高性能：
```php
// 缓存文件位置
cache/attributes.php
cache/routes.php
```

### 2. 延迟处理
```php
// 属性信息按需处理，避免不必要的解析
class LazyController
{
    #[Get('/heavy')]
    public function heavyOperation()
    {
        // 只在访问该路由时才处理属性
    }
}
```

### 3. 批量处理
```php
// 批量处理同类属性，减少反射开销
RouteCollector::run(); // 一次性处理所有路由属性
```

这个属性系统为 JnmPHP 框架提供了现代化、类型安全和高性能的配置方式，是框架的核心创新特性之一。