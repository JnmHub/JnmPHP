**模型之间关联关系**的注解（Attributes）。

在 `HasMetadata.php` 文件中，这个数组 `[HasMany::class => 'HasMany', ...]` 的作用是告诉元数据解析器：“当你在一个属性上看到 `#[HasMany]` 这个注解时，就把它注册为一种 'HasMany' 类型的关联”。

下面我来详细讲解每一个注解的作用和用法：

------



### 1. `#[HasMany]` (一对多)



- **作用**：定义一个“一对多”的关联。用于“一”的那一方。
- **示例**：一个用户（User）拥有多篇文章（Post）。
- **参数**：
    - `related`：关联模型的类名（例如 `Post::class`）。
    - `foreignKey`：（可选）关联模型（Post）上的外键名。默认是 `user_id` (根据当前模型名 `User` + `_id` 自动推断)。
    - `localKey`：（可选）当前模型（User）上的主键名。默认是 `id`。
- **怎么用**（`User.php` 中的例子）：

PHP

```
// 在 App\Models\User.php 中
class User extends BaseModel
{
    /**
     * ✅ 定义 User 拥有多个 Post 的关系
     */
    #[HasMany(related: Post::class)]
    protected array $posts; // 属性名 'posts' 就是关联名
}
```

------



### 2. `#[BelongsTo]` (从属于)



- **作用**：定义“一对多”关系中“多”的那一方，表示它“从属于”另一个模型。
- **示例**：一篇文章（Post）从属于一个用户（User）。
- **参数**：
    - `related`：关联模型的类名（例如 `User::class`）。
    - `foreignKey`：（可选）当前模型（Post）上的外键名。默认是 `user` (根据关联名 `user` + `_id` 自动推断)。
    - `ownerKey`：（可选）关联模型（User）上的主键名。默认是 `id`。
- **怎么用**（假设在 `Post.php` 中）：

PHP

```
// 在 App\Models\Post.php 中
class Post extends BaseModel
{
    /**
     * 定义 Post 从属于 User 的关系
     */
    #[BelongsTo(related: User::class, foreignKey: 'user_id')]
    protected User $user; // 属性名 'user' 就是关联名
}
```

*(注意：`foreignKey` 最好明确指定，或者确保属性名 `user` 能正确推断为 `user_id`)*

------



### 3. `#[BelongsToMany]` (多对多)



- **作用**：定义一个“多对多”关联。
- **示例**：一篇文章（Post）可以有多个标签（Tag），一个标签（Tag）也可以属于多篇文章。这需要一个中间表（例如 `post_tag`）。
- **参数**：
    - `related`：关联模型的类名（例如 `Tag::class`）。
    - `table`：（可选）中间表的名称（例如 `post_tag`）。
    - `foreignPivotKey`：（可选）中间表上指向当前模型（Post）的外键名（例如 `post_id`）。
    - `relatedPivotKey`：（可选）中间表上指向关联模型（Tag）的外键名（例如 `tag_id`）。
- **怎么用**（假设在 `Post.php` 中）：

PHP

```
// 在 App\Models\Post.php 中
class Post extends BaseModel
{
    /**
     * 定义 Post 和 Tag 的多对多关系
     */
    #[BelongsToMany(related: Tag::class, table: 'post_tag')]
    protected array $tags; // 属性名 'tags' 就是关联名
}
```

------



### 4. `#[HasOne]` (一对一)



- **作用**：定义一个“一对一”关联。
- **示例**：一个用户（User）拥有一个档案（Profile）。
- **参数**：
    - `related`：关联模型的类名（例如 `Profile::class`）。
    - `foreignKey`：（可选）关联模型（Profile）上的外键名（例如 `user_id`）。
    - `localKey`：（可选）当前模型（User）上的主键名（例如 `id`）。
- **怎么用**（在 `User.php` 中）：

PHP

```
// 在 App\Models\User.php 中
class User extends BaseModel
{
    /**
     * 定义 User 拥有一个 Profile 的关系
     */
    #[HasOne(related: Profile::class)]
    protected Profile $profile; // 属性名 'profile' 就是关联名
}
```

------



### 5. `#[HasManyThrough]` (远程一对多)



- **作用**：定义一个“通过”中间表的“一对多”关系。
- **示例**：一个国家（Country）有很多篇文章（Post），*通过* 用户（User）表来关联。
    - `Country` (id) -> `User` (country_id, id) -> `Post` (user_id)
- **参数**（根据您提供的代码）：
    - `related`：最终关联的模型（例如 `Post::class`）。
    - `through`：中间模型（例如 `User::class`）。
    - `firstKey`：（可选）中间模型（User）上指向当前模型（Country）的外键（`country_id`）。
    - `secondKey`：（可选）最终模型（Post）上指向中间模型（User）的外键（`user_id`）。
    - `localKey`：（可选）当前模型（Country）的主键（`id`）。
    - `secondLocalKey`：（可选）中间模型（User）的主键（`id`）。
- **怎么用**（假设在 `Country.php` 中）：

PHP

```
// 在 App\Models\Country.php 中
class Country extends BaseModel
{
    /**
     * 定义 Country 通过 User 拥有多个 Post 的关系
     */
    #[HasManyThrough(related: Post::class, through: User::class)]
    protected array $posts;
}
```

------



### 6. `#[MorphMany]` (多态一对多)



- **作用**：定义一个“多态”的“一对多”关联。
- **示例**：文章（Post）和视频（Video）都可以有多条评论（Comment）。`Comment` 表需要 `commentable_id` 和 `commentable_type` 两个字段。
- **参数**（根据您提供的代码）：
    - `related`：关联的模型（例如 `Comment::class`）。
    - `name`：关联名称（例如 `commentable`）。这个名称会用于生成 `_id` 和 `_type` 字段名。
    - `type`：（可选）`_type` 列的名称（默认 `commentable_type`）。
    - `id`：（可选）`_id` 列的名称（默认 `commentable_id`）。
    - `localKey`：（可选）当前模型（Post/Video）的主键（默认 `id`）。
- **怎么用**（在 `Post.php` 中）：

PHP

```
// 在 App\Models\Post.php 中
class Post extends BaseModel
{
    /**
     * 定义 Post 可以有多态的 Comment
     */
    #[MorphMany(related: Comment::class, name: 'commentable')]
    protected array $comments;
}
```

------



### 7. `#[MorphTo]` (多态归属)



- **作用**：定义多态关联中“多”的那一方（与 `MorphMany` 对应）。
- **示例**：评论（Comment）可以从属于文章（Post），也可以从属于视频（Video）。
- **参数**（根据您提供的代码）：
    - `name`：关联名称（例如 `commentable`）。
    - `type`：（可选）`_type` 列的名称。
    - `id`：（可选）`_id` 列的名称。
    - `ownerKey`：（可选）关联的所属键。
- **怎么用**（在 `Comment.php` 中）：

PHP

```
// 在 App\Models\Comment.php 中
class Comment extends BaseModel
{
    /**
     * 定义 Comment 可以从属于任何其他模型
     */
    #[MorphTo(name: 'commentable')]
    protected $commentable; // 属性名必须和 'name' 匹配
}
```