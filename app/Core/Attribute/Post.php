<?php
namespace App\Core\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Post extends Route
{
    public function __construct(string $path)
    {
        parent::__construct($path, ['POST']);
    }
}