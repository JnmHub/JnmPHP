# 模型 (Models)

此目录包含 JnmPHP 框架的所有 Eloquent 模型类。模型基于 PHP 8 Attributes 特性，实现了现代化的数据库操作和关系定义。

## 目录结构

```
app/Models/
├── User.php                    # 用户模型
├── Post.php                    # 文章模型
├── Product.php                 # 产品模型
├── Tag.php                     # 标签模型
└── README.md                   # 本文档
```

## 模型架构

### 基础模型类

所有模型都继承自 `Kernel\Database\BaseModel`，该类基于 Laravel 的 Eloquent ORM 并集成了以下 Traits：

- **HasMetadata** - 元数据处理
- **HasAttributes** - 属性管理
- **HasCrud** - CRUD 操作
- **HasFillable** - 批量赋值

### 属性系统

JnmPHP 使用 PHP 8 Attributes 定义模型行为，替代传统的配置文件方式：

| 属性类型 | 作用 | 目标 |
|----------|------|------|
| `#[TableField]` | 定义数据库字段映射 | 类属性 |
| `#[HasMany]` | 一对多关系 | 类属性 |
| `#[BelongsTo]` | 从属关系 | 类属性 |
| `#[BelongsToMany]` | 多对多关系 | 类属性 |
| `#[Validate]` | 验证规则 | 类属性 |
| `#[Accessor]` | 访问器 | 类方法 |
| `#[Mutator]` | 修改器 | 类方法 |

## 模型详解

### 1. User 模型 - 用户管理

**表名：** `users`

#### 字段定义

| 属性 | 数据库字段 | 类型 | 特性 | 说明 |
|------|------------|------|------|------|
| `$id` | `id` | `int` | 主键, 不可填充, 隐藏 | 用户唯一标识 |
| `$userName` | `name` | `string` | 可填充 | 用户名 |
| `$email` | `email` | `string` | 可填充 | 邮箱地址 |
| `$password` | `password` | `string` | 不可填充 | 用户密码 |
| `$lastLoginAt` | `last_login_at` | `?string` | 可填充, datetime转换 | 最后登录时间 |
| `$fullName` | - | `string` | 追加属性 | 计算属性，全名 |

#### 关系定义

```php
#[HasMany(related: Post::class)]
protected array $posts;  // 用户拥有多篇文章
```

#### 访问器 (Accessors)

```php
#[Accessor]
public function getUserNameAccessor(?string $value, array $u): string
{
    return "ccc" . ucfirst((string) $value);  // 用户名首字母大写
}

#[Accessor]
public function getFullNameAccessor(): string
{
    return 'Full Name: ';  // 全名计算属性
}
```

#### 修改器 (Mutators)

```php
#[Mutator]
public function setPasswordMutator(string $value): string
{
    return password_hash($value, PASSWORD_BCRYPT);  // 密码自动哈希
}
```

**使用示例：**
```php
// 创建用户
$user = new User();
$user->userName = 'john';
$user->email = 'john@example.com';
$user->password = 'secret';  // 自动哈希
$user->save();

// 访问属性
echo $user->userName;  // 输出: "cccJohn"
echo $user->fullName;  // 输出: "Full Name: "

// 获取关系
$posts = $user->posts;  // 获取用户的所有文章
```

---

### 2. Post 模型 - 文章管理

**表名：** `posts`

#### 字段定义

| 属性 | 数据库字段 | 类型 | 特性 | 说明 |
|------|------------|------|------|------|
| `$id` | `id` | `int` | 主键, 不可填充 | 文章唯一标识 |
| `$title` | `title` | `string` | 可填充 | 文章标题 |
| `$content` | `content` | `string` | 可填充 | 文章内容 |
| `$userId` | `user_id` | `int` | 不可填充 | 作者ID |

#### 关系定义

```php
#[BelongsTo(related: User::class, foreignKey: 'user_id')]
protected User $user;  // 文章属于一个用户

#[BelongsToMany(
    related: Tag::class,
    table: 'post_tag',
    foreignPivotKey: 'post_id',
    relatedPivotKey: 'tag_id'
)]
protected array $tags;  // 文章有多个标签
```

**使用示例：**
```php
// 创建文章
$post = new Post();
$post->title = '我的第一篇文章';
$post->content = '文章内容...';
$post->userId = 1;  // 设置作者ID
$post->save();

// 关系操作
$author = $post->user;        // 获取文章作者
$tags = $post->tags;          // 获取文章标签
$post->tags()->attach([1, 2]); // 关联标签
```

---

### 3. Product 模型 - 产品管理

**表名：** `products`

#### 字段定义与验证

| 属性 | 数据库字段 | 类型 | 验证规则 | 说明 |
|------|------------|------|----------|------|
| `$id` | `id` | `int` | 主键, 不可填充 | 产品唯一标识 |
| `$sku` | `sku` | `string` | required|string|unique:products,sku|max:50 | 产品SKU |
| `$name` | `name` | `string` | required|string|max:255 | 产品名称 |
| `$price` | `price` | `float` | required|numeric|min:0 | 产品价格 |
| `$stock` | `stock` | `int` | sometimes|integer|min:0 | 库存数量 |

#### 验证规则详解

```php
#[Validate('required|string|unique:products,sku|max:50')]
protected string $sku;  // 必填、字符串、唯一、最大50字符

#[Validate('required|string|max:255')]
protected string $name; // 必填、字符串、最大255字符

#[Validate('required|numeric|min:0')]
protected float $price; // 必填、数字、最小值0

#[Validate('sometimes|integer|min:0')]
protected int $stock;   // 可选、整数、最小值0
```

**使用示例：**
```php
// 创建产品（自动验证）
$product = new Product();
$product->sku = 'PROD-001';
$product->name = '优质产品';
$product->price = 99.99;
$product->stock = 100;
$product->save();  // 验证通过后保存
```

---

### 4. Tag 模型 - 标签管理

**表名：** `tags`

#### 字段定义

| 属性 | 数据库字段 | 类型 | 特性 | 说明 |
|------|------------|------|------|------|
| `$id` | `id` | `int` | 主键, 不可填充 | 标签唯一标识 |
| `$name` | `name` | `string` | 可填充 | 标签名称 |

#### 关系定义

```php
#[BelongsToMany(
    related: Post::class,
    table: 'post_tag',
    foreignPivotKey: 'tag_id',
    relatedPivotKey: 'post_id'
)]
protected array $posts;  // 标签属于多篇文章
```

**使用示例：**
```php
// 创建标签
$tag = new Tag();
$tag->name = 'PHP';
$tag->save();

// 关系操作
$posts = $tag->posts;     // 获取使用该标签的文章
$tag->posts()->attach([1, 2, 3]); // 关联文章
```

## 数据库关系图

```
Users (用户)
├── id (PK)
├── name
├── email
├── password
└── last_login_at
    │
    └── hasMany → Posts (文章)
                   ├── id (PK)
                   ├── title
                   ├── content
                   ├── user_id (FK)
                   │
                   ├── belongsTo → Users
                   └── belongsToMany → Tags (中间表: post_tag)
                                        ├── post_id (FK)
                                        └── tag_id (FK)
                                        │
                                        └── belongsToMany → Posts
```

## 属性系统详解

### TableField 属性

用于定义模型属性与数据库字段的映射关系：

```php
#[TableField(
    columnName: 'database_column_name',  // 数据库字段名，null时使用属性名
    isPrimaryKey: false,                 // 是否为主键
    isFillable: true,                    // 是否允许批量赋值
    cast: 'int',                         // 类型转换
    isHidden: false,                     // 序列化时是否隐藏
    isAppended: false                    // 是否追加到序列化结果
)]
protected int $id;
```

**常用配置示例：**

```php
// 主键字段
#[TableField(isPrimaryKey: true, isFillable: false, isHidden: true)]
protected int $id;

// 普通可填充字段
#[TableField(columnName: 'user_name', isFillable: true)]
protected string $userName;

// 只读字段
#[TableField(isFillable: false)]
protected string $password;

// 时间字段
#[TableField(columnName: 'created_at', cast: 'datetime')]
protected ?string $createdAt;

// 计算属性
#[TableField(isAppended: true)]
protected string $fullName;
```

### 关系属性

#### HasMany (一对多)

```php
#[HasMany(related: Post::class, foreignKey: 'user_id', localKey: 'id')]
protected array $posts;
```

- `related`: 关联模型类名
- `foreignKey`: 关联模型中的外键字段
- `localKey`: 当前模型的主键字段

#### BelongsTo (从属关系)

```php
#[BelongsTo(related: User::class, foreignKey: 'user_id', ownerKey: 'id')]
protected User $user;
```

- `related`: 关联模型类名
- `foreignKey`: 当前模型中的外键字段
- `ownerKey`: 关联模型的主键字段

#### BelongsToMany (多对多)

```php
#[BelongsToMany(
    related: Tag::class,
    table: 'post_tag',
    foreignPivotKey: 'post_id',
    relatedPivotKey: 'tag_id'
)]
protected array $tags;
```

- `related`: 关联模型类名
- `table`: 中间表名
- `foreignPivotKey`: 中间表中指向当前模型的外键
- `relatedPivotKey`: 中间表中指向关联模型的外键

### 验证属性

```php
#[Validate('required|string|email|max:255|unique:users,email')]
protected string $email;
```

**验证规则示例：**
- `required` - 必填
- `string` - 字符串类型
- `email` - 邮箱格式
- `max:255` - 最大长度255
- `unique:users,email` - 在users表中email字段唯一
- `sometimes` - 可选字段
- `numeric` - 数字类型
- `min:0` - 最小值0

### 访问器和修改器

#### 访问器 (Accessor)

```php
#[Accessor]
public function getFieldNameAccessor(mixed $value, array $context): mixed
{
    // $value: 数据库中的原始值
    // $context: 上下文信息
    return transformed_value;
}
```

**命名规则：** `getFieldNameAccessor()`

#### 修改器 (Mutator)

```php
#[Mutator]
public function setFieldNameMutator(mixed $value): mixed
{
    // $value: 要设置的值
    return transformed_value_to_store;
}
```

**命名规则：** `setFieldNameMutator()`

## CRUD 操作

### 创建记录

```php
// 使用构造函数
$user = new User(['userName' => 'John', 'email' => 'john@example.com']);
$user->save();

// 使用静态方法
$user = User::create(['userName' => 'John', 'email' => 'john@example.com']);
```

### 查询记录

```php
// 基础查询
$user = User::find(1);
$users = User::all();

// 条件查询
$users = User::where('email', 'john@example.com')->get();
$users = User::where('name', 'like', '%John%')->get();

// 关系查询
$user = User::with('posts')->find(1);
$posts = $user->posts;
```

### 更新记录

```php
$user = User::find(1);
$user->userName = 'Updated Name';
$user->save();

// 批量更新
User::where('id', 1)->update(['userName' => 'Updated Name']);
```

### 删除记录

```php
$user = User::find(1);
$user->delete();

// 批量删除
User::where('status', 'inactive')->delete();
```

## 高级功能

### 静态方法 (来自 HasCrud Trait)

BaseModel 提供了丰富的静态方法：

```php
// 查找方法
User::find($id);
User::findOrFail($id);
User::findBy('email', 'john@example.com');
User::getById($id);  // 自定义方法

// 创建方法
User::create($attributes);
User::firstOrCreate($attributes, $values);
User::updateOrCreate($attributes, $values);

// 查询构建器
User::where('status', 'active')->get();
User::with('posts')->limit(10)->get();
```

### 类型转换

```php
#[TableField(cast: 'datetime')]
protected ?string $createdAt;

#[TableField(cast: 'int')]
protected string $count;

#[TableField(cast: 'bool')]
protected int $isActive;
```

**支持的转换类型：**
- `int` / `integer` - 整数
- `float` / `double` / `real` - 浮点数
- `string` - 字符串
- `bool` / `boolean` - 布尔值
- `datetime` - 日期时间
- `date` - 日期
- `array` - 数组
- `json` - JSON
- `object` - 对象

### 序列化控制

```php
// 隐藏字段
#[TableField(isHidden: true)]
protected string $password;

// 追加计算属性
#[TableField(isAppended: true)]
protected string $fullName;

// 在 toArray() 时控制输出
public function toArray()
{
    $array = parent::toArray();
    $array['custom_field'] = 'custom value';
    return $array;
}
```

## 最佳实践

### 1. 模型设计原则

- **单一职责：** 每个模型专注于一个业务实体
- **关系清晰：** 明确定义模型间的关系
- **验证完整：** 为所有可输入字段定义验证规则
- **安全第一：** 合理设置批量赋值和隐藏字段

### 2. 属性使用规范

```php
// ✅ 推荐：完整的属性定义
#[TableField(
    columnName: 'user_name',
    isFillable: true,
    cast: 'string'
)]
#[Validate('required|string|max:255')]
protected string $userName;

// ❌ 避免：过于简单的定义
protected string $userName;
```

### 3. 关系定义最佳实践

```php
// ✅ 推荐：明确的关系定义
#[HasMany(
    related: Post::class,
    foreignKey: 'author_id',
    localKey: 'id'
)]
protected array $posts;

// ✅ 推荐：多对多关系完整配置
#[BelongsToMany(
    related: Tag::class,
    table: 'post_tag',
    foreignPivotKey: 'post_id',
    relatedPivotKey: 'tag_id'
)]
protected array $tags;
```

### 4. 性能优化

```php
// ✅ 推荐：预加载关系
$users = User::with('posts', 'tags')->get();

// ✅ 推荐：选择特定字段
$users = User::select(['id', 'name', 'email'])->get();

// ✅ 推荐：限制结果数量
$recentPosts = Post::latest()->limit(10)->get();
```

### 5. 安全考虑

```php
// ✅ 推荐：敏感字段不可填充
#[TableField(isFillable: false, isHidden: true)]
protected string $password;

// ✅ 推荐：强验证规则
#[Validate('required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/')]
protected string $password;

// ✅ 推荐：使用修改器处理敏感数据
#[Mutator]
public function setPasswordMutator(string $value): string
{
    return password_hash($value, PASSWORD_BCRYPT);
}
```

## IDE 支持

为了获得更好的 IDE 代码提示，建议在模型类上添加 PHPDoc 注释：

```php
/**
 * @property int $id
 * @property string $userName
 * @property string $email
 * @property-read \Illuminate\Database\Eloquent\Collection|Post[] $posts
 * @property-read string $fullName
 */
class User extends BaseModel
{
    // 模型实现
}
```

这个模型系统为 JnmPHP 框架提供了强大而灵活的数据库操作能力，支持现代化的 PHP 8 特性和最佳实践。

### 可以使用文件监听工具来自动生成PHPDoc 注释

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
