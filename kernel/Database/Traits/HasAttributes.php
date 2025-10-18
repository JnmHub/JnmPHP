<?php

namespace Kernel\Database\Traits;

use BadMethodCallException;
use DateTimeInterface;
use ReflectionMethod;

trait HasAttributes
{
    /**
     * 动态处理对模型属性的 get/set 和关联关系加载。
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

            $relationQuery = match ($relationMeta['type']) {
                'BelongsToMany' => $this->belongsToMany($config->related, $config->table, $config->foreignPivotKey, $config->relatedPivotKey),
                'HasOne' => $this->hasOne($config->related, $config->foreignKey, $config->localKey),
                'HasMany' => $this->hasMany($config->related, $config->foreignKey, $config->localKey),
                'BelongsTo' => $this->belongsTo($config->related, $config->foreignKey, $config->ownerKey),
                default => null,
            };

            // HasOne 和 BelongsTo 返回单个模型实例, 其他返回集合
            $result = in_array($relationMeta['type'], ['HasOne', 'BelongsTo'])
                ? $relationQuery->first()
                : $relationQuery->get();

            return $this->getRelations()[$key] = $result;
        }

        // 4. 如果不是关联关系，则调用 Eloquent 的默认行为
        return parent::__get($key);
    }

    /**
     * 动态处理对模型属性的 get/set 方法调用。
     */
    /**
     * 【已修复】动态处理对模型属性的 get/set 方法调用。
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     * 【已修复并增强】动态处理方法调用。
     * 现在它不仅支持 get/set，还能动态处理关联关系方法的调用。
     *
     */
    public function __call($method, $parameters)
    {
        // --- 第一部分：处理我们自定义的 get/set 动态方法 ---
        $prefix = substr($method, 0, 3);
        if ($prefix === 'get' || $prefix === 'set') {
            $propertyName = lcfirst(substr($method, 3));
            $metadata = $this->getMetadata();

            if (array_key_exists($propertyName, $metadata['mappings'])) {
                $columnName = $metadata['mappings'][$propertyName];
                if ($prefix === 'get') {
                    return $this->getAttribute($columnName);
                }
                if ($prefix === 'set') {
                    $this->setAttribute($columnName, $parameters[0]);
                    return $this;
                }
            }
        }

        // --- 第二部分：【新增】处理关联关系方法的调用 ---
        $relations = $this->getMetadata()['relations'];
        // 检查被调用的方法名是否存在于我们通过注解定义的关联列表中
        if (array_key_exists($method, $relations)) {
            $relationMeta = $relations[$method];
            $config = $relationMeta['config'];

            // 根据元数据中的关联类型，调用正确的 Eloquent 关联方法并返回 Relation 对象
            return match ($relationMeta['type']) {
                'BelongsToMany' => $this->belongsToMany($config->related, $config->table, $config->foreignPivotKey, $config->relatedPivotKey),
                'HasOne' => $this->hasOne($config->related, $config->foreignKey, $config->localKey),
                'HasMany' => $this->hasMany($config->related, $config->foreignKey, $config->localKey),
                'BelongsTo' => $this->belongsTo($config->related, $config->foreignKey, $config->ownerKey),
            };
        }

        // --- 第三部分：回退到父类 ---
        // 如果以上都不是，则将调用交还给父类（例如，启动查询构建器）
        return parent::__call($method, $parameters);
    }

    /**
     * 重写 getAttribute 方法以支持访问器。
     */
    public function getAttribute($key)
    {
        $metadata = $this->getMetadata();
        $propertyName = $metadata['reverseMappings'][$key] ?? $key;

        if (array_key_exists($propertyName, $metadata['accessors'])) {
            $column = $metadata['mappings'][$propertyName] ?? $propertyName;
            $rawValue = parent::getAttribute($column);
            $arrays = $this->getArray();
            return $this->{$metadata['accessors'][$propertyName]}($rawValue,$arrays);
        }

        return parent::getAttribute($key);
    }

    /**
     * 重写 setAttribute 方法以支持修改器。
     */
    public function setAttribute($key, $value)
    {
        $metadata = $this->getMetadata();
        $propertyName = $metadata['reverseMappings'][$key] ?? $key;

        // 1. 优先检查你的注解修改器 (#[Mutator])
        // 这部分逻辑是正确的，因为它是一个明确的重写意图
        if (array_key_exists($propertyName, $metadata['mutators'])) {
            $mutatorMethod = $metadata['mutators'][$propertyName];
            $returnedValue = $this->{$mutatorMethod}($value);

            $reflector = new ReflectionMethod($this, $mutatorMethod);
            if ($reflector->getReturnType() && $reflector->getReturnType()->getName() !== 'void') {
                // 注意：这里也应该调用 parent::setAttribute 来设置，而不是直接操作 attributes 数组
                // $this->attributes[$key] = $returnedValue;  <-- 旧的方式
                parent::setAttribute($key, $returnedValue); // <-- 推荐的方式
            }
            return $this;
        }

        // 2. 核心改动：将属性名翻译成列名
        // 无论传入的是属性名(userName)还是列名(name)，都统一处理
        $columnName = $metadata['mappings'][$key] ?? $key;

        // 3. 将最终的列名和值，全权交给父类的 setAttribute 处理
        // 这样，所有 Eloquent 的原生功能（类型转换、日期处理等）都会被触发
        return parent::setAttribute($columnName, $value);
    }

    /**
     * 为数组/JSON序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}