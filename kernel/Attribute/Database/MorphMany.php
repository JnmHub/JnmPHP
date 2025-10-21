<?php

namespace Kernel\Attribute\Database;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MorphMany
{
    /**
     * 定义一个多态一对多关联
     *
     * @param string $related 关联的模型 (如: Comment::class)
     * @param string $name 关联名称 (如: 'commentable')
     * @param string|null $type '..._type' 列名 (如: 'commentable_type')
     * @param string|null $id '..._id' 列名 (如: 'commentable_id')
     * @param string $localKey 本地键
     */
    public function __construct(
        public string $related,
        public string $name,
        public ?string $type = null,
        public ?string $id = null,
        public string $localKey = ''
    ) {
    }
}