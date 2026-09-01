<?php
declare (strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\Member;
use app\common\service\JwtService;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * 认证控制器
 *
 * POST /api/v1/auth/login     登录（邮箱 + 密码）
 * POST /api/v1/auth/refresh   刷新访问令牌（轮换刷新令牌）
 * POST /api/v1/auth/logout    登出（撤销当前会话）
 */
class AuthController extends ApiController
{
    /**
     * 登录
     */
    public function login(Request $request): Response
    {
        $this->validate($request->post(), [
            'email'    => 'require|email',
            'password' => 'require|max:128',
        ], [
            'email.require'    => '邮箱不能为空',
            'email.email'      => '邮箱格式不正确',
            'password.require' => '密码不能为空',
        ]);

        $data = $request->post();
        $member = Member::where('email', $data['email'])->find();
        if (!$member || !$member->verifyPassword($data['password'])) {
            throw BusinessException::unauthenticated('邮箱或密码错误');
        }
        if ($member->status !== 1) {
            throw BusinessException::forbidden('账号已被停用');
        }

        // 创建设备会话
        $sessionId = bin2hex(random_bytes(24));
        Db::name('member_sessions')->insert([
            'member_id'  => $member->id,
            'session_id' => $sessionId,
            'device'     => $request->header('User-Agent', ''),
            'ip'         => $request->ip(),
            'created_at' => date('Y-m-d H:i:s'),
            'last_seen'  => date('Y-m-d H:i:s'),
        ]);

        $jwt = new JwtService();
        $refreshToken = $jwt->issueRefreshToken((int) $member->id, $sessionId, $request->header('User-Agent', ''));
        $accessToken = $jwt->issueAccessToken((int) $member->id, $sessionId);

        return $this->success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) env('JWT_ACCESS_TTL', 7200),
            'member'        => [
                'id'        => $member->id,
                'email'     => $member->email,
                'name'      => $member->name,
            ],
        ], '登录成功');
    }

    /**
     * 刷新令牌（轮换）
     */
    public function refresh(Request $request): Response
    {
        $this->validate($request->post(), [
            'refresh_token' => 'require',
        ], ['refresh_token.require' => '缺少刷新令牌']);
        $data = $request->post();

        $jwt = new JwtService();
        $payload = $jwt->parseRefreshToken($data['refresh_token']);
        if (!$payload) {
            throw BusinessException::unauthenticated('刷新令牌无效或已过期');
        }

        $memberId = (int) $payload['sub'];
        $sessionId = (string) $payload['sid'];
        $member = Member::find($memberId);
        if (!$member || $member->status !== 1) {
            throw BusinessException::forbidden('账号已被停用');
        }

        // 轮换：撤销旧刷新令牌，签发新的
        $jwt->revokeRefreshToken($sessionId);
        Db::name('member_sessions')->where('session_id', $sessionId)->update([
            'last_seen' => date('Y-m-d H:i:s'),
        ]);

        $newRefresh = $jwt->issueRefreshToken($memberId, $sessionId);
        $newAccess = $jwt->issueAccessToken($memberId, $sessionId);

        return $this->success([
            'access_token'  => $newAccess,
            'refresh_token' => $newRefresh,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) env('JWT_ACCESS_TTL', 7200),
        ], '刷新成功');
    }

    /**
     * 登出
     */
    public function logout(Request $request): Response
    {
        $jwt = new JwtService();
        $sessionId = (string) $request->sessionId;
        if ($sessionId !== '') {
            $jwt->revokeSession($sessionId);
        }
        return $this->success(null, '已退出登录');
    }
}
