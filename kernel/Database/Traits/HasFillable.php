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
     * 将模型实例转换为数组。
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();
        if (!$attributes) {
            return [];
        }

        $newArray = [];
        $reverseMappings = $this->getMetadata()['reverseMappings'];

        foreach ($attributes as $columnName => $value) {
            $propertyName = $reverseMappings[$columnName] ?? $columnName;
            $newArray[$propertyName] = $value;
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