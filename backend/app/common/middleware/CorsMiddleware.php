<?php
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;

/** Cross-origin support for the separate Vite admin application. */
final class CorsMiddleware
{
    private const HEADERS = [
        'Access-Control-Allow-Origin' => 'http://127.0.0.1:5175',
        'Access-Control-Allow-Methods' => 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
        'Access-Control-Allow-Headers' => 'Authorization,Content-Type,Idempotency-Key,X-Request-Id',
        'Access-Control-Max-Age' => '86400',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() === 'OPTIONS') {
            return response('', 204)->header(self::HEADERS);
        }

        return $next($request)->header(self::HEADERS);
    }
}
