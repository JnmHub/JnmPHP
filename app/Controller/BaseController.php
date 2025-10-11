<?php

namespace App\Controller;

use App\Core\Http\FileResponse;
use App\Core\Http\ViewResponse;
use App\Exception\HttpException;

class BaseController
{
    /**
     * 准备一个视图响应.
     *
     * @param string $view 视图文件路径 (例如 'index/index')
     * @param array $data 传递给视图的数据
     * @return ViewResponse 返回一个视图响应对象
     */
    protected function view(string $view, array $data = []): ViewResponse
    {
        return new ViewResponse($view, $data);
    }

    /**
     * 准备一个文件下载响应.
     *
     * @param string $filePath 服务器上的文件绝对路径
     * @param string|null $downloadName 客户端下载时显示的文件名
     * @return FileResponse
     * @throws HttpException
     */
    protected function file(string $filePath, ?string $downloadName = null): FileResponse
    {
        return new FileResponse($filePath, $downloadName);
    }
}