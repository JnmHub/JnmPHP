<?php

namespace Kernel\Attribute\Validation;

use Attribute;

/**
 * 标记一个属性的验证规则。
 */
#[Attribute(Attribute::TARGET_PROPERTY)] // 这个注解只能用在属性上
class Validate
{
    /**
     * @param string $rules 验证规则 (例如 "required|email|max:255")
     */
    public function __construct(
        public string $rules
    ) {}
}