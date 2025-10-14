<?php

namespace App\Models;

use App\Attribute\TableField;

class User extends BaseModel
{
    /**
     * Eloquent 会自动管理 created_at 和 updated_at，
     * 所以我们不需要为它们添加注解，除非有特殊需求。
     */

    #[TableField(isPrimaryKey: true, isFillable: false)] // ID 是主键，通常不允许手动填充
    protected int $id;

    #[TableField(columnName: 'name')] // 使用默认值：允许填充，列名与属性名一致
    protected string $userName;

    #[TableField]
    protected string $email;

    #[TableField(isFillable: false)]
    protected string $age;
}