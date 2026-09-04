<?php
declare(strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;
final class ChannelSkuMappingService
{
    public function create(int $channelProductId, array $data): array
    {
        return Db::transaction(function () use ($channelProductId, $data): array {
            $product = Db::name('channel_products')->where('id', $channelProductId)->lock(true)->find(); if (!$product) throw BusinessException::notFound('渠道商品不存在');
            $sku = Db::name('product_skus')->where('id', $data['product_sku_id'])->find(); if (!$sku) throw BusinessException::notFound('内部 SKU 不存在'); if ((int) $sku['product_id'] !== (int) $product['product_id']) throw new BusinessException('CHANNEL_SKU_PRODUCT_MISMATCH', '平台商品与内部 SKU 不属于同一商品', 409);
            $data['channel_product_id'] = $channelProductId; $data['spec_snapshot'] = json_encode($data['spec_snapshot'] ?? [], JSON_UNESCAPED_UNICODE); $now = date('Y-m-d H:i:s'); $data['created_at'] = $now; $data['updated_at'] = $now;
            try { $id = Db::name('channel_product_skus')->insertGetId($data); } catch (\Throwable $e) { if (str_contains($e->getMessage(), 'Duplicate')) throw new BusinessException('CHANNEL_SKU_MAPPING_CONFLICT', '平台 SKU 映射已存在', 409); throw $e; } return Db::name('channel_product_skus')->where('id', $id)->find();
        });
    }
    public function update(int $id, array $data): array { $mapping = Db::name('channel_product_skus')->where('id', $id)->find(); if (!$mapping) throw BusinessException::notFound('SKU 映射不存在'); if (isset($data['product_sku_id'])) { $productId = Db::name('channel_products')->where('id', $mapping['channel_product_id'])->value('product_id'); $skuProductId = Db::name('product_skus')->where('id', $data['product_sku_id'])->value('product_id'); if ((int) $productId !== (int) $skuProductId) throw new BusinessException('CHANNEL_SKU_PRODUCT_MISMATCH', '平台商品与内部 SKU 不属于同一商品', 409); } if (isset($data['spec_snapshot'])) $data['spec_snapshot'] = json_encode($data['spec_snapshot'], JSON_UNESCAPED_UNICODE); $data['updated_at'] = date('Y-m-d H:i:s'); Db::name('channel_product_skus')->where('id', $id)->update($data); return Db::name('channel_product_skus')->where('id', $id)->find(); }
    public function archive(int $id): array { return $this->update($id, ['listing_status' => 'archived']); }
}
