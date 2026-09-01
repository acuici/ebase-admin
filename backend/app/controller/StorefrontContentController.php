<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\StorefrontSite;
use app\validate\StorefrontContentValidate;
use think\facade\Db; use think\Request; use think\Response;
class StorefrontContentController extends ApiController
{
 public function index(int $siteId,Request $request): Response { if(!StorefrontSite::find($siteId))throw BusinessException::notFound('站点不存在'); $type=$request->get('type'); $q=Db::name('storefront_content')->where('site_id',$siteId); if($type)$q->where('content_type',$type); return $this->success($q->order('id','desc')->select()); }
 public function upsert(Request $request,int $siteId): Response { if(!StorefrontSite::find($siteId))throw BusinessException::notFound('站点不存在'); $this->validate($request->post(),StorefrontContentValidate::class); $data=$request->only(['content_type','content_key','title','slug','status','payload']); $data['payload']=json_encode($data['payload'],JSON_UNESCAPED_UNICODE); $now=date('Y-m-d H:i:s'); if($request->post('status')==='published')$data['published_at']=$now; $found=Db::name('storefront_content')->where('site_id',$siteId)->where('content_type',$data['content_type'])->where('content_key',$data['content_key'])->find(); if($found){Db::name('storefront_content')->where('id',$found['id'])->update(array_merge($data,['updated_at'=>$now]));$id=$found['id'];}else{$id=Db::name('storefront_content')->insertGetId(array_merge($data,['site_id'=>$siteId,'created_at'=>$now,'updated_at'=>$now]));} return $this->success(Db::name('storefront_content')->where('id',$id)->find(),'独立站内容已保存'); }
}
