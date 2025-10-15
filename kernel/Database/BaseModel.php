<?php

namespace Kernel\Database;

use BadMethodCallException;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Kernel\Attribute\Accessor;
use Kernel\Attribute\BelongsTo;
use Kernel\Attribute\BelongsToMany;
use Kernel\Attribute\HasMany;
use Kernel\Attribute\HasOne;
use Kernel\Attribute\Mutator;
use Kernel\Attribute\TableField;
use ReflectionClass;
use ReflectionMethod;

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
        $is_attribute = str_ends_with($method, 'Attribute');
        if ($prefix !== 'get' && $prefix !== 'set' ||  $is_attribute) {
            // 如果不是 get/set 开头，则调用父类的 __call 方法，保持 Eloquent 原有功能
            return parent::__call($method, $arguments);
        }

        // 2. 从方法名中提取属性名 (例如 "getUserName" -> "userName")
        $propertyName = lcfirst(substr($method, 3));

        // 3. 检查这个属性是否在我们通过注解定义的元数据中
        $metadata = $this->getMetadata();
        if (!array_key_exists($propertyName, $metadata['mappings'])) {
            throw new BadMethodCallException(sprintf(
                '执行未定义的方法 %s::%s()', static::class, $method
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
                    'Function 方法 %s::%s() 期待一个参数, %d 传进来', static::class, $method, count($arguments)
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
            'mappings' => [], // ✅ 新增：用于存储 [属性名 => 列名] 的映射[ 'propertyName' => 'columnName' ]
            'reverseMappings' => [], // ✅ 新增：[ 'columnName' => 'propertyName' ]
            'relations' => [], // 关联关系元数据
            'casts' => [],
            'accessors' => [], // ✅ 新增：用于存储访问器
            'mutators' => [],  // ✅ 新增：用于存储修改器
        ];

        foreach ($properties as $property) {
            $attributes = $property->getAttributes(TableField::class);
            $propertyName = $property->getName();

            if (!empty($attributes)) {
                $field = $attributes[0]->newInstance();

                // 如果注解中未指定列名，则默认列名等于属性名
                $columnName = $field->columnName ?? $propertyName;
                // 存储正向和反向映射
                // 存储映射关系
                $metadata['mappings'][$propertyName] = $columnName;
                $metadata['reverseMappings'][$columnName] = $propertyName;

                if ($field->isPrimaryKey) {
                    // 主键的属性名
                    $metadata['primaryKey'] = $propertyName;
                }
                if ($field->isFillable) {
                    // 可填充的属性名
                    $metadata['fillable'][] = $propertyName;
                }
                if ($field->cast) {
                    // key 应该是数据库的“列名”, 而不是属性名
                    $metadata['casts'][$columnName] = $field->cast;
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
            $relationAttributes = $property->getAttributes(BelongsToMany::class);
            if (!empty($relationAttributes)) {
                $relation = $relationAttributes[0]->newInstance();
                $metadata['relations'][$propertyName] = ['type' => 'BelongsToMany', 'config' => $relation];
                continue;
            }
            $relationAttributes = $property->getAttributes(HasOne::class);
            if (!empty($relationAttributes)) {
                $relation = $relationAttributes[0]->newInstance();
                $metadata['relations'][$propertyName] = ['type' => 'HasOne', 'config' => $relation];
            }
        }
        $methods = $reflector->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->getName();
            // 解析访问器
            if (!empty($method->getAttributes(Accessor::class))) {
                // 约定：方法名格式为 getXxxAttribute, 对应属性为 xxx
                // 例如：getUserNameAttribute -> userName
                if (str_ends_with($methodName, 'Attribute') && str_starts_with($methodName, 'get')) {
                    $propertyName = lcfirst(substr($methodName, 3, -9));
                    $metadata['accessors'][$propertyName] = $methodName;
                }
            }

            // 解析修改器
            if (!empty($method->getAttributes(Mutator::class))) {
                // 约定：方法名格式为 setXxxAttribute, 对应属性为 xxx
                // 例如：setPasswordAttribute -> password
                if (str_ends_with($methodName, 'Attribute') && str_starts_with($methodName, 'set')) {
                    $propertyName = lcfirst(substr($methodName, 3, -9));
                    $metadata['mutators'][$propertyName] = $methodName;
                }
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
                case 'BelongsToMany':
                    $relationQuery = $this->belongsToMany(
                        $config->related,
                        $config->table,
                        $config->foreignPivotKey,
                        $config->relatedPivotKey
                    );
                    return $this->getRelations()[$key] = $relationQuery->get();
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
     * ✅ 核心修正：完全信任 parent::toArray() 的值，只做键名映射
     *
     * @return array
     */
    public function toArray(): array
    {
        // 1. 获取值已经完全处理好的、以“列名”为键的数组。
        //    (访问器、类型转换、日期格式化在这一步已经全部完成)
        $attributes = parent::toArray();
        if (!$attributes) {
            return [];
        }

        $newArray = [];
        $reverseMappings = $this->getMetadata()['reverseMappings'];

        // 2. 遍历这个完美的数组，我们只做一件事：替换键名。
        foreach ($attributes as $columnName => $value) {
            // 这里不再调用 getAttribute，直接使用 $value
            $propertyName = $reverseMappings[$columnName] ?? $columnName;
            $newArray[$propertyName] = $value;
        }

        // 3. 附加关联关系 (保持不变)
        foreach ($this->getRelations() as $relationName => $relationValue) {
            $newArray[$relationName] = $relationValue->toArray();
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
     * ✅ 核心修正：重写 getAttribute 方法，正确地查找并调用访问器
     */
    public function getAttribute($key)
    {
        $metadata = $this->getMetadata();

        // 1. 根据传入的 key (通常是列名), 找到对应的 PHP 属性名
        //    例如：传入 'name', 找到 'userName'
        $propertyName = $metadata['reverseMappings'][$key] ?? $key;

        // 2. 使用属性名去 accessors 缓存中查找是否存在访问器方法
        if (array_key_exists($propertyName, $metadata['accessors'])) {

            // 3. 获取该属性的原始值
            //    我们直接调用父类的 getAttribute，因为它能正确处理所有情况
            $rawValue = parent::getAttribute($key);

            // 4. 调用我们找到的访问器方法，并将原始值作为参数传入
            return $this->{$metadata['accessors'][$propertyName]}($rawValue);
        }

        // 5. 如果没有找到我们自定义的访问器，则完全交由 Eloquent 的默认流程处理
        //    (这会自动处理类型转换 casts 等)
        return parent::getAttribute($key);
    }


    /**
     * ✅ 核心修正：重写 setAttribute 方法，以支持有返回值的修改器
     */
    public function setAttribute($key, $value)
    {
        $metadata = $this->getMetadata();
        $propertyName = $key;
        $columnName = $key;
        foreach ($metadata['reverseMappings'] as $column => $property) {
            if($column === $key) {
                $propertyName = $property;
                $columnName = $column;
            }
        }
        // 检查是否存在我们定义的修改器
        if (array_key_exists($propertyName, $metadata['mutators'])) {
            $mutatorMethodName = $metadata['mutators'][$propertyName];

            // 1. 调用修改器方法，并捕获其返回值
            $returnedValue = $this->{$mutatorMethodName}($value);

            // 2. 使用反射检查修改器方法的返回类型
            $reflector = new ReflectionMethod($this, $mutatorMethodName);
            $returnType = $reflector->getReturnType();

            // 3. 判断：如果方法有返回值 (即返回类型不是 'void')
            if ($returnType && $returnType->getName() !== 'void') {
                // 就将“返回值”赋给对应的属性
                // 我们直接调用父类的 setAttribute 来设置，以避免无限循环
//                return parent::setAttribute($key, $returnedValue);
                $this->attributes[$columnName] = $returnedValue;
            }

            // 4. 如果方法的返回类型是 'void'，则我们假设它在内部自己处理了赋值
            //    (这兼容了我们之前的实现)
            return $this;
        }

        // 如果没有找到自定义的修改器，则走 Eloquent 的默认流程
        return parent::setAttribute($key, $value);
    }


    /**
     * ✅ 核心改动：重写 getCasts 方法
     * 将我们从注解中收集的类型转换规则提供给 Eloquent。
     *
     * @return array
     */
    public function getCasts(): array
    {
        // 合并 Eloquent 默认的 casts 和我们从注解中解析的 casts
        return array_merge(parent::getCasts(), $this->getMetadata()['casts']);
    }
    /**
     * 为数组/JSON序列化准备日期。
     *
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
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