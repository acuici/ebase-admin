<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;use think\facade\Db;use think\Response;
final class ProductQualityController extends ApiController
{
 public function report(int $productId):Response{$p=Db::name('products')->where('id',$productId)->find();if(!$p)return $this->error('RESOURCE_NOT_FOUND','商品不存在',404);$missing=[];foreach(['name','brand','description']as$f)if(trim((string)$p[$f])==='')$missing[]=$f;$sku=Db::name('product_skus')->where('product_id',$productId)->count()>0;$assets=Db::name('asset_relations')->where('relation_type','product')->where('relation_id',$productId)->count()>0;$seo=Db::name('storefront_product_listings')->where('product_id',$productId)->whereNotNull('seo_title')->whereNotNull('seo_description')->count()>0;$score=($missing?0:30)+($sku?25:0)+($assets?20:0)+($seo?25:0);$now=date('Y-m-d H:i:s');Db::name('product_quality_reports')->insert(['product_id'=>$productId,'score'=>$score,'missing_fields'=>json_encode($missing),'has_sku'=>$sku?1:0,'has_assets'=>$assets?1:0,'has_storefront_seo'=>$seo?1:0,'checked_at'=>$now],true);return $this->success(['product_id'=>$productId,'score'=>$score,'missing_fields'=>$missing,'checks'=>['sku'=>$sku,'assets'=>$assets,'storefront_seo'=>$seo],'checked_at'=>$now]);}
}
