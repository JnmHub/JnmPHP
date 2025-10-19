<?php

namespace App\Models;

use Kernel\Database\BaseModel;
use Kernel\Attribute\Database\TableField;
use Kernel\Attribute\Validation\Validate; // 引入 Validate 注解

/**
 * --- PHPDoc for IDE ---
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property float $price
 * @property int $stock
 */
class Product extends BaseModel
{
    /**
     * 明确指定模型关联的表名
     * (如果你的表名不是 'products'，请修改这里)
     *
     * @var string
     */
    protected $table = 'products';

    #[TableField(isPrimaryKey: true, isFillable: false)]
    protected int $id;

    /**
     * 商品SKU
     * 规则: 必填, 字符串, 在 products 表的 sku 字段中唯一, 最大50字符
     */
    #[TableField(columnName: 'sku', isFillable: true)]
    #[Validate('required|string|unique:products,sku|max:50')]
    protected string $sku;

    /**
     * 商品名称
     * 规则: 必填, 字符串, 最大255字符
     */
    #[TableField(isFillable: true)]
    #[Validate('required|string|max:255')]
    protected string $name;

    /**
     * 商品价格
     * 规则: 必填, 数字, 最小值 0
     */
    #[TableField(isFillable: true)]
    #[Validate('required|numeric|min:0')]
    protected float $price;

    /**
     * 商品库存
     * 规则: 有时(可选), 整数, 最小值 0
     */
    #[TableField(isFillable: true)]
    #[Validate('sometimes|integer|min:0')]
    protected int $stock;
}