<?php

namespace Kernel\Attribute\Database;

use Attribute;

/**
 * 模型字段注解
 * 用于在模型属性上声明其与数据库表字段的映射关系和行为。
 */
#[Attribute(Attribute::TARGET_PROPERTY)] // 这个注解只能用在类的属性上
class TableField
{
    /**
     * @param string|null $columnName 数据库中的列名，如果为null，则使用属性名
     * @param bool $isPrimaryKey 是否为主键
     * @param bool $isFillable   是否允许被批量赋值
     * @param string|null $cast     自动类型转换 (例如 'int', 'bool', 'float')
     * @param bool $isHidden 在序列化时是否隐藏该字段
     * @param bool $isAppended 是否将该（通常是计算出的）属性追加到序列化结果中
     */
    public function __construct(
        public ?string $columnName = null,
        public bool $isPrimaryKey = false,
        public bool $isFillable = true, // 默认允许填充
        public ?string $cast = null,
        public bool $isHidden = false,
        public bool $isAppended = false
    ) {}
}