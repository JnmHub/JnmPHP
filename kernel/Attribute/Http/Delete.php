<?php
namespace Kernel\Attribute\Http;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Delete extends Route
{
    public function __construct(string $path)
    {
        parent::__construct($path, ['DELETE']);
    }
}