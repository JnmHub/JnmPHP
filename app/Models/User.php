<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // 如果你的表名不是 users，可以在这里指定
    // protected $table = 'my_users';

    // Eloquent默认会管理 created_at 和 updated_at 字段
    // 如果你没有这两个字段，可以设置 public $timestamps = false;
    /**
     * ✅ 定义可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'age', // 假设这些是用户创建时可以提供的字段
    ];
}