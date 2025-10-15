<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Kernel\Attribute\Database\HasMany;
use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\ModelAccessor\Accessor;
use Kernel\Attribute\ModelAccessor\Mutator;
use Kernel\Database\BaseModel;

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
    #[TableField(isPrimaryKey: true, isFillable: false,isHidden: true)]
    protected int $id;

    #[TableField(columnName: 'name',isFillable: true)]
    protected string $userName;

    #[TableField]
    protected string $email;

    #[TableField(isFillable: false)]
    protected string $password;

    /**
     * ✅ 定义 User 拥有多个 Post 的关系
     */
    #[HasMany(related: Post::class)]
    protected array $posts;

    #[TableField(columnName: 'last_login_at', isFillable: true, cast: 'datetime')] // 时间转换
    protected ?string $lastLoginAt;
    #[TableField(isAppended: true)]
    protected string $fullName;
    /**
     * 用户名字段的访问器。
     * 无论数据库里存的是什么，获取时都会变成首字母大写的格式。
     * 例如：存的是 'gemini'，获取 $user->userName 时得到 'Gemini'。
     * @return string
     */
    #[Accessor]
    public function getUserNameAccessor(?string $value,array $u): string
    {
        return "ccc".ucfirst((string) $value);
    }

    /**
     * 密码字段的修改器。
     * 当执行 $user->password = 'secret' 时，这个方法会被自动调用。
     * 存入数据库的将是哈希后的值。
     * @param string $value
     */
    #[Mutator]
    public function setPasswordMutator(string $value): string
    {
        // 使用 PHP 内置的哈希函数
        return password_hash($value, PASSWORD_BCRYPT);
//        $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT);  // 两种使用方法

    }

    /**
     * ✅ 新增：为 fullName 属性提供一个访问器。
     * 当 toArray 方法需要追加 fullName 时，这个方法会被调用。
     * @return string
     */
    #[Accessor]
    public function getFullNameAccessor(): string
    {
        // 假设我们有一个 firstName 和 lastName 字段
        // 为简单起见，我们这里直接用 userName 演示
        return 'Full Name: ' ;
    }
}