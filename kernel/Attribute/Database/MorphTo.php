<?php

namespace Kernel\Attribute\Database;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MorphTo
{
    /**
     * 定义一个多态归属关联
     *
     * @param string $name 关联名称 (如: 'commentable')
     * @param string|null $type '..._type' 列名
     * @param string|null $id '..._id' 列名
     * @param string|null $ownerKey 关联的所属键
     */
    public function __construct(
        public string $name,
        public ?string $type = null,
        public ?string $id = null,
        public ?string $ownerKey = null
    ) {
    }
}