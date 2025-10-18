<?php

namespace Kernel\Database\Traits;

use Illuminate\Database\Eloquent\MassAssignmentException;

trait HasFillable
{

    /**
     * 【新增】重写 fillableFromArray 方法，这是执行智能转换的最佳位置。
     *
     * Eloquent 在执行批量赋值时，会先调用这个方法来过滤和准备数据。
     *
     * @param  array  $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        $metadata = $this->getMetadata();
        $mappings = $metadata['mappings']; // 属性名 => 列名
        $reverseMappings = $metadata['reverseMappings']; // 列名 => 属性名

        $processedAttributes = [];

        // 遍历传入的所有数据
        foreach ($attributes as $key => $value) {
            // 将传入的 key (可能是属性名或列名) 统一转换为【属性名】
            $propertyName = $reverseMappings[$key] ?? $key;

            // 检查这个【属性名】是否是 truly fillable
            if ($this->isFillable($propertyName)) {
                // 将它的【列名】作为 key 放入最终的数组
                $columnName = $mappings[$propertyName] ?? $propertyName;
                $processedAttributes[$columnName] = $value;
            }else{
                throw new MassAssignmentException(sprintf(
                    'Add [%s] to fillable property to allow mass assignment on [%s].',
                    $key, get_class($this)
                ));
            }
        }

        // 返回一个键全部是【列名】的、且已经过白名单过滤的干净数组
        return $processedAttributes;
    }


    /**
     * 【已修正】检查给定属性是否可批量赋值，使其能识别属性名和列名。
     *
     * @param  string  $key
     * @return bool
     */
    public function isFillable($key): bool
    {
        $metadata = $this->getMetadata();
        $fillableProperties = $metadata['fillable'];
        $reverseMappings = $metadata['reverseMappings'];

        // 无论传入的是列名(name)还是属性名(userName)，都先统一转成【属性名】
        $propertyName = $reverseMappings[$key] ?? $key;

        // 使用【属性名】来检查白名单
        return in_array($propertyName, $fillableProperties);
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