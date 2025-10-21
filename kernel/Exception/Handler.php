<?php

namespace Kernel\Exception;

use ErrorException;
use Illuminate\Validation\ValidationException;
use Kernel\Response\JsonResponse;
use Psr\Log\LoggerInterface;
use Throwable;


class Handler
{

    protected LoggerInterface $logger;



    /**
     * 构造函数注入 Logger
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 新增：处理 Error (用于 set_error_handler)
     *
     * @param $severity
     * @param $message
     * @param $file
     * @param $line
     * @throws ErrorException
     */
    public function handleError($severity, $message, $file, $line): void
    {
        if (!(error_reporting() & $severity)) {
            return;
        }
        // 将 PHP Error 转换为异常，抛出
        // 这将被下面的 handleException 捕获
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * 统一处理所有异常 (用于 set_exception_handler)
     * @param Throwable $e
     */
    public function handleException(Throwable $e): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }


        if (!$this->shouldntReport($e)) {
            $this->logger->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // 2. 渲染响应
        if ($e instanceof HttpException) {
            http_response_code($e->statusCode);
            foreach ($e->headers as $name => $value) {
                header($name . ': ' . $value);
            }
            self::renderJsonError($e, $e->statusCode);
            return;
        }

        if ($e instanceof ValidationException) {
            http_response_code(422);
            JsonResponse::error('Validation Failed', 422, $e->errors())->send();
            return;
        }

        // 500 错误
        http_response_code(500);

        // 根据 DEBUG 模式选择渲染
        if (defined('DEBUG') && DEBUG) {
            self::renderDevError($e, 500); // 使用你写的 static 方法
        } else {
            self::renderProdError(); // 使用你写的 static 方法
            // 生产环境下也可以额外返回JSON
            // JsonResponse::error('Server Error', 500)->send();
        }
    }

    /**
     * 帮助函数：判断是否需要报告为 Error 级别
     */
    protected function shouldntReport(Throwable $e): bool
    {
        return $e instanceof HttpException || $e instanceof ValidationException;
    }

    /**
     * 暂时无用
     * 渲染开发环境下的错误页面
     * @param Throwable $e
     */
    private static function renderDevError(Throwable $e, int $code): void
    {
        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '    <meta charset="UTF-8">';
        echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '    <title>Framework Error</title>';
        echo '    <style>';
        echo '        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 40px; background-color: #f9f9f9; color: #333; }';
        echo '        .container { max-width: 800px; margin: 0 auto; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }';
        echo '        h1 { color: #d9534f; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 0; }';
        echo '        p { margin: 5px 0; }';
        echo '        strong { display: inline-block; width: 80px; }';
        echo '        .stack-trace { background: #eee; padding: 15px; border-radius: 5px; margin-top: 20px; white-space: pre-wrap; word-wrap: break-word; font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace; font-size: 13px; line-height: 1.6; }';
        echo '    </style>';
        echo '</head>';
        echo '<body>';
        echo '    <div class="container">';
        echo '        <h1>Oops! Something went wrong.</h1>';
        echo '        <p><strong>Type:</strong> ' . get_class($e) . '</p>';
        echo '        <p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '        <p><strong>File:</strong> ' . $e->getFile() . '</p>';
        echo '        <p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        echo '        <div class="stack-trace"><strong>Stack Trace:</strong><br>' . nl2br(htmlspecialchars($e->getTraceAsString())) . '</div>';
        echo '    </div>';
        echo '</body>';
        echo '</html>';
    }

    /**
     * 暂时无用
     * 渲染生产环境下的错误页面
     */
    private static function renderProdError(): void
    {
        // 在实际项目中，这里应该加载一个美观的视图文件
        // 也可以记录日志
        // error_log($e->getMessage() . "\n" . $e->getTraceAsString());

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '    <meta charset="UTF-8">';
        echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '    <title>Error</title>';
        echo '    <style>';
        echo '        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; text-align: center; padding: 150px; background-color: #f5f5f5; }';
        echo '        h1 { font-size: 48px; color: #555; }';
        echo '        p { font-size: 20px; color: #777; }';
        echo '    </style>';
        echo '</head>';
        echo '<body>';
        echo '    <h1>Server Error</h1>';
        echo '    <p>We are sorry, but something went wrong on our end.</p>';
        echo '</body>';
        echo '</html>';
    }

    private static function renderJsonError(Throwable $e, int $code): void
    {
        JsonResponse::error(message: $e->getMessage(), code: $code)->send();
    }
}