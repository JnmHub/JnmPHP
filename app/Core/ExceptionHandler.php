<?php

namespace App\Core;

use App\Exception\HttpException;
use Throwable; // Throwable 是 Error 和 Exception 的父接口

class ExceptionHandler
{
    /**
     * 注册异常和错误处理器
     */
    public static function register(): void
    {
        // 当一个异常未被捕获时，会调用此函数
        set_exception_handler([self::class, 'handleException']);

        // 当代码中出现错误（如E_WARNING, E_NOTICE等）时，会调用此函数
        // 我们将所有错误都转换为 ErrorException，然后由 handleException 统一处理
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                // This error code is not included in error_reporting
                return;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    /**
     * 统一处理所有异常和错误
     * @param Throwable $e
     */
    public static function handleException(Throwable $e): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        // ✅ 新增逻辑：处理自定义的 HttpException
        if ($e instanceof HttpException) {
            http_response_code($e->statusCode);
            foreach ($e->headers as $name => $value) {
                header($name . ': ' . $value);
            }
            self::renderJsonError($e,$e->statusCode);
            return;
        }
        http_response_code(500);
        self::renderJsonError($e,500);
    }

    /**
     * 暂时无用
     * 渲染开发环境下的错误页面
     * @param Throwable $e
     */
    private static function renderDevError(Throwable $e): void
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
    private static function renderJsonError(Throwable $e,int $code): void
    {
        JsonResponse::error(message: $e->getMessage(), code: $code)->send();
    }
}