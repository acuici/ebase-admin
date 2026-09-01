<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\model\Member;
use think\facade\Db;
use think\Request;
use think\Response;
class MemberController extends ApiController
{
    public function profile(): Response
    {
        $member = $this->requireMember();
        return $this->success([
            'id' => $member->id,
            'email' => $member->email,
            'name' => $member->name,
            'avatar' => $member->avatar,
            'status' => $member->status,
            'permissions' => $member->getPermissionCodes(),
        ]);
    }
    public function sessions(): Response
    {
        $member = $this->requireMember();
        $items = Db::name('member_sessions')->where('member_id', $member->id)->whereNull('revoked_at')->order('last_seen', 'desc')->select();
        return $this->success($items);
    }
    public function revokeSession(Request $request, string $id): Response
    {
        $member = $this->requireMember();
        Db::name('member_sessions')->where('member_id', $member->id)->where('session_id', $id)->update(['revoked_at' => date('Y-m-d H:i:s')]);
        (new \app\common\service\JwtService())->revokeRefreshToken($id);
        return $this->success(null, '设备会话已撤销');
    }
}
