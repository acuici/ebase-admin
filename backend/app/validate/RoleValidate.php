<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class RoleValidate extends Validate
{
 protected $rule=['name'=>'require|max:80','description'=>'max:255','permission_codes'=>'array','is_active'=>'in:0,1'];
 protected $message=['name.require'=>'角色名称不能为空','permission_codes.array'=>'权限码列表格式不正确','is_active.in'=>'角色状态不合法'];
}
