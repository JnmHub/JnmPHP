<?php

namespace Kernel\Attribute;

use Attribute;

/**
 * 标记一个方法为“访问器” (Accessor)。
 * 当获取模型属性时，该方法会被自动调用。
 */
#[Attribute(Attribute::TARGET_METHOD)] // 这个注解只能用在方法上
class Accessor
{
}