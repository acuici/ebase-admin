<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BusinessException;
use think\facade\Db;

final class MemberListService
{
    private const ALLOWED=['page','page_size','keyword','status','department','role','data_scope','sort_by','sort_order'];
    private const SORTS=['id','email','name','status','created_at','updated_at'];
    public function list(array $input):array
    {
        $unknown=array_diff(array_keys($input),self::ALLOWED);if($unknown)throw new BusinessException('VALIDATION_ERROR','存在未知查询参数',422,['unknown'=>array_values($unknown)]);
        $sort=(string)($input['sort_by']??'id');$direction=strtolower((string)($input['sort_order']??'desc'));if(!in_array($sort,self::SORTS,true)||!in_array($direction,['asc','desc'],true))throw new BusinessException('VALIDATION_ERROR','排序参数不合法',422);
        $q=Db::name('members')->alias('m')->leftJoin('member_profiles mp','mp.member_id=m.id')->field('m.id,m.email,m.name,m.status,m.is_super,m.created_at,m.updated_at,mp.department');
        if(($v=trim((string)($input['keyword']??'')))!=='')$q->whereLike('m.email|m.name','%'.addcslashes($v,'%_').'%');
        if(($v=$input['status']??'')!=='')$q->where('m.status',(int)$v);
        if(($v=$input['department']??'')!=='')$q->where('mp.department',$v);
        if(($v=$input['role']??'')!=='')$q->whereExists(function($s)use($v){$s->table('member_roles mr')->join('roles r','r.id=mr.role_id')->whereRaw('mr.member_id=m.id')->where('r.name',$v);});
        if(($v=$input['data_scope']??'')!=='')$q->whereExists(function($s)use($v){$s->table('member_data_scopes ds')->whereRaw('ds.member_id=m.id')->where('ds.scope_value',$v);});
        ['page' => $page, 'page_size' => $size] = ListPagination::normalize($input);$total=(int)(clone$q)->count('m.id');return ListPagination::response($q->order('m.'.$sort,$direction)->page($page,$size)->select()->toArray(),$page,$size,$total);
    }
}
