<?php

namespace Kernel\Database;

use Illuminate\Database\Eloquent\Model;
use Kernel\Database\Traits\HasAttributes;
use Kernel\Database\Traits\HasCrud;
use Kernel\Database\Traits\HasFillable;
use Kernel\Database\Traits\HasMetadata;

abstract class BaseModel extends Model
{
    use HasMetadata,
        HasAttributes,
        HasCrud,
        HasFillable;
}