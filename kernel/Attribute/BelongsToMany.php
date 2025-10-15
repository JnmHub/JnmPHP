<?php

namespace Kernel\Attribute;

use Attribute;

/**
 * “多对多”关联关系注解
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsToMany
{
    /**
     * @param string      $related              关联模型的类名 (e.g., Tag::class)
     * @param string|null $table                中间表/枢纽表的表名 (e.g., 'post_tag')
     * @param string|null $foreignPivotKey      中间表中，指向“当前”模型的外键名 (e.g., 'post_id')
     * @param string|null $relatedPivotKey      中间表中，指向“关联”模型的外键名 (e.g., 'tag_id')
     */
    public function __construct(
        public string $related,
        public ?string $table = null,
        public ?string $foreignPivotKey = null,
        public ?string $relatedPivotKey = null
    ) {}
}