<?php

namespace Kernel\Database\Traits;

use Kernel\Attribute\Database\BelongsTo;
use Kernel\Attribute\Database\BelongsToMany;
use Kernel\Attribute\Database\HasMany;
use Kernel\Attribute\Database\HasOne;
use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\ModelAccessor\Accessor;
use Kernel\Attribute\ModelAccessor\Mutator;
use ReflectionClass;

trait HasMetadata
{
    /**
     * @var array 用于缓存已解析的类元数据，避免重复反射造成性能损耗
     */
    private static array $classMetadataCache = [];

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
            'fillable' => [],
            'mappings' => [],
            'reverseMappings' => [],
            'relations' => [],
            'casts' => [],
            'accessors' => [],
            'mutators' => [],
        ];

        foreach ($properties as $property) {
            $attributes = $property->getAttributes(TableField::class);
            $propertyName = $property->getName();

            if (!empty($attributes)) {
                $field = $attributes[0]->newInstance();
                $columnName = $field->columnName ?? $propertyName;
                $metadata['mappings'][$propertyName] = $columnName;
                $metadata['reverseMappings'][$columnName] = $propertyName;

                if ($field->isPrimaryKey) {
                    $metadata['primaryKey'] = $propertyName;
                }
                if ($field->isFillable) {
                    $metadata['fillable'][] = $propertyName;
                }
                if ($field->cast) {
                    $metadata['casts'][$columnName] = $field->cast;
                }
                continue;
            }

            // --- 关联关系解析 ---
            $relationConfigs = [
                HasMany::class => 'HasMany',
                BelongsTo::class => 'BelongsTo',
                BelongsToMany::class => 'BelongsToMany',
                HasOne::class => 'HasOne',
            ];

            foreach ($relationConfigs as $attributeClass => $type) {
                $relationAttributes = $property->getAttributes($attributeClass);
                if (!empty($relationAttributes)) {
                    $relation = $relationAttributes[0]->newInstance();
                    $metadata['relations'][$propertyName] = ['type' => $type, 'config' => $relation];
                    continue 2; // 继续外层循环
                }
            }
        }

        // --- 访问器和修改器解析 ---
        $methods = $reflector->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (!empty($method->getAttributes(Accessor::class))) {
                if (str_ends_with($methodName, 'Attribute') && str_starts_with($methodName, 'get')) {
                    $propertyName = lcfirst(substr($methodName, 3, -9));
                    $metadata['accessors'][$propertyName] = $methodName;
                }
            }

            if (!empty($method->getAttributes(Mutator::class))) {
                if (str_ends_with($methodName, 'Attribute') && str_starts_with($methodName, 'set')) {
                    $propertyName = lcfirst(substr($methodName, 3, -9));
                    $metadata['mutators'][$propertyName] = $methodName;
                }
            }
        }

        return self::$classMetadataCache[$class] = $metadata;
    }

    /**
     * 重写 getCasts 方法, 将从注解中收集的类型转换规则提供给 Eloquent。
     *
     * @return array
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), $this->getMetadata()['casts']);
    }
}