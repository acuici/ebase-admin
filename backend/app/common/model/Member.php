<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsToMany;

/**
 * 成员模型（后台内部员工）
 *
 * 与前端「用户/消费者」严格区分。成员通过角色获得权限码。
 */
class Member extends Model
{
    protected $name = 'members';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $hidden = ['password_hash', 'refresh_token_hash'];

    // 关联角色
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'member_roles', 'role_id', 'member_id');
    }

    /**
     * 获取成员所有权限码（缓存）
     *
     * @return string[]
     */
    public function getPermissionCodes(): array
    {
        return \think\facade\Cache::remember('ebase:perm:member:' . $this->id, function () {
            $codes = [];
            foreach ($this->roles as $role) {
                if (!$role->is_active) {
                    continue;
                }
                foreach (explode(',', (string) $role->permission_codes) as $code) {
                    $code = trim($code);
                    if ($code !== '') {
                        $codes[] = $code;
                    }
                }
            }
            return array_values(array_unique($codes));
        }, 300);
    }

    /**
     * 校验密码
     */
    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, (string) $this->password_hash);
    }
}
