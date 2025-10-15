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
    public function __call($method, $arguments)
    {
        $prefix = substr($method, 0, 3);
        if ($prefix !== 'get' && $prefix !== 'set' || str_ends_with($method, 'Attribute')) {
            return parent::__call($method, $arguments);
        }

        $propertyName = lcfirst(substr($method, 3));
        $metadata = $this->getMetadata();
        if (!array_key_exists($propertyName, $metadata['mappings'])) {
            throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
        }

        $columnName = $metadata['mappings'][$propertyName];

        if ($prefix === 'get') {
            return $this->getAttribute($columnName);
        }

        if ($prefix === 'set') {
            if (count($arguments) !== 1) {
                throw new \ArgumentCountError(sprintf('%s::%s() expects exactly one argument, %d given', static::class, $method, count($arguments)));
            }
            $this->setAttribute($columnName, $arguments[0]);
            return $this;
        }
    }

    /**
     * 重写 getAttribute 方法以支持访问器。
     */
    public function getAttribute($key)
    {
        $metadata = $this->getMetadata();
        $propertyName = $metadata['reverseMappings'][$key] ?? $key;

        if (array_key_exists($propertyName, $metadata['accessors'])) {
            $rawValue = parent::getAttribute($key);
            return $this->{$metadata['accessors'][$propertyName]}($rawValue);
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

        if (array_key_exists($propertyName, $metadata['mutators'])) {
            $mutatorMethod = $metadata['mutators'][$propertyName];
            $returnedValue = $this->{$mutatorMethod}($value);

            $reflector = new ReflectionMethod($this, $mutatorMethod);
            if ($reflector->getReturnType() && $reflector->getReturnType()->getName() !== 'void') {
                $this->attributes[$key] = $returnedValue;
                return $this;
            }
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * 为数组/JSON序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}