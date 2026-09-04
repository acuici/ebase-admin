<?php
declare(strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;
final class ChannelOrderImportService
{
    public function import(array $data, ?int $operatorId): array
    {
        $items = $data['items'] ?? [];
        $hasItems = !empty($items);

        if ($hasItems && empty($data['channel_store_id'])) {
            throw new BusinessException('CHANNEL_STORE_NOT_FOUND', '含商品行的渠道订单必须指定店铺', 422);
        }

        return Db::transaction(function () use ($data, $items, $operatorId): array {
            $store = $this->validateStore((string) $data['channel_type'], (int) $data['channel_store_id']);
            $existing = $this->findExistingOrder((string) $data['channel_type'], (int) $store['id'], (string) $data['external_order_no']);
            if ($existing) return $this->idempotentResponse($existing);
            if (($data['status'] ?? null) !== 'paid') throw new BusinessException('CHANNEL_ORDER_ITEM_INVALID', '含商品行的外部订单必须是已支付状态', 422);

            $now = date('Y-m-d H:i:s');
            $orderId = $this->createOrder($data, (int) $store['id'], $now);
            [$resolved, $exceptions] = $this->resolveItems($data['items'], $orderId, (int) $store['id'], (string) $data['channel_type'], (string) $data['external_order_no'], $now);
            $mappingStatus = $exceptions ? 'pending' : 'resolved';
            $this->createExtension($data, $orderId, (int) $store['id'], $mappingStatus, $now);
            $this->createExceptions($exceptions);
            if (!$exceptions) { $this->createOrderItems($orderId, $resolved); $this->processImportedInventory($resolved, $orderId, $operatorId, $now); }
            $this->writeStatusLog($orderId, $operatorId, $mappingStatus, $now);
            return ['order_id' => $orderId, 'created' => true, 'idempotent' => false, 'mapping_status' => $mappingStatus, 'items_created' => $exceptions ? 0 : count($resolved), 'exceptions_created' => count($exceptions), 'inventory_processed' => !$exceptions, 'inventory_processed_at' => !$exceptions ? $now : null];
        });
    }

    private function validateStore(string $channel, int $storeId): array { $store = Db::name('channel_stores')->where('id', $storeId)->where('channel_type', $channel)->lock(true)->find(); if (!$store || $store['status'] !== 'active') throw new BusinessException('CHANNEL_STORE_INACTIVE', '渠道店铺不存在或未启用', 409); return $store; }
    private function findExistingOrder(string $channel, int $storeId, string $externalNo): ?array { return Db::name('order_channel_extensions')->where('channel_type', $channel)->where('channel_store_id', $storeId)->where('external_order_no', $externalNo)->lock(true)->find(); }
    private function idempotentResponse(array $existing): array { return ['order_id' => (int) $existing['order_id'], 'created' => false, 'idempotent' => true, 'mapping_status' => $existing['mapping_status'], 'inventory_processed' => $existing['inventory_processed_at'] !== null, 'inventory_processed_at' => $existing['inventory_processed_at']]; }
    private function createOrder(array $data, int $storeId, string $now): int { return (int) Db::name('orders')->insertGetId(['order_no' => 'EO' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3))), 'member_id' => null, 'channel_type' => $data['channel_type'], 'channel_store_id' => $storeId, 'external_order_no' => $data['external_order_no'], 'status' => 'paid', 'total_amount' => $data['total_amount'], 'currency' => $data['currency'], 'paid_at' => $now, 'created_at' => $now, 'updated_at' => $now]); }
    private function resolveItems(array $items, int $orderId, int $storeId, string $channel, string $externalNo, string $now): array { $resolved=[];$exceptions=[];foreach($items as $index=>$item){$key=$item['external_order_item_id']??hash('sha256',json_encode([$channel,$storeId,$externalNo,$item['external_product_id']??null,$item['external_sku_id']??null,$index]));$quantity=(int)($item['quantity']??0);$price=(string)($item['unit_price']??'');$mapping=Db::name('channel_product_skus')->alias('m')->join('channel_products p','p.id=m.channel_product_id')->where('p.channel_store_id',$storeId)->where('p.external_product_id',$item['external_product_id']??'')->where('m.external_sku_id',$item['external_sku_id']??'')->where('m.listing_status','published')->where('m.sync_status','<>','conflict')->find();$reason=$quantity>0&&preg_match('/^\d{1,10}(\.\d{1,2})?$/',$price)?null:'CHANNEL_ORDER_ITEM_INVALID';if(!$mapping&&!$reason)$reason='CHANNEL_SKU_MAPPING_NOT_FOUND';$sku=$mapping?Db::name('product_skus')->where('id',$mapping['product_sku_id'])->where('status','active')->find():null;if(!$sku&&!$reason)$reason='CHANNEL_SKU_MAPPING_NOT_FOUND';if($reason){$exceptions[]=$this->exceptionRow($orderId,$storeId,$item,$key,$reason,$now);continue;}$resolved[]=['channel_order_item_key'=>$key,'sku_id'=>(int)$sku['id'],'sku_code'=>$sku['sku_code'],'product_name'=>$item['product_name']??$sku['name'],'quantity'=>$quantity,'unit_price'=>$price,'subtotal'=>bcmul($price,(string)$quantity,2)];}return[$resolved,$exceptions]; }
    private function createExtension(array $data,int $orderId,int $storeId,string $mappingStatus,string $now):void{Db::name('order_channel_extensions')->insert(['order_id'=>$orderId,'channel_type'=>$data['channel_type'],'channel_store_id'=>$storeId,'external_order_no'=>$data['external_order_no'],'buyer_external_id'=>$data['buyer_external_id']??null,'raw_payload'=>json_encode($data['raw_payload']??[],JSON_UNESCAPED_UNICODE),'mapping_status'=>$mappingStatus,'inventory_processed_at'=>null,'imported_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);}
    private function createExceptions(array $exceptions):void{foreach($exceptions as $exception)Db::name('channel_order_item_exceptions')->insert($exception);}
    private function createOrderItems(int $orderId,array $lines):void{foreach($lines as $line)Db::name('order_items')->insert(['order_id'=>$orderId,'channel_order_item_key'=>$line['channel_order_item_key'],'sku_id'=>$line['sku_id'],'sku_code'=>$line['sku_code'],'product_name'=>$line['product_name'],'quantity'=>$line['quantity'],'unit_price'=>$line['unit_price'],'subtotal'=>$line['subtotal']]);}
    private function processImportedInventory(array $lines,int $orderId,?int $operatorId,string $now):void{(new OrderService())->confirmInventory($lines,$orderId,$operatorId);Db::name('order_channel_extensions')->where('order_id',$orderId)->update(['inventory_processed_at'=>$now,'updated_at'=>$now]);}
    private function writeStatusLog(int $orderId,?int $operatorId,string $mappingStatus,string $now):void{Db::name('order_status_logs')->insert(['order_id'=>$orderId,'from_status'=>null,'to_status'=>'paid','operator_id'=>$operatorId,'source'=>'channel_import','remark'=>$mappingStatus==='pending'?'渠道订单导入，等待 SKU 映射':'渠道订单导入并完成库存处理','created_at'=>$now]);}
    private function exceptionRow(int $orderId,?int $storeId,array $item,string $key,string $reason,string $now):array{return['order_id'=>$orderId,'channel_store_id'=>$storeId,'external_order_item_id'=>$item['external_order_item_id']??null,'external_order_item_key'=>$key,'external_product_id'=>$item['external_product_id']??null,'external_sku_id'=>$item['external_sku_id']??null,'merchant_sku_code'=>$item['merchant_sku_code']??null,'reason_code'=>$reason,'status'=>'pending','raw_item'=>json_encode($item,JSON_UNESCAPED_UNICODE),'created_at'=>$now,'updated_at'=>$now];}
}
