<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 角色模型
 *
 * 角色承载权限码列表（逗号分隔）。成员关联多个角色，聚合所有权限码。
 */
class Role extends Model
{
    protected $name = 'roles';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 权限码使用 domain.resource.action 命名
     * 例如：order.order.export, catalog.product.update, admin.member.invite
     *
     * 存储在 permission_codes 字段（TEXT），逗号分隔。
     */
}
