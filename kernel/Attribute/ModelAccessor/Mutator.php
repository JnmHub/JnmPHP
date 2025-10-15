<?php

namespace Kernel\Attribute\ModelAccessor;

use Attribute;

/**
 * 标记一个方法为“修改器” (Mutator)。
 * 当设置模型属性时，该方法会被自动调用。
 */
#[Attribute(Attribute::TARGET_METHOD)] // 这个注解只能用在方法上
class Mutator
{
}