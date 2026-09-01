<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;use app\common\exception\BusinessException;use app\common\model\Role;use app\validate\RoleValidate;use think\facade\Cache;use think\facade\Db;use think\Request;use think\Response;
class RoleController extends ApiController
{
 public function index():Response{return $this->success(Role::order('id')->select());}
 public function read(int $id):Response{$role=Role::find($id);if(!$role)throw BusinessException::notFound('角色不存在');$data=$role->toArray();$data['permission_codes']=array_values(array_filter(explode(',',(string)$role->permission_codes)));return $this->success($data);}
 public function create(Request $request):Response{$this->validate($request->post(),RoleValidate::class);$data=$request->post();$role=Role::create(['name'=>$data['name'],'description'=>$data['description']??null,'permission_codes'=>implode(',',array_unique($data['permission_codes']??[])),'is_active'=>$data['is_active']??1]);return $this->success($role,'角色创建成功',201);}
 public function update(Request $request,int $id):Response{$role=Role::find($id);if(!$role)throw BusinessException::notFound('角色不存在');$data=$request->post();$this->validate(array_merge($role->toArray(),$data),RoleValidate::class);$update=[];foreach(['name','description','is_active'] as $key)if(array_key_exists($key,$data))$update[$key]=$data[$key];if(array_key_exists('permission_codes',$data))$update['permission_codes']=implode(',',array_unique($data['permission_codes']));$role->save($update);$this->invalidateRoleMembers($id);return $this->success($role,'角色已更新');}
 public function delete(int $id):Response{$role=Role::find($id);if(!$role)throw BusinessException::notFound('角色不存在');if(Db::name('member_roles')->where('role_id',$id)->count()>0)throw new BusinessException('RESOURCE_CONFLICT','角色仍有关联成员，不能删除',409);$role->delete();return $this->success(null,'角色已删除');}
 private function invalidateRoleMembers(int $roleId):void{foreach(Db::name('member_roles')->where('role_id',$roleId)->column('member_id') as $memberId)Cache::store('redis')->delete('perm:member:'.$memberId);}
}
