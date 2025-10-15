<?php

namespace Kernel\Attribute\Http;

use Attribute;

/**
 * 将方法参数标记为应从 HTTP 请求体（JSON）创建的目标对象。
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class RequestBody
{
    // 这个注解本身不需要任何参数，它的存在就是一种标记。
    public function __construct()
    {
    }
}