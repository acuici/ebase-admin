<?php
declare (strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 请求 ID 中间件
 *
 * 为每个请求生成或沿用 request_id，写入请求对象与响应头。
 * 日志与响应使用同一 ID，便于问题追踪。
 */
class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('x-request-id', '')
            ?: strtoupper(bin2hex(random_bytes(8)));

        $request->requestId = $requestId;

        $response = $next($request);
        $response->header(['X-Request-Id' => $requestId]);

        return $response;
    }
}
