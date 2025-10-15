<?php
namespace Kernel\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)] // <- 重点：指定这个注解只能用在类上
class RoutePrefix
{
    public function __construct(public string $prefix)
    {
    }
}