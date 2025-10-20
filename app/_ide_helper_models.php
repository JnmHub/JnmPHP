<?php
// @formatter:off
// phpcs:ignoreFile

/**
 * 这是一个 IDE 辅助文件，由 JnmPHP Console Command 自动生成。
 * 它不应在运行时被加载。
 *
 * 生成时间: 2025-10-20 15:33:24
 */


namespace App\Models {
    /**
 * App\Models\Product
 *
 * --- 属性 (Properties) ---
 * @property int  $id
 * @property string  $sku
 * @property string  $name
 * @property float  $price
 * @property int  $stock
 *
 * --- 静态魔术列名 (Static Magic Columns) ---
 * @method static string _Id()
 * @method static string _Sku()
 * @method static string _Name()
 * @method static string _Price()
 * @method static string _Stock()
 *
 * --- 静态魔术查询 (Static Magic Wheres) ---
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereStock($value)
 *
 * --- 静态 HasCrud Trait 方法 ---
 * @method static ?Product  getById(string|int $id, array $columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Collection  list(array $columns = array (  0 => '*',))
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator  page(int $perPage = 15, array $columns = array (  0 => '*',))
 * @method static Product  quickCreate(array $attributes)
 * @method static bool  quickUpdateById(string|int $id, array $values)
 * @method static int  deleteById(array|string|int $ids)
 *
 * --- 常用静态 Eloquent 方法 ---
 * @method static \Illuminate\Database\Eloquent\Builder|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product query()
 * @method static Product|null find(mixed $id, array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection findMany(array $ids, array $columns = ['*'])
 * @method static Product findOrFail(mixed $id, array $columns = ['*'])
 * @method static Product firstOrFail(array $columns = ['*'])
 * @method static Product firstOrNew(array $attributes = [], array $values = [])
 * @method static Product firstOrCreate(array $attributes = [], array $values = [])
 * @method static Product updateOrCreate(array $attributes, array $values = [])
 * @method static Product firstOr(array $columns = ['*'], \Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Collection all(array $columns = ['*'])
 * @method static Product create(array $attributes)
 * @method static int insert(array $values)
 * @method static int insertOrIgnore(array $values)
 * @method static int upsert(array $values, mixed $uniqueBy, array|null $update = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Product withGlobalScope(string $identifier, \Closure $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|Product withoutGlobalScope(string $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|Product withoutGlobalScopes(array|null $scopes = null)
 *
 * --- 常用 Eloquent 实例方法 ---
 * @method bool save(array $options = [])
 * @method bool update(array $attributes = [], array $options = [])
 * @method bool delete()
 * @method bool forceDelete()
 * @method bool restore()
 * @method $this refresh()
 * @method $this fill(array $attributes)
 * @method $this replicate(array $except = null)
 * @method bool isDirty($attributes = null)
 * @method bool isClean($attributes = null)
 * @method bool wasChanged($attributes = null)
 * @method array getDirty()
 * @method array getChanges()
 * @method mixed getOriginal($key = null, $default = null)
 * @method bool is($model)
 * @method bool isNot($model)
 * @method bool exists()
 * @method bool doesntExist()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasOne hasOne($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasMany hasMany($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
 *
 * @mixin \Eloquent
 */
 abstract class Product extends Kernel\Database\BaseModel {}
}

namespace App\Models {
    /**
 * App\Models\Post
 *
 * --- 属性 (Properties) ---
 * @property int  $id
 * @property string  $title
 * @property string  $content
 * @property int  $userId
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 *
 * --- 静态魔术列名 (Static Magic Columns) ---
 * @method static string _Id()
 * @method static string _Title()
 * @method static string _Content()
 * @method static string _UserId()
 * @method static string _User()
 * @method static string _Tags()
 *
 * --- 静态魔术查询 (Static Magic Wheres) ---
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereTags($value)
 *
 * --- 静态 HasCrud Trait 方法 ---
 * @method static ?Post  getById(string|int $id, array $columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Collection  list(array $columns = array (  0 => '*',))
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator  page(int $perPage = 15, array $columns = array (  0 => '*',))
 * @method static Post  quickCreate(array $attributes)
 * @method static bool  quickUpdateById(string|int $id, array $values)
 * @method static int  deleteById(array|string|int $ids)
 *
 * --- 常用静态 Eloquent 方法 ---
 * @method static \Illuminate\Database\Eloquent\Builder|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Post query()
 * @method static Post|null find(mixed $id, array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection findMany(array $ids, array $columns = ['*'])
 * @method static Post findOrFail(mixed $id, array $columns = ['*'])
 * @method static Post firstOrFail(array $columns = ['*'])
 * @method static Post firstOrNew(array $attributes = [], array $values = [])
 * @method static Post firstOrCreate(array $attributes = [], array $values = [])
 * @method static Post updateOrCreate(array $attributes, array $values = [])
 * @method static Post firstOr(array $columns = ['*'], \Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Collection all(array $columns = ['*'])
 * @method static Post create(array $attributes)
 * @method static int insert(array $values)
 * @method static int insertOrIgnore(array $values)
 * @method static int upsert(array $values, mixed $uniqueBy, array|null $update = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Post withGlobalScope(string $identifier, \Closure $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|Post withoutGlobalScope(string $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|Post withoutGlobalScopes(array|null $scopes = null)
 *
 * --- 常用 Eloquent 实例方法 ---
 * @method bool save(array $options = [])
 * @method bool update(array $attributes = [], array $options = [])
 * @method bool delete()
 * @method bool forceDelete()
 * @method bool restore()
 * @method $this refresh()
 * @method $this fill(array $attributes)
 * @method $this replicate(array $except = null)
 * @method bool isDirty($attributes = null)
 * @method bool isClean($attributes = null)
 * @method bool wasChanged($attributes = null)
 * @method array getDirty()
 * @method array getChanges()
 * @method mixed getOriginal($key = null, $default = null)
 * @method bool is($model)
 * @method bool isNot($model)
 * @method bool exists()
 * @method bool doesntExist()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasOne hasOne($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasMany hasMany($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
 *
 * @mixin \Eloquent
 */
 abstract class Post extends Kernel\Database\BaseModel {}
}

namespace App\Models {
    /**
 * App\Models\Tag
 *
 * --- 属性 (Properties) ---
 * @property int  $id
 * @property string  $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Post[] $posts
 *
 * --- 静态魔术列名 (Static Magic Columns) ---
 * @method static string _Id()
 * @method static string _Name()
 * @method static string _Posts()
 *
 * --- 静态魔术查询 (Static Magic Wheres) ---
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag wherePosts($value)
 *
 * --- 静态 HasCrud Trait 方法 ---
 * @method static ?Tag  getById(string|int $id, array $columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Collection  list(array $columns = array (  0 => '*',))
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator  page(int $perPage = 15, array $columns = array (  0 => '*',))
 * @method static Tag  quickCreate(array $attributes)
 * @method static bool  quickUpdateById(string|int $id, array $values)
 * @method static int  deleteById(array|string|int $ids)
 *
 * --- 常用静态 Eloquent 方法 ---
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag query()
 * @method static Tag|null find(mixed $id, array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection findMany(array $ids, array $columns = ['*'])
 * @method static Tag findOrFail(mixed $id, array $columns = ['*'])
 * @method static Tag firstOrFail(array $columns = ['*'])
 * @method static Tag firstOrNew(array $attributes = [], array $values = [])
 * @method static Tag firstOrCreate(array $attributes = [], array $values = [])
 * @method static Tag updateOrCreate(array $attributes, array $values = [])
 * @method static Tag firstOr(array $columns = ['*'], \Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Collection all(array $columns = ['*'])
 * @method static Tag create(array $attributes)
 * @method static int insert(array $values)
 * @method static int insertOrIgnore(array $values)
 * @method static int upsert(array $values, mixed $uniqueBy, array|null $update = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag withGlobalScope(string $identifier, \Closure $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag withoutGlobalScope(string $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag withoutGlobalScopes(array|null $scopes = null)
 *
 * --- 常用 Eloquent 实例方法 ---
 * @method bool save(array $options = [])
 * @method bool update(array $attributes = [], array $options = [])
 * @method bool delete()
 * @method bool forceDelete()
 * @method bool restore()
 * @method $this refresh()
 * @method $this fill(array $attributes)
 * @method $this replicate(array $except = null)
 * @method bool isDirty($attributes = null)
 * @method bool isClean($attributes = null)
 * @method bool wasChanged($attributes = null)
 * @method array getDirty()
 * @method array getChanges()
 * @method mixed getOriginal($key = null, $default = null)
 * @method bool is($model)
 * @method bool isNot($model)
 * @method bool exists()
 * @method bool doesntExist()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasOne hasOne($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasMany hasMany($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
 *
 * @mixin \Eloquent
 */
 abstract class Tag extends Kernel\Database\BaseModel {}
}

namespace App\Models {
    /**
 * App\Models\User
 *
 * --- 属性 (Properties) ---
 * @property int  $id
 * @property string  $userName
 * @property string  $email
 * @property string  $password
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Post[] $posts
 * @property ?string  $lastLoginAt
 * @property string  $fullName
 *
 * --- 静态魔术列名 (Static Magic Columns) ---
 * @method static string _Id()
 * @method static string _UserName()
 * @method static string _Email()
 * @method static string _Password()
 * @method static string _Posts()
 * @method static string _LastLoginAt()
 * @method static string _FullName()
 *
 * --- 静态魔术查询 (Static Magic Wheres) ---
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFullName($value)
 *
 * --- 静态 HasCrud Trait 方法 ---
 * @method static ?User  getById(string|int $id, array $columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Collection  list(array $columns = array (  0 => '*',))
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator  page(int $perPage = 15, array $columns = array (  0 => '*',))
 * @method static User  quickCreate(array $attributes)
 * @method static bool  quickUpdateById(string|int $id, array $values)
 * @method static int  deleteById(array|string|int $ids)
 *
 * --- 常用静态 Eloquent 方法 ---
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static User|null find(mixed $id, array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection findMany(array $ids, array $columns = ['*'])
 * @method static User findOrFail(mixed $id, array $columns = ['*'])
 * @method static User firstOrFail(array $columns = ['*'])
 * @method static User firstOrNew(array $attributes = [], array $values = [])
 * @method static User firstOrCreate(array $attributes = [], array $values = [])
 * @method static User updateOrCreate(array $attributes, array $values = [])
 * @method static User firstOr(array $columns = ['*'], \Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Collection all(array $columns = ['*'])
 * @method static User create(array $attributes)
 * @method static int insert(array $values)
 * @method static int insertOrIgnore(array $values)
 * @method static int upsert(array $values, mixed $uniqueBy, array|null $update = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User withGlobalScope(string $identifier, \Closure $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutGlobalScope(string $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutGlobalScopes(array|null $scopes = null)
 *
 * --- 访问器/修改器 (Accessors/Mutators) ---
 * @method string  getUserNameAccessor(?string $value, array $u)
 * @method string  setPasswordMutator(string $value)
 * @method string  getFullNameAccessor()
 *
 * --- 常用 Eloquent 实例方法 ---
 * @method bool save(array $options = [])
 * @method bool update(array $attributes = [], array $options = [])
 * @method bool delete()
 * @method bool forceDelete()
 * @method bool restore()
 * @method $this refresh()
 * @method $this fill(array $attributes)
 * @method $this replicate(array $except = null)
 * @method bool isDirty($attributes = null)
 * @method bool isClean($attributes = null)
 * @method bool wasChanged($attributes = null)
 * @method array getDirty()
 * @method array getChanges()
 * @method mixed getOriginal($key = null, $default = null)
 * @method bool is($model)
 * @method bool isNot($model)
 * @method bool exists()
 * @method bool doesntExist()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasOne hasOne($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasMany hasMany($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
 *
 * @mixin \Eloquent
 */
 abstract class User extends Kernel\Database\BaseModel {}
}

namespace App\Models\as {
    /**
 * App\Models\as\Post
 *
 * --- 属性 (Properties) ---
 * @property int  $id
 * @property string  $title
 * @property string  $content
 * @property int  $userId
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 *
 * --- 静态魔术列名 (Static Magic Columns) ---
 * @method static string _Id()
 * @method static string _Title()
 * @method static string _Content()
 * @method static string _UserId()
 * @method static string _User()
 * @method static string _Tags()
 *
 * --- 静态魔术查询 (Static Magic Wheres) ---
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereTags($value)
 *
 * --- 静态 HasCrud Trait 方法 ---
 * @method static ?Post  getById(string|int $id, array $columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Collection  list(array $columns = array (  0 => '*',))
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator  page(int $perPage = 15, array $columns = array (  0 => '*',))
 * @method static Post  quickCreate(array $attributes)
 * @method static bool  quickUpdateById(string|int $id, array $values)
 * @method static int  deleteById(array|string|int $ids)
 *
 * --- 常用静态 Eloquent 方法 ---
 * @method static \Illuminate\Database\Eloquent\Builder|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Post query()
 * @method static Post|null find(mixed $id, array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection findMany(array $ids, array $columns = ['*'])
 * @method static Post findOrFail(mixed $id, array $columns = ['*'])
 * @method static Post firstOrFail(array $columns = ['*'])
 * @method static Post firstOrNew(array $attributes = [], array $values = [])
 * @method static Post firstOrCreate(array $attributes = [], array $values = [])
 * @method static Post updateOrCreate(array $attributes, array $values = [])
 * @method static Post firstOr(array $columns = ['*'], \Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Collection all(array $columns = ['*'])
 * @method static Post create(array $attributes)
 * @method static int insert(array $values)
 * @method static int insertOrIgnore(array $values)
 * @method static int upsert(array $values, mixed $uniqueBy, array|null $update = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Post withGlobalScope(string $identifier, \Closure $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|Post withoutGlobalScope(string $scope)
 * @method static \Illuminate\Database\Eloquent\Builder|Post withoutGlobalScopes(array|null $scopes = null)
 *
 * --- 常用 Eloquent 实例方法 ---
 * @method bool save(array $options = [])
 * @method bool update(array $attributes = [], array $options = [])
 * @method bool delete()
 * @method bool forceDelete()
 * @method bool restore()
 * @method $this refresh()
 * @method $this fill(array $attributes)
 * @method $this replicate(array $except = null)
 * @method bool isDirty($attributes = null)
 * @method bool isClean($attributes = null)
 * @method bool wasChanged($attributes = null)
 * @method array getDirty()
 * @method array getChanges()
 * @method mixed getOriginal($key = null, $default = null)
 * @method bool is($model)
 * @method bool isNot($model)
 * @method bool exists()
 * @method bool doesntExist()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasOne hasOne($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasMany hasMany($related, $foreignKey = null, $localKey = null)
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
 *
 * @mixin \Eloquent
 */
 abstract class Post extends Kernel\Database\BaseModel {}
}
