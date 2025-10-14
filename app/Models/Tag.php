<?php

namespace App\Models;

use App\Attribute\BelongsToMany;
use App\Attribute\TableField;
use Illuminate\Database\Eloquent\Collection;

/**
 * --- PHPDoc for IDE ---
 * @property int $id
 * @property string $name
 * @property-read Collection|Post[] $posts
 */
class Tag extends BaseModel
{
    #[TableField(isPrimaryKey: true, isFillable: false)]
    protected int $id;

    #[TableField]
    protected string $name;

    /**
     * 定义 Tag 从属于多个 Post 的反向多对多关系
     */
    #[BelongsToMany(related: Post::class, table: 'post_tag', foreignPivotKey: 'tag_id', relatedPivotKey: 'post_id')]
    protected array $posts;
}