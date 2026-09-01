<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class MemberAdminValidate extends Validate
{
 protected $rule=['email'=>'require|email|max:190','name'=>'require|max:80','role_ids'=>'array','status'=>'in:0,1'];
 protected $message=['email.require'=>'邮箱不能为空','email.email'=>'邮箱格式不正确','name.require'=>'成员姓名不能为空','role_ids.array'=>'角色列表格式不正确','status.in'=>'成员状态不合法'];
}
