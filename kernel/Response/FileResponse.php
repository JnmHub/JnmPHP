<?php

namespace Kernel\Response;

use App\Exception\HttpException;

class FileResponse implements ResponseInterface
{
    protected string $filePath;
    protected ?string $downloadName;

    /**
     * @param string $filePath 服务器上的文件绝对路径
     * @param string|null $downloadName 客户端下载时显示的文件名，如果为null，则使用原文件名
     */
    public function __construct(string $filePath, ?string $downloadName = null)
    {
        if (!is_readable($filePath) || !is_file($filePath)) {
            // 如果文件不存在或不可读，抛出404异常
            throw new HttpException(404, 'File not found.');
        }
        $this->filePath = $filePath;
        $this->downloadName = $downloadName;
    }

    public function send(): void
    {
        $downloadName = $this->downloadName ?? basename($this->filePath);

        // 清空所有可能存在的输出
        if (ob_get_level()) {
            ob_end_clean();
        }

        // 设置必要的HTTP头
        if (!headers_sent()) {
            header('Content-Description: File Transfer');
            // 使用 application/octet-stream 表示这是一个通用的二进制文件
            header('Content-Type: application/octet-stream');
            // Content-Disposition a让浏览器弹出下载对话框
            // filename*=UTF-8'' 是为了处理非ASCII字符的文件名
            header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($this->filePath));
        }

        // 读取文件并输出到客户端
        // 使用 readfile() 是一个内存高效的方式，因为它不会将整个文件加载到内存中
        flush(); // 确保所有头信息都已发送
        readfile($this->filePath);
    }
}