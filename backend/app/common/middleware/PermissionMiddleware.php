<?php
declare (strict_types=1);

namespace app\common\middleware;

use app\common\exception\BusinessException;
use Closure;
use think\Request;
use think\Response;

/**
 * 权限校验中间件
 *
 * 基于 RBAC：member → roles → permission_codes。
 * 用法：路由上配置权限码，如 ->middleware(PermissionMiddleware::class, 'order.order.export')
 *
 * 前端只做可见性，后端此中间件做最终授权。
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission = ''): Response
    {
        $member = $request->member ?? null;
        if (!$member) {
            throw BusinessException::unauthenticated('请先登录');
        }

        // 超级管理员跳过校验
        if ($permission === '*' || $this->isSuperAdmin($member)) {
            return $next($request);
        }

        if ($permission === '') {
            throw BusinessException::forbidden('未配置权限码');
        }

        if (!$this->hasPermission($member, $permission)) {
            throw BusinessException::forbidden('无权访问：' . $permission);
        }

        return $next($request);
    }

    protected function isSuperAdmin($member): bool
    {
        return (int) ($member->is_super ?? 0) === 1;
    }

    /**
     * 判断成员是否拥有某权限码（含缓存）
     */
    protected function hasPermission($member, string $permission): bool
    {
        $codes = $member->getPermissionCodes();
        return in_array($permission, $codes, true);
    }
}
