<?php

namespace App\Models;

use App\Attribute\HasMany;
use App\Attribute\TableField;
use Illuminate\Database\Eloquent\Collection;

/**
 * --- PHPDoc for IDE ---
 * @property int $id
 * @property string $userName
 * @property string $email
 * @property-read Collection|Post[] $posts
 *
 * --- Auto Getters/Setters ---
 * @method int|null    getId()
 * @method string|null getUserName()
 * @method string|null getEmail()
 * @method $this       setUserName(string $value)
 * @method $this       setEmail(string $value)
 */
class User extends BaseModel
{
    #[TableField(isPrimaryKey: true, isFillable: false)]
    protected int $id;

    #[TableField(columnName: 'name')]
    protected string $name;

    #[TableField]
    protected string $email;

    #[TableField(isFillable: false)]
    protected string $password;

    /**
     * ✅ 定义 User 拥有多个 Post 的关系
     */
    #[HasMany(related: Post::class)]
    protected array $posts;
}