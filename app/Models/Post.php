<?php

namespace App\Models;

use Kernel\Attribute\Database\BelongsTo;
use Kernel\Attribute\Database\BelongsToMany;
use Kernel\Attribute\Database\TableField;
use Kernel\Database\BaseModel;

/**
 * --- PHPDoc for IDE ---
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property-read User $user
 */
class Post extends BaseModel
{
    #[TableField(isPrimaryKey: true, isFillable: false)]
    protected int $id;

    #[TableField]
    protected string $title;

    #[TableField]
    protected string $content;

    #[TableField('user_id',isFillable: false)] // user_id 通常在代码中设置，不通过批量赋值
    protected int $userId;

    /**
     * 定义 Post 从属于 User 的关系
     */
    #[BelongsTo(related: User::class, foreignKey: 'user_id')]
    protected User $user;

    #[BelongsToMany(related: Tag::class, table: 'post_tag', foreignPivotKey: 'post_id', relatedPivotKey: 'tag_id')]
    protected array $tags;
}