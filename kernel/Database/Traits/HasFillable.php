<?php

namespace Kernel\Database\Traits;

trait HasFillable
{
    /**
     * 检查给定属性是否可批量赋值。
     */
    public function isFillable($key): bool
    {
        return in_array($key, $this->getMetadata()['fillable']);
    }

    /**
     * 获取模型的主键列名。
     */
    public function getKeyName(): string
    {
        $primaryKeyProperty = $this->getMetadata()['primaryKey'];
        return $this->getMetadata()['mappings'][$primaryKeyProperty] ?? $primaryKeyProperty;
    }

    /**
     * @return array
     * 获取所有对应关系
     */
    public function getArray():array
    {
        $attributes = parent::toArray();
        if (!$attributes) {
            return [];
        }
        $newArray = [];
        $metadata = $this->getMetadata();
        $reverseMappings = $metadata['reverseMappings'];
        foreach ($attributes as $columnName => $value) {
            if (isset($reverseMappings[$columnName])) {
                $newArray[$reverseMappings[$columnName]] = $value;
            }
            $newArray[$columnName] = $value;
        }
        return $newArray;
    }
    /**
     * 将模型实例转换为数组。
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();
        if (!$attributes) {
            return [];
        }
        $newArray = [];
        $metadata = $this->getMetadata();
        $reverseMappings = $metadata['reverseMappings'];
        $hidden = $metadata['hidden'];
        foreach ($attributes as $columnName => $value) {
            if(array_key_exists($columnName, $metadata['accessors'])){
                $arrays = $this->getArray();
                $value =  $this->{$metadata['accessors'][$columnName]}($value,$arrays);
            }
            $propertyName = $reverseMappings[$columnName] ?? $columnName;

            // 如果属性在 hidden 列表中，则跳过
            if (in_array($propertyName, $hidden)) {
                continue;
            }

            $newArray[$propertyName] = $value;
        }

        foreach ($metadata['appends'] as $propertyName) {
            $newArray[$propertyName] = $this->getAttribute($propertyName);
        }
        foreach ($this->getRelations() as $relationName => $relationValue) {
            if ($relationValue) {
                $newArray[$relationName] = $relationValue->toArray();
            }
        }

        return $newArray;
    }

    /**
     * 从一个数组填充模型属性，支持字段名映射。
     */
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
                $columnName = $modelMappings[$propertyName] ?? $propertyName;
                $attributesToFill[$columnName] = $value;
            }
        }

        $this->fill($attributesToFill);

        return $this;
    }
}