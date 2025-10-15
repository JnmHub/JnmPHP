<?php

namespace Kernel\Attribute;

use Attribute;

/**
 * “从属于”关联关系注解
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo
{
    /**
     * @param string $related    关联模型的类名 (例如 User::class)
     * @param string|null $foreignKey 当前模型上的外键名 (默认是 'user_id')
     * @param string|null $ownerKey   关联模型上的主键名 (默认是 'id')
     */
    public function __construct(
        public string $related,
        public ?string $foreignKey = null,
        public ?string $ownerKey = null
    ) {}
}