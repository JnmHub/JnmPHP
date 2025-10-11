<?php

namespace App\Exception;

use Exception;

class BaseException extends Exception
{
    // 你可以在这里添加所有自定义异常共有的属性或方法
    // 例如，可以强制所有子类都定义一个错误码
}