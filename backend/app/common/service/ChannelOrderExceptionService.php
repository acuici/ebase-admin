<?php
declare(strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;
final class ChannelOrderExceptionService
{
    public function list(array $filters, int $page, int $size): array
    {
        $query=Db::name('channel_order_item_exceptions')->alias('e')->leftJoin('orders o','o.id=e.order_id')->field('e.*,o.order_no');
        foreach(['status','reason_code','channel_store_id','order_id','external_sku_id'] as $key) if(!empty($filters[$key])) $query->where('e.'.$key,$filters[$key]);
        if(!empty($filters['keyword'])) $query->whereLike('e.external_product_id|e.external_sku_id|e.merchant_sku_code|o.order_no','%'.addcslashes($filters['keyword'],'%_').'%');
        $total=$query->count(); return ['items'=>$query->order('e.id','desc')->page($page,$size)->select()->toArray(),'pagination'=>['page'=>$page,'page_size'=>$size,'total'=>$total,'pages'=>max(1,(int)ceil($total/$size))]];
    }
    public function read(int $id): array { $row=Db::name('channel_order_item_exceptions')->where('id',$id)->find(); if(!$row) throw BusinessException::notFound('订单商品异常不存在'); return $row; }
    public function resolve(int $id, int $skuId, int $memberId, string $note): array
    {
        return Db::transaction(function () use ($id, $skuId, $memberId, $note): array {
            $exception = Db::name('channel_order_item_exceptions')->where('id', $id)->lock(true)->find();
            if (!$exception) throw BusinessException::notFound('订单商品异常不存在');
            if ($exception['status'] !== 'pending') {
                return array_merge($exception, ['idempotent' => true]);
            }
            $mapping = Db::name('channel_product_skus')->alias('m')->join('channel_products p', 'p.id=m.channel_product_id')->where('p.channel_store_id', $exception['channel_store_id'])->where('p.external_product_id', $exception['external_product_id'])->where('m.external_sku_id', $exception['external_sku_id'])->where('m.product_sku_id', $skuId)->where('m.listing_status', 'published')->find();
            if (!$mapping) throw new BusinessException('CHANNEL_SKU_MAPPING_NOT_FOUND', '提交的内部 SKU 与平台商品或平台 SKU 不匹配', 409);
            $sku = Db::name('product_skus')->where('id', $skuId)->where('status', 'active')->find();
            if (!$sku) throw BusinessException::notFound('内部 SKU 不存在');
            $item = json_decode($exception['raw_item'], true) ?: [];
            $existingItem = Db::name('order_items')->where('order_id', $exception['order_id'])->where('channel_order_item_key', $exception['external_order_item_key'])->find();
            if (!$existingItem) {
                Db::name('order_items')->insert(['order_id' => $exception['order_id'], 'channel_order_item_key' => $exception['external_order_item_key'], 'sku_id' => $skuId, 'sku_code' => $sku['sku_code'], 'product_name' => $item['product_name'] ?? $sku['name'], 'quantity' => (int) $item['quantity'], 'unit_price' => (string) $item['unit_price'], 'subtotal' => bcmul((string) $item['unit_price'], (string) $item['quantity'], 2)]);
            }
            $pending = Db::name('channel_order_item_exceptions')->where('order_id', $exception['order_id'])->where('status', 'pending')->where('id', '<>', $id)->count();
            $now = date('Y-m-d H:i:s');
            Db::name('channel_order_item_exceptions')->where('id', $id)->update(['status' => 'resolved', 'resolved_product_sku_id' => $skuId, 'resolved_by' => $memberId, 'resolved_at' => $now, 'resolution_note' => $note, 'updated_at' => $now]);
            Db::name('order_channel_extensions')->where('order_id', $exception['order_id'])->update(['mapping_status' => $pending ? 'pending' : 'resolved', 'updated_at' => $now]);
            $this->writeOperationLog($memberId, 'resolve', $id, $now);
            return Db::name('channel_order_item_exceptions')->where('id', $id)->find();
        });
    }
    private function writeOperationLog(int $operatorId, string $action, int $exceptionId, string $now): void
    {
        if (Db::name('operation_logs')->count() >= 0) {
            Db::name('operation_logs')->insert(['operator_id' => $operatorId, 'module' => 'channel_order', 'action' => $action, 'resource_type' => 'channel_order_item_exception', 'resource_id' => (string) $exceptionId, 'result' => 'success', 'risk_level' => 'medium', 'detail' => json_encode(['action' => $action], JSON_UNESCAPED_UNICODE), 'created_at' => $now]);
        }
    }

    public function ignore(int $id, int $memberId, string $note): array
    {
        return Db::transaction(function () use ($id, $memberId, $note): array {
            $exception = Db::name('channel_order_item_exceptions')->where('id', $id)->lock(true)->find();
            if (!$exception) throw BusinessException::notFound('订单商品异常不存在');
            if ($exception['status'] !== 'pending') {
                return array_merge($exception, ['idempotent' => true]);
            }
            $now = date('Y-m-d H:i:s');
            Db::name('channel_order_item_exceptions')->where('id', $id)->update(['status' => 'ignored', 'resolved_by' => $memberId, 'resolved_at' => $now, 'resolution_note' => $note, 'updated_at' => $now]);
            $this->writeOperationLog($memberId, 'ignore', $id, $now);
            return Db::name('channel_order_item_exceptions')->where('id', $id)->find();
        });
    }
}
