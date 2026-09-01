<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\service\JwtService;
use think\facade\Db;
use think\Request;
use think\Response;

final class MemberController extends ApiController
{
    public function profile(): Response
    {
        $member = $this->requireMember();
        $profile = Db::name('member_profiles')->where('member_id', $member->id)->find() ?: [];

        return $this->success([
            'id' => (string) $member->id,
            'email' => $member->email,
            'name' => $member->name,
            'avatar' => $member->avatar,
            'status' => (int) $member->status,
            'permissions' => $member->getPermissionCodes(),
            'phone' => $profile['phone'] ?? null,
            'job_title' => $profile['job_title'] ?? null,
            'department' => $profile['department'] ?? null,
            'locale' => $profile['locale'] ?? 'zh-CN',
            'notification_preferences' => isset($profile['notification_preferences'])
                ? json_decode($profile['notification_preferences'], true)
                : null,
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $member = $this->requireMember();
        $this->validate($request->post(), [
            'name' => 'require|max:80',
            'phone' => 'max:32',
            'job_title' => 'max:120',
            'department' => 'max:120',
            'locale' => 'in:zh-CN,en-US',
            'notification_preferences' => 'array',
        ]);

        $data = $request->post();
        $now = date('Y-m-d H:i:s');
        $member->save(['name' => $data['name']]);

        $profileData = [
            'phone' => $data['phone'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'department' => $data['department'] ?? null,
            'locale' => $data['locale'] ?? 'zh-CN',
            'notification_preferences' => isset($data['notification_preferences'])
                ? json_encode($data['notification_preferences'], JSON_UNESCAPED_UNICODE)
                : null,
            'updated_at' => $now,
        ];
        $exists = Db::name('member_profiles')->where('member_id', $member->id)->find();
        if ($exists) {
            Db::name('member_profiles')->where('member_id', $member->id)->update($profileData);
        } else {
            Db::name('member_profiles')->insert(['member_id' => $member->id, ...$profileData]);
        }

        return $this->profile();
    }

    public function sessions(): Response
    {
        $member = $this->requireMember();
        $items = Db::name('member_sessions')
            ->where('member_id', $member->id)
            ->whereNull('revoked_at')
            ->order('last_seen', 'desc')
            ->select();

        return $this->success($items);
    }

    public function revokeSession(Request $request, string $id): Response
    {
        $member = $this->requireMember();
        $affected = Db::name('member_sessions')
            ->where('member_id', $member->id)
            ->where('session_id', $id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);

        if ($affected === 0) {
            throw BusinessException::notFound('设备会话不存在');
        }

        (new JwtService())->revokeRefreshToken($id);
        return $this->success(null, '设备会话已撤销');
    }
}
