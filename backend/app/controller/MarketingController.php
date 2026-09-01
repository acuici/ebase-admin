<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;use app\common\exception\BusinessException;use think\facade\Db;use think\Request;use think\Response;
final class MarketingController extends ApiController
{
 public function coupons(Request $request):Response{return $this->listTable('coupons',$request);}
 public function campaigns(Request $request):Response{return $this->listTable('marketing_campaigns',$request);}
 public function approvals(Request $request):Response{return $this->listTable('approval_requests',$request);}
 public function createCoupon(Request $request):Response{$this->validate($request->post(),['code'=>'require|max:64|alphaDash','name'=>'require|max:120','discount_type'=>'require|in:fixed,percent','discount_value'=>'require|float|gt:0','total_quantity'=>'require|integer|gt:0']);$d=$request->post();$id=Db::name('coupons')->insertGetId(['code'=>$d['code'],'name'=>$d['name'],'discount_type'=>$d['discount_type'],'discount_value'=>$d['discount_value'],'min_amount'=>$d['min_amount']??0,'total_quantity'=>$d['total_quantity'],'status'=>'draft','starts_at'=>$d['starts_at']??null,'ends_at'=>$d['ends_at']??null,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);return $this->success(Db::name('coupons')->where('id',$id)->find(),'优惠券创建成功',201);}
 public function claim(Request $request,int $couponId):Response{$customerId=(int)$request->post('customer_id');return Db::transaction(function()use($couponId,$customerId){$coupon=Db::name('coupons')->where('id',$couponId)->lock(true)->find();if(!$coupon)throw BusinessException::notFound('优惠券不存在');if($coupon['claimed_quantity'] >= $coupon['total_quantity'])throw new BusinessException('RESOURCE_CONFLICT','优惠券已领完',409);$exists=Db::name('coupon_claims')->where('coupon_id',$couponId)->where('customer_id',$customerId)->find();if($exists)throw new BusinessException('RESOURCE_CONFLICT','该消费者已领取',409);$now=date('Y-m-d H:i:s');Db::name('coupons')->where('id',$couponId)->inc('claimed_quantity')->update(['updated_at'=>$now]);$id=Db::name('coupon_claims')->insertGetId(['coupon_id'=>$couponId,'customer_id'=>$customerId,'status'=>'available','claimed_at'=>$now]);return $this->success(Db::name('coupon_claims')->where('id',$id)->find(),'优惠券领取成功',201);});}
 private function listTable(string $table,Request $request):Response{$page=max(1,(int)$request->get('page',1));$size=min(100,max(1,(int)$request->get('page_size',20)));$q=Db::name($table);$total=$q->count();return $this->paginated($q->order('id','desc')->page($page,$size)->select(),$page,$size,$total);}
}
