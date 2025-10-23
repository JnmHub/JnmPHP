<?php

namespace App\Middleware;

use Closure;
use Kernel\Exception\HttpException;
use Kernel\Middleware\MiddlewareInterface;
use Kernel\Request\Request;
use Kernel\Session\SessionManager;

class VerifyCsrfTokenMiddleware implements MiddlewareInterface
{
    protected SessionManager $session;

    /**
     * 不需要 CSRF 验证的 URI
     */
    protected array $except = [
        // 'api/v1/*'
    ];

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    /**
     * @throws HttpException
     */
    public function handle(mixed $request, Closure $next)
    {
        if (
            $this->isReading($request) ||
            $this->inExceptArray($request) ||
            $this->tokensMatch($request)
        ) {
            return $next($request);
        }
        throw new HttpException(419, 'CSRF token mismatch.');
    }

    /**
     * 检查是否为读请求 (GET, HEAD, OPTIONS)
     */
    protected function isReading(Request $request): bool
    {
        return in_array(strtoupper($request->method), ['GET', 'HEAD', 'OPTIONS']);
    }

    /**
     * 检查 URI 是否在豁免列表中
     */
    protected function inExceptArray(Request $request): bool
    {
        $uri = trim($request->uri, '/');
        foreach ($this->except as $except) {
            $except = trim($except, '/');
            if ($except === $uri || str_starts_with($uri, $except)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 验证 Token 是否匹配
     */
    protected function tokensMatch(Request $request): bool
    {
        // 1. 从 Session 获取 Token
        $sessionToken = $this->session->token();
        // TODO 校验是否过期，或者 CSRF 不校验过期
        // 2. 从请求体 (POST 数据) 获取 Token
        $requestToken = $request->post['_token'] ?? null;

        // 3. (可选) 从 Header 获取 Token (用于 AJAX)
        if (empty($requestToken)) {
            $requestToken = $request->header['x-csrf-token'][0] ?? null;
        }

        if (!is_string($sessionToken) || !is_string($requestToken)) {
            return false;
        }

        // 使用 hash_equals 防止时序攻击
        return hash_equals($sessionToken, $requestToken);
    }
}