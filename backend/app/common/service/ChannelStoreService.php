<?php
declare(strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;
final class ChannelStoreService
{
    public function list(array $filters, int $page, int $size): array
    {
        $query = Db::name('channel_stores');
        if (!empty($filters['channel_type'])) $query->where('channel_type', $filters['channel_type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['authorization_status'])) $query->where('authorization_status', $filters['authorization_status']);
        if (!empty($filters['keyword'])) $query->whereLike('store_code|external_store_id|name', '%' . addcslashes($filters['keyword'], '%_') . '%');
        $total = $query->count();
        return ['items' => $query->order('id', 'desc')->page($page, $size)->select()->toArray(), 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total, 'pages' => max(1, (int) ceil($total / $size))]];
    }
    public function create(array $data): array
    {
        $now = date('Y-m-d H:i:s'); $data['created_at'] = $now; $data['updated_at'] = $now;
        try { $id = Db::name('channel_stores')->insertGetId($data); } catch (\Throwable $e) { if (str_contains($e->getMessage(), 'Duplicate')) throw new BusinessException('RESOURCE_CONFLICT', '渠道店铺已存在', 409); throw $e; }
        return Db::name('channel_stores')->where('id', $id)->find();
    }
    public function read(int $id): array { $row = Db::name('channel_stores')->where('id', $id)->find(); if (!$row) throw BusinessException::notFound('渠道店铺不存在'); return $row; }
    public function update(int $id, array $data): array { $this->read($id); $data['updated_at'] = date('Y-m-d H:i:s'); Db::name('channel_stores')->where('id', $id)->update($data); return $this->read($id); }
    public function disable(int $id): array { return $this->update($id, ['status' => 'disabled', 'authorization_status' => 'revoked']); }
}
