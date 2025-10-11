<?php

namespace App\Controller;

class BaseController
{
    public function view(string $viewPath, array $data = [])
    {
        // 将数组的键名作为变量名，键值作为变量值
        extract($data);
        $viewFile = APP_ROOT . "/app/View/" . $viewPath . ".php";

        // (可选但推荐) 增加一个文件存在性检查，提供更友好的错误提示
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            // 如果文件不存在，抛出一个异常而不是直接致命错误
            
            throw new \Exception("View file not found: " . $viewFile);
        }
    }
}