<?php
declare(strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;
final class ChannelProductService
{
    public function list(array $filters, int $page, int $size): array
    {
        $query = Db::name('channel_products')->alias('cp')->leftJoin('channel_stores cs', 'cs.id = cp.channel_store_id')->leftJoin('products p', 'p.id = cp.product_id')->field('cp.*,cs.name AS store_name,cs.channel_type,p.product_no,p.name AS internal_product_name');
        foreach (['channel_store_id','product_id'] as $key) if (!empty($filters[$key])) $query->where('cp.' . $key, $filters[$key]);
        foreach (['listing_status','sync_status'] as $key) if (!empty($filters[$key])) $query->where('cp.' . $key, $filters[$key]);
        if (!empty($filters['channel_type'])) $query->where('cs.channel_type', $filters['channel_type']);
        if (!empty($filters['keyword'])) $query->whereLike('cp.external_product_id|cp.merchant_product_code|cp.title|p.product_no|p.name', '%' . addcslashes($filters['keyword'], '%_') . '%');
        $total = $query->count(); return ['items' => $query->order('cp.id', 'desc')->page($page, $size)->select()->toArray(), 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total, 'pages' => max(1, (int) ceil($total / $size))]];
    }
    public function create(array $data): array
    {
        $store = Db::name('channel_stores')->where('id', $data['channel_store_id'])->find(); if (!$store) throw BusinessException::notFound('渠道店铺不存在'); if ($store['status'] !== 'active') throw new BusinessException('CHANNEL_STORE_INACTIVE', '渠道店铺未启用', 409);
        if ((int) Db::name('products')->where('id', $data['product_id'])->count() === 0) throw BusinessException::notFound('内部商品不存在');
        $now = date('Y-m-d H:i:s'); $data['created_at'] = $now; $data['updated_at'] = $now; $data['platform_payload'] = json_encode($data['platform_payload'] ?? [], JSON_UNESCAPED_UNICODE);
        try { $id = Db::name('channel_products')->insertGetId($data); } catch (\Throwable $e) { if (str_contains($e->getMessage(), 'Duplicate')) throw new BusinessException('CHANNEL_PRODUCT_CONFLICT', '该店铺商品映射已存在', 409); throw $e; }
        return $this->read($id);
    }
    public function read(int $id): array { $row = Db::name('channel_products')->alias('cp')->leftJoin('channel_stores cs', 'cs.id=cp.channel_store_id')->leftJoin('products p', 'p.id=cp.product_id')->field('cp.*,cs.name AS store_name,cs.channel_type,p.product_no,p.name AS internal_product_name')->where('cp.id', $id)->find(); if (!$row) throw BusinessException::notFound('渠道商品不存在'); $row['skus'] = Db::name('channel_product_skus')->where('channel_product_id', $id)->select()->toArray(); return $row; }
    public function update(int $id, array $data): array { $this->read($id); $data['updated_at'] = date('Y-m-d H:i:s'); if (isset($data['platform_payload'])) $data['platform_payload'] = json_encode($data['platform_payload'], JSON_UNESCAPED_UNICODE); Db::name('channel_products')->where('id', $id)->update($data); return $this->read($id); }
    public function archive(int $id): array { return $this->update($id, ['listing_status' => 'archived']); }
}
