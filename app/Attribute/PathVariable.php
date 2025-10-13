<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class PathVariable
{
    public string $name;
    public ?string $missingParamMessage;

    public function __construct(string $name, ?string $missingParamMessage = null)
    {
        $this->name = $name;
        $this->missingParamMessage = $missingParamMessage;
    }
}