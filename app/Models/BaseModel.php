<?php

namespace App\Models;

use App\Attribute\BelongsTo;
use App\Attribute\HasMany;
use App\Attribute\HasOne;
use App\Attribute\TableField;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use BadMethodCallException;
abstract class BaseModel extends Model
{
    /**
     * @var array 用于缓存已解析的类元数据，避免重复反射造成性能损耗
     */
    private static array $classMetadataCache = [];

    /**
     * 动态处理对模型属性的 get/set 方法调用。
     *
     * @param string $method    被调用的方法名 (例如 "getUserName")
     * @param array  $arguments 传递给方法的参数 (例如 ["Gemini"])
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        // 1. 检查方法名是 getter 还是 setter
        $prefix = substr($method, 0, 3);
        if ($prefix !== 'get' && $prefix !== 'set') {
            // 如果不是 get/set 开头，则调用父类的 __call 方法，保持 Eloquent 原有功能
            return parent::__call($method, $arguments);
        }

        // 2. 从方法名中提取属性名 (例如 "getUserName" -> "userName")
        $propertyName = lcfirst(substr($method, 3));

        // 3. 检查这个属性是否在我们通过注解定义的元数据中
        $metadata = $this->getMetadata();
        if (!array_key_exists($propertyName, $metadata['mappings'])) {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()', static::class, $method
            ));
        }

        // 4. 获取数据库列名 (例如 "userName" -> "name")
        $columnName = $metadata['mappings'][$propertyName];

        // 5. 根据是 get 还是 set 执行相应操作
        if ($prefix === 'get') {
            // Getter: 例如 $user->getUserName()
            return $this->getAttribute($columnName);
        }

        if ($prefix === 'set') {
            // Setter: 例如 $user->setUserName("Gemini")
            if (count($arguments) !== 1) {
                throw new \ArgumentCountError(sprintf(
                    'Method %s::%s() expects exactly one argument, %d given', static::class, $method, count($arguments)
                ));
            }
            $this->setAttribute($columnName, $arguments[0]);
            return $this; // 返回 $this 以支持链式调用
        }
    }

    /**
     * 获取当前模型类的所有元数据（主键、可填充字段等）。
     *
     * @return array
     */
    protected function getMetadata(): array
    {
        $class = static::class;
        if (isset(self::$classMetadataCache[$class])) {
            return self::$classMetadataCache[$class];
        }

        $reflector = new ReflectionClass($class);
        $properties = $reflector->getProperties();

        $metadata = [
            'primaryKey' => 'id',
            'fillable' => [], // 可填入
            'mappings' => [], // ✅ 新增：用于存储 [属性名 => 列名] 的映射
            'relations' => [] // 关联关系元数据
        ];

        foreach ($properties as $property) {
            $attributes = $property->getAttributes(TableField::class);
            $propertyName = $property->getName();
            if (!empty($attributes)) {
                $field = $attributes[0]->newInstance();

                // 如果注解中未指定列名，则默认列名等于属性名
                $columnName = $field->columnName ?? $propertyName;
                // 存储映射关系
                $metadata['mappings'][$propertyName] = $columnName;
                if ($field->isPrimaryKey) {
                    // 主键的属性名
                    $metadata['primaryKey'] = $propertyName;
                }
                if ($field->isFillable) {
                    // 可填充的属性名
                    $metadata['fillable'][] = $propertyName;
                }
                continue;
            }

            $relationAttributes = $property->getAttributes(HasMany::class);
            if (!empty($relationAttributes)) {
                $relation = $relationAttributes[0]->newInstance();
                $metadata['relations'][$propertyName] = ['type' => 'HasMany', 'config' => $relation];
                continue; // 这是一个关系属性，跳过后续解析
            }

            $relationAttributes = $property->getAttributes(BelongsTo::class);
            if (!empty($relationAttributes)) {
                $relation = $relationAttributes[0]->newInstance();
                $metadata['relations'][$propertyName] = ['type' => 'BelongsTo', 'config' => $relation];
                continue;
            }
            $relationAttributes = $property->getAttributes(HasOne::class);
            if (!empty($relationAttributes)) {
                $relation = $relationAttributes[0]->newInstance();
                $metadata['relations'][$propertyName] = ['type' => 'HasOne', 'config' => $relation];
            }
        }

        return self::$classMetadataCache[$class] = $metadata;
    }
    /**
     * ✅ 核心改动：重写 __get 魔术方法来处理关联关系加载
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        // 1. 检查访问的属性是否是我们定义的关联关系
        $relations = $this->getMetadata()['relations'];
        if (array_key_exists($key, $relations)) {
            // 2. 如果关系已经加载过了（缓存），直接返回
            if ($this->relationLoaded($key)) {
                return $this->getRelation($key);
            }

            // 3. 如果没加载过，则执行关联查询
            $relationMeta = $relations[$key];
            $config = $relationMeta['config'];

            switch ($relationMeta['type']) {
                case 'HasOne':
                    $relationQuery = $this->hasOne($config->related, $config->foreignKey, $config->localKey);
                    // HasOne 返回单个模型实例
                    return $this->getRelations()[$key] = $relationQuery->first();

                case 'HasMany':
                    $relationQuery = $this->hasMany($config->related, $config->foreignKey, $config->localKey);
                    // 执行查询并缓存结果
                    return $this->getRelations()[$key] = $relationQuery->get();
                case 'BelongsTo':
                    $relationQuery = $this->belongsTo($config->related, $config->foreignKey, $config->ownerKey);
                    // 执行查询并缓存结果
                    return $this->getRelations()[$key] = $relationQuery->first();
            }
        }

        // 4. 如果不是关联关系，则调用 Eloquent 的默认行为
        return parent::__get($key);
    }
    /**
     * ✅ 核心改动：重写 toArray 方法，实现属性名到列名的转换
     *
     * @return array
     */
    public function toArray(): array
    {
        // 1. 先获取 Eloquent 默认的、以“列名”为键的数组
        $attributes = parent::toArray();
        if(!($attributes)) {
            return [];
        }
        $newArray = [];

        // 2. 获取我们定义的 [属性名 => 列名] 映射
        $mappings = $this->getMetadata()['mappings'];

        // 3. 创建一个反向映射 [列名 => 属性名] 以便快速查找
        $reverseMappings = array_flip($mappings);

        // 4. 遍历原始属性数组
        foreach ($attributes as $columnName => $value) {
            // 如果这个列名在我们的反向映射中，就使用我们定义的属性名作为新键
            // 否则，保持原样 (例如 created_at, updated_at 等)
            $propertyName = $reverseMappings[$columnName] ?? $columnName;
            $newArray[$propertyName] = $value;
        }

        return $newArray;
    }

    public function isFillable($key): bool
    {
        // isFillable 应该检查“属性名”，而不是“列名”
        return in_array($key, $this->getMetadata()['fillable']);
    }

    public function getKeyName(): string
    {
        // getKeyName 返回的是“列名”
        $primaryKeyProperty = $this->getMetadata()['primaryKey'];
        return $this->getMetadata()['mappings'][$primaryKeyProperty] ?? $primaryKeyProperty;
    }

    // 我们还需要稍微调整 fillFrom 来处理列名映射
    public function fillFrom(array $data, array $map = []): static
    {
        if (empty($map) && method_exists($this, 'getFieldMap')) {
            $map = $this->getFieldMap();
        }

        $attributesToFill = [];
        $modelMappings = $this->getMetadata()['mappings'];

        foreach ($data as $key => $value) {
            $propertyName = $map[$key] ?? $key;

            if ($this->isFillable($propertyName)) {
                // 填充时，使用 Eloquent 能识别的“列名”作为键
                $columnName = $modelMappings[$propertyName] ?? $propertyName;
                $attributesToFill[$columnName] = $value;
            }
        }

        // Eloquent 的 fill 方法需要以“列名”为键的数组
        $this->fill($attributesToFill);

        return $this;
    }


    /**
     * ===================================================================
     * 查 (Read)
     * ===================================================================
     */

    /**
     * 根据主键ID快速获取单个模型实例。
     * 这是对 Eloquent find() 方法的一个更简洁的别名。
     *
     * @param int|string $id
     * @param array $columns
     * @return static|null  // 使用 static 关键字确保返回的是子类实例，例如 User 实例
     */
    public static function getById(int|string $id, array $columns = ['*']): ?static
    {
        return static::find($id, $columns);
    }

    /**
     * 快速获取所有模型实例的列表。
     *
     * @param array $columns
     * @return Collection
     */
    public static function list(array $columns = ['*'])
    {
        return static::all($columns);
    }

    /**
     * 快速获取分页后的模型实例列表。
     *
     * @param int $perPage 每页数量
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public static function page(int $perPage = 15, array $columns = ['*'])
    {
        return static::paginate($perPage, $columns);
    }

    /**
     * ===================================================================
     * 增 (Create) 与 快速赋值
     * ===================================================================
     */

    /**
     * 快速创建一条新记录。
     * 这个方法直接使用了 Eloquent 的 create 方法，它会自动处理 “快速赋值”。
     * !!! 重要：为了安全，必须在子类中定义 $fillable 属性来指定哪些字段可以被批量赋值。
     *
     * @param array $attributes
     * @return static
     */
    public static function quickCreate(array $attributes): static
    {
        return static::create($attributes);
    }


    /**
     * ===================================================================
     * 改 (Update)
     * ===================================================================
     */

    /**
     * 根据主键ID快速更新一条记录。
     *
     * @param int|string $id
     * @param array $values
     * @return bool 是否成功
     */
    public static function quickUpdateById(int|string $id, array $values): bool
    {
        $model = static::find($id);
        if ($model) {
            return $model->update($values);
        }
        return false;
    }


    /**
     * ===================================================================
     * 删 (Delete)
     * ===================================================================
     */

    /**
     * 根据主键ID快速删除一条或多条记录。
     *
     * @param int|string|array $ids
     * @return int 返回被删除的记录数
     */
    public static function deleteById(int|string|array $ids): int
    {
        return static::destroy($ids);
    }
}