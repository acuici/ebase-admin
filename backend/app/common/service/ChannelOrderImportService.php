<?php
declare (strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;
/** Imports platform orders without leaking platform-specific payload into orders. */
class ChannelOrderImportService
{
 public function import(array $data, ?int $operatorId): array
 {
  return Db::transaction(function()use($data,$operatorId){
   $storeId=$data['channel_store_id']??null; $channel=$data['channel_type']; $external=$data['external_order_no'];
   $existing=Db::name('order_channel_extensions')->where('channel_type',$channel)->where('channel_store_id',$storeId)->where('external_order_no',$external)->lock(true)->find();
   if($existing)return Db::name('orders')->where('id',$existing['order_id'])->find();
   $now=date('Y-m-d H:i:s'); $orderNo='EO'.date('YmdHis').strtoupper(bin2hex(random_bytes(3)));
   $orderId=Db::name('orders')->insertGetId(['order_no'=>$orderNo,'member_id'=>null,'channel_type'=>$channel,'channel_store_id'=>$storeId,'external_order_no'=>$external,'status'=>$data['status']??'pending_payment','total_amount'=>$data['total_amount'],'currency'=>$data['currency'],'created_at'=>$now,'updated_at'=>$now]);
   Db::name('order_channel_extensions')->insert(['order_id'=>$orderId,'channel_type'=>$channel,'channel_store_id'=>$storeId,'external_order_no'=>$external,'buyer_external_id'=>$data['buyer_external_id']??null,'raw_payload'=>json_encode($data['raw_payload'],JSON_UNESCAPED_UNICODE),'imported_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
   Db::name('order_status_logs')->insert(['order_id'=>$orderId,'from_status'=>null,'to_status'=>$data['status']??'pending_payment','operator_id'=>$operatorId,'source'=>'channel_import','remark'=>'渠道订单导入','created_at'=>$now]);
   return Db::name('orders')->where('id',$orderId)->find();
  });
 }
}
