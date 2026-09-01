<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\Product;
use app\common\model\StorefrontSite;
use app\validate\StorefrontListingValidate;
use think\facade\Db; use think\Request; use think\Response;
class StorefrontListingController extends ApiController
{
 public function index(int $siteId,Request $request): Response { if(!StorefrontSite::find($siteId))throw BusinessException::notFound('站点不存在'); $q=Db::name('storefront_product_listings')->alias('l')->join('products p','p.id=l.product_id')->field('l.*,p.product_no,p.name as product_name'); if($status=$request->get('status'))$q->where('l.status',$status); return $this->success($q->where('l.site_id',$siteId)->order('l.id','desc')->select()); }
 public function upsert(Request $request,int $siteId,int $productId): Response { if(!StorefrontSite::find($siteId))throw BusinessException::notFound('站点不存在'); if(!Product::find($productId))throw BusinessException::notFound('商品不存在'); $data=array_merge($request->post(),['product_id'=>$productId]); $this->validate($data,StorefrontListingValidate::class); $fields=$request->only(['status','title','slug','description','price','inventory_policy','seo_title','seo_description','scheduled_at']); $now=date('Y-m-d H:i:s'); $existing=Db::name('storefront_product_listings')->where('site_id',$siteId)->where('product_id',$productId)->find(); if($fields['status']==='published')$fields['published_at']=$now; if($existing){Db::name('storefront_product_listings')->where('id',$existing['id'])->update(array_merge($fields,['updated_at'=>$now]));$id=$existing['id'];}else{$id=Db::name('storefront_product_listings')->insertGetId(array_merge($fields,['site_id'=>$siteId,'product_id'=>$productId,'created_at'=>$now,'updated_at'=>$now]));} return $this->success(Db::name('storefront_product_listings')->where('id',$id)->find(),'独立站商品配置已保存'); }
}
