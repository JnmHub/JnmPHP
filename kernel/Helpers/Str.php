<?php
declare(strict_types=1);

namespace Kernel\Helpers;
class Str
{
    /**
     * 对输入数据进行 URL 解码。
     *
     * - 如果输入是字符串，则使用 `urldecode` 函数进行解码并返回结果。
     * - 如果输入不是字符串，则直接返回原始数据，不做任何处理。
     *
     * @param mixed $data 要解码的数据，可以是任何类型。
     * @return mixed 解码后的字符串，或者原始的非字符串数据。
     */
    public static function urldecode($data)
    {
        // 检查输入数据是否为字符串类型
        if (is_string($data)) {
            // 如果是字符串，则进行 URL 解码
            return urldecode($data);
        }

        // 如果不是字符串，则直接返回原始数据
        return $data;
    }
}