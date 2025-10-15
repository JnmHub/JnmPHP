<?php

namespace Kernel\Attribute;

use Attribute;

/**
 * “一对多”关联关系注解
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasMany
{
    /**
     * @param string $related    关联模型的类名 (例如 Post::class)
     * @param string|null $foreignKey 关联模型上的外键名 (默认是 'user_id')
     * @param string|null $localKey   当前模型上的主键名 (默认是 'id')
     */
    public function __construct(
        public string $related,
        public ?string $foreignKey = null,
        public ?string $localKey = null
    ) {}
}