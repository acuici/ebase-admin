<?php
declare (strict_types=1);

namespace app\common\middleware;

use app\common\exception\BusinessException;
use app\common\model\Member;
use app\common\service\JwtService;
use Closure;
use think\Request;
use think\Response;

/**
 * JWT 认证中间件
 *
 * 校验 Authorization: Bearer <token>，解析出成员并挂载到请求对象。
 * 受保护的 API 路由必须经过此中间件；未认证返回 401。
 */
class AuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);
        if (!$token) {
            throw BusinessException::unauthenticated('缺少访问令牌');
        }

        $payload = (new JwtService())->parseAccessToken($token);
        if (!$payload) {
            throw BusinessException::unauthenticated('访问令牌无效或已过期');
        }

        $member = Member::find((int) $payload['sub']);
        if (!$member || $member->status !== 1) {
            throw BusinessException::forbidden('账号已被停用');
        }

        // 挂载到请求对象，供控制器使用
        $request->member = $member;
        $request->memberId = (int) $member->id;
        $request->sessionId = (string) $payload['sid'];

        return $next($request);
    }

    /**
     * 从 Authorization 头提取 Bearer Token
     */
    protected function extractToken(Request $request): ?string
    {
        $auth = $request->header('Authorization', '');
        return (str_starts_with($auth, 'Bearer '))
            ? substr($auth, 7)
            : null;
    }
}
