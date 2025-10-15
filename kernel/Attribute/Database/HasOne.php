<?php

namespace Kernel\Attribute\Database;

use Attribute;

/**
 * “一对一”关联关系注解
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne
{
    /**
     * @param string $related    关联模型的类名
     * @param string|null $foreignKey 关联模型上的外键名
     * @param string|null $localKey   当前模型上的主键名
     */
    public function __construct(
        public string $related,
        public ?string $foreignKey = null,
        public ?string $localKey = null
    ) {}
}