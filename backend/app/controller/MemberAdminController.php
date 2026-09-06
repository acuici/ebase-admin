<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\Member;
use app\common\service\MemberListService;
use app\common\service\WarehouseService;
use app\validate\MemberAdminValidate;
use think\App;
use think\facade\Cache;
use think\facade\Db;
use think\Request;
use think\Response;

final class MemberAdminController extends ApiController
{
    private MemberListService $lists;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->lists = new MemberListService();
    }

    public function index(Request $request): Response
    {
        return $this->success($this->lists->list($request->get()));
    }

    public function read(int $id): Response
    {
        $member = Member::with('roles')->find($id);
        if (!$member) {
            throw BusinessException::notFound('成员不存在');
        }
        return $this->success($member);
    }

    public function invite(Request $request): Response
    {
        $this->validate($request->post(), MemberAdminValidate::class);
        $data = $request->post();
        if (Member::where('email', $data['email'])->find()) {
            throw new BusinessException('RESOURCE_CONFLICT', '该邮箱已存在', 409);
        }
        $temporaryPassword = bin2hex(random_bytes(12));
        $member = Db::transaction(function () use ($data, $temporaryPassword) {
            $now = date('Y-m-d H:i:s');
            $id = Db::name('members')->insertGetId([
                'email' => $data['email'],
                'name' => $data['name'],
                'member_code' => $this->newMemberCode($data['email']),
                'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
                'status' => 1,
                'is_super' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($data['role_ids'] ?? [] as $roleId) {
                Db::name('member_roles')->insert(['member_id' => $id, 'role_id' => $roleId, 'created_at' => $now]);
            }
            return Member::find($id);
        });
        $token = bin2hex(random_bytes(24));
        Cache::store('redis')->set('auth:invite:' . $token, json_encode(['member_id' => $member->id]), 86400);
        return $this->success(['member' => $member, 'invite_token' => $token, 'expires_in' => 86400], '成员已邀请', 201);
    }

    public function update(Request $request, int $id): Response
    {
        $member = Member::find($id);
        if (!$member) {
            throw BusinessException::notFound('成员不存在');
        }
        $data = $request->post();
        $wasActive = (int) $member->status === 1;
        $this->validate(array_merge($member->toArray(), $data), MemberAdminValidate::class);
        Db::transaction(function () use ($member, $data) {
            $update = [];
            foreach (['name', 'email', 'status'] as $key) {
                if (array_key_exists($key, $data)) {
                    $update[$key] = $data[$key];
                }
            }
            if ($update) {
                $member->save($update);
            }
            if (array_key_exists('role_ids', $data)) {
                Db::name('member_roles')->where('member_id', $member->id)->delete();
                foreach (array_unique($data['role_ids']) as $roleId) {
                    Db::name('member_roles')->insert(['member_id' => $member->id, 'role_id' => $roleId, 'created_at' => date('Y-m-d H:i:s')]);
                }
            }
        });
        Cache::store('redis')->delete('perm:member:' . $id);
        $requestId = (string) ($request->requestId ?? $request->header('x-request-id', ''));
        if ($wasActive && array_key_exists('status', $data) && (int) $data['status'] === 0) {
            (new WarehouseService())->memberOffboard($id, (int) $this->requireMember()->id, $requestId);
        }
        if (!$wasActive && array_key_exists('status', $data) && (int) $data['status'] === 1) {
            (new WarehouseService())->recoverForMember($id, (int) $this->requireMember()->id, $requestId);
        }
        return $this->success(Member::with('roles')->find($id), '成员已更新');
    }

    public function disable(Request $request, int $id): Response
    {
        $member = Member::find($id);
        if (!$member) {
            throw BusinessException::notFound('成员不存在');
        }
        if ($member->is_super) {
            throw new BusinessException('RESOURCE_CONFLICT', '不能停用超级管理员', 409);
        }
        $member->save(['status' => 0]);
        Db::name('member_sessions')->where('member_id', $id)->whereNull('revoked_at')->update(['revoked_at' => date('Y-m-d H:i:s')]);
        Cache::store('redis')->delete('perm:member:' . $id);
        $actor = $this->requireMember();
        $requestId = (string) ($request->requestId ?? $request->header('x-request-id', ''));
        $reclaimed = (new WarehouseService())->memberOffboard($id, (int) $actor->id, $requestId);
        return $this->success(['reclaimed_warehouse_count' => $reclaimed], '成员已停用，权限和负责人关系已回收');
    }

    public function resetPassword(int $id): Response
    {
        $member = Member::find($id);
        if (!$member) {
            throw BusinessException::notFound('成员不存在');
        }
        $token = bin2hex(random_bytes(24));
        Cache::store('redis')->set('auth:password-reset:' . $token, json_encode(['member_id' => $id]), 1800);
        return $this->success(['reset_token' => $token, 'expires_in' => 1800], '密码重置令牌已生成');
    }

    private function newMemberCode(string $email): string
    {
        $prefix = preg_replace('/[^a-z0-9]+/i', '_', strtolower(strtok($email, '@'))) ?: 'member';
        $candidate = trim($prefix, '_');
        if (!Db::name('members')->where('member_code', $candidate)->find()) {
            return $candidate;
        }
        return $candidate . '_' . bin2hex(random_bytes(3));
    }
}
