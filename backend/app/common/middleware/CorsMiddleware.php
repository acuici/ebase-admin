<?php
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * CORS for the separately-served admin UI.
 *
 * Development Vite ports may change when another process owns 5173, so local
 * loopback ports 5173–5179 are explicitly allowed. Production must set the
 * fixed CORS_ALLOWED_ORIGINS allowlist in environment configuration.
 */
final class CorsMiddleware
{
    private const LOCAL_ORIGIN_PATTERN = '#^https?://127\.0\.0\.1:517[3-9]$#';

    public function handle(Request $request, Closure $next): Response
    {
        $origin = (string) $request->header('Origin', '');
        $headers = $this->corsHeaders($origin);

        if ($request->method() === 'OPTIONS') {
            return response('', 204)->header($headers);
        }

        return $next($request)->header($headers);
    }

    private function corsHeaders(string $origin): array
    {
        $allowedOrigins = array_filter(array_map(
            'trim',
            explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
        ));

        $allowed = preg_match(self::LOCAL_ORIGIN_PATTERN, $origin) === 1
            || in_array($origin, $allowedOrigins, true);

        return [
            'Access-Control-Allow-Origin' => $allowed ? $origin : 'null',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization,Content-Type,Idempotency-Key,X-Request-Id',
            'Access-Control-Max-Age' => '86400',
            'Vary' => 'Origin',
        ];
    }
}
