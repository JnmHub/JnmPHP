<?php

namespace Kernel\Attribute\Database;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasManyThrough
{
    /**
     * 定义一个 "Has Many Through" 远程一对多 关联
     *
     * @param string $related 最终关联的模型 (如: Post::class)
     * @param string $through 中间模型 (如: User::class)
     * @param string|null $firstKey 中间模型上的外键 (如: users.country_id)
     * @param string|null $secondKey 最终模型上的外键 (如: posts.user_id)
     * @param string|null $localKey 起始模型上的主键 (如: countries.id)
     * @param string|null $secondLocalKey 中间模型上的主键 (如: users.id)
     */
    public function __construct(
        public string $related,
        public string $through,
        public ?string $firstKey = null,
        public ?string $secondKey = null,
        public ?string $localKey = null,
        public ?string $secondLocalKey = null
    ) {
    }
}