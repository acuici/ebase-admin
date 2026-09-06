<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BusinessException;
use think\facade\Db;

final class WarehouseService
{
    public function list(array $input): array
    {
        $allowed = ['page', 'page_size', 'keyword', 'status', 'owner_code', 'city', 'sort_by', 'sort_order'];
        $unknown = array_diff(array_keys($input), $allowed);
        if ($unknown) {
            throw new BusinessException('VALIDATION_ERROR', '存在未知查询参数', 422, ['unknown' => array_values($unknown)]);
        }
        $sort = (string) ($input['sort_by'] ?? 'id');
        if (!in_array($sort, ['id', 'warehouse_code', 'name', 'capacity_rate', 'created_at', 'updated_at'], true)) {
            throw new BusinessException('VALIDATION_ERROR', '排序字段不合法', 422);
        }
        $direction = strtolower((string) ($input['sort_order'] ?? 'desc'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new BusinessException('VALIDATION_ERROR', '排序方向不合法', 422);
        }
        $query = Db::name('warehouses')->alias('w')
            ->leftJoin('members m', 'm.member_code = w.owner_code')
            ->leftJoin('suppliers sp', 'sp.id = w.supplier_id')
            ->field('w.*,m.name AS owner_name,m.status AS owner_status,sp.supplier_code,sp.name AS supplier_name')
            ->field('(SELECT COUNT(*) FROM product_skus ps WHERE ps.warehouse_code = w.warehouse_code) AS sku_count')
            ->field('(SELECT COALESCE(SUM(ps.stock_quantity - ps.reserved_quantity), 0) FROM product_skus ps WHERE ps.warehouse_code = w.warehouse_code) AS available_stock');
        if (($keyword = trim((string) ($input['keyword'] ?? ''))) !== '') {
            $query->whereLike('w.warehouse_code|w.name|w.city|w.owner_code|m.name', '%' . addcslashes($keyword, '%_') . '%');
        }
        foreach (['status' => 'w.status', 'owner_code' => 'w.owner_code', 'city' => 'w.city'] as $key => $column) {
            if (($value = (string) ($input[$key] ?? '')) !== '') {
                $query->where($column, $value);
            }
        }
        ['page' => $page, 'page_size' => $size] = ListPagination::normalize($input);
        $total = (int) (clone $query)->count('w.id');
        $items = $query->order('w.' . $sort, $direction)->page($page, $size)->select()->toArray();
        return ListPagination::response($items, $page, $size, $total);
    }

    public function members(): array
    {
        return Db::name('members')->where('status', 1)->field('id,member_code,name,email')->order('name', 'asc')->select()->toArray();
    }

    public function assertActiveOwner(?string $ownerCode): void
    {
        if ($ownerCode === null || $ownerCode === '') {
            return;
        }
        $this->activeMember($ownerCode);
    }

    public function disable(int $warehouseId, int $actorId, string $requestId): void
    {
        $warehouse = Db::name('warehouses')->where('id', $warehouseId)->find();
        if (!$warehouse) {
            throw BusinessException::notFound('仓库不存在');
        }
        $now = date('Y-m-d H:i:s');
        Db::name('warehouses')->where('id', $warehouseId)->update([
            'status' => 'disabled',
            'updated_at' => $now,
        ]);
        $this->log($actorId, $requestId, 'disable', $warehouseId, $now);
    }

    public function assign(int $warehouseId, string $ownerCode, int $actorId, string $requestId): array
    {
        $warehouse = Db::name('warehouses')->where('id', $warehouseId)->find();
        if (!$warehouse) throw BusinessException::notFound('仓库不存在');
        $member = $this->activeMember($ownerCode);
        $now = date('Y-m-d H:i:s');
        Db::transaction(function () use ($warehouse, $member, $actorId, $requestId, $now): void {
            $this->closeActiveHandover((int) $warehouse['id'], $now);
            Db::name('warehouses')->where('id', $warehouse['id'])->update(['owner_code' => $member['member_code'], 'updated_at' => $now]);
            $this->log($actorId, $requestId, 'assign', (int) $warehouse['id'], $now);
        });
        return $this->find((int) $warehouse['id']);
    }

    public function handover(int $warehouseId, string $toOwnerCode, string $handoverType, ?string $reason, int $actorId, string $requestId): array
    {
        if (!in_array($handoverType, ['temporary', 'permanent'], true)) throw new BusinessException('VALIDATION_ERROR', '交接类型不合法', 422);
        $warehouse = Db::name('warehouses')->where('id', $warehouseId)->find();
        if (!$warehouse) throw BusinessException::notFound('仓库不存在');
        $toMember = $this->activeMember($toOwnerCode);
        $fromMember = $warehouse['owner_code'] ? Db::name('members')->where('member_code', $warehouse['owner_code'])->find() : null;
        if ($fromMember && (int) $fromMember['id'] === (int) $toMember['id']) throw new BusinessException('RESOURCE_CONFLICT', '交接对象不能是当前负责人', 409);
        $now = date('Y-m-d H:i:s');
        Db::transaction(function () use ($warehouse, $toMember, $fromMember, $handoverType, $reason, $actorId, $requestId, $now): void {
            $this->closeActiveHandover((int) $warehouse['id'], $now);
            Db::name('warehouse_handover_records')->insert([
                'warehouse_id' => $warehouse['id'], 'from_member_id' => $fromMember['id'] ?? null, 'to_member_id' => $toMember['id'],
                'handover_type' => $handoverType, 'status' => 'active', 'reason' => $reason, 'started_at' => $now,
                'created_by' => $actorId, 'created_at' => $now, 'updated_at' => $now,
            ]);
            Db::name('warehouses')->where('id', $warehouse['id'])->update(['owner_code' => $toMember['member_code'], 'updated_at' => $now]);
            $this->log($actorId, $requestId, 'handover', (int) $warehouse['id'], $now);
        });
        return $this->find((int) $warehouse['id']);
    }

    public function restore(int $warehouseId, int $actorId, string $requestId): array
    {
        $warehouse = Db::name('warehouses')->where('id', $warehouseId)->find();
        if (!$warehouse) throw BusinessException::notFound('仓库不存在');
        $handover = Db::name('warehouse_handover_records')->where('warehouse_id', $warehouseId)->where('status', 'active')->where('handover_type', 'temporary')->order('id', 'desc')->find();
        if (!$handover || !$handover['from_member_id']) throw new BusinessException('RESOURCE_CONFLICT', '没有可恢复的临时交接', 409);
        $member = Db::name('members')->where('id', $handover['from_member_id'])->where('status', 1)->find();
        if (!$member) throw new BusinessException('RESOURCE_CONFLICT', '原负责人当前不可恢复', 409);
        $now = date('Y-m-d H:i:s');
        Db::transaction(function () use ($warehouseId, $handover, $member, $actorId, $requestId, $now): void {
            Db::name('warehouse_handover_records')->where('id', $handover['id'])->update(['status' => 'completed', 'ended_at' => $now, 'updated_at' => $now]);
            Db::name('warehouses')->where('id', $warehouseId)->update(['owner_code' => $member['member_code'], 'updated_at' => $now]);
            $this->log($actorId, $requestId, 'restore_handover', $warehouseId, $now);
        });
        return $this->find($warehouseId);
    }

    public function handovers(int $warehouseId): array
    {
        return Db::name('warehouse_handover_records')->alias('h')
            ->leftJoin('members fm', 'fm.id=h.from_member_id')->leftJoin('members tm', 'tm.id=h.to_member_id')
            ->where('h.warehouse_id', $warehouseId)->field('h.*,fm.name AS from_member_name,tm.name AS to_member_name')->order('h.id', 'desc')->select()->toArray();
    }

    public function memberOffboard(int $memberId, int $actorId, string $requestId): int
    {
        $now = date('Y-m-d H:i:s');
        $memberCode = Db::name('members')->where('id', $memberId)->value('member_code');
        if (!$memberCode) {
            return 0;
        }
        $warehouses = Db::name('warehouses')->where('owner_code', $memberCode)->select()->toArray();
        foreach ($warehouses as $warehouse) {
            Db::name('warehouses')->where('id', $warehouse['id'])->update(['owner_code' => null, 'updated_at' => $now]);
            $this->log($actorId, $requestId, 'offboard_reclaim', (int) $warehouse['id'], $now);
        }
        return count($warehouses);
    }
    public function recoverForMember(int $memberId, int $actorId, string $requestId): int
    {
        $member = Db::name('members')->where('id', $memberId)->where('status', 1)->find();
        if (!$member) {
            return 0;
        }
        $records = Db::name('warehouse_handover_records')
            ->where('from_member_id', $memberId)
            ->where('handover_type', 'temporary')
            ->where('status', 'active')
            ->select()
            ->toArray();
        $count = 0;
        foreach ($records as $record) {
            $warehouse = Db::name('warehouses')->where('id', $record['warehouse_id'])->find();
            if (!$warehouse) {
                continue;
            }
            $this->restore((int) $warehouse['id'], $actorId, $requestId);
            $count++;
        }
        return $count;
    }

    public function find(int $id): array
    {
        $item = Db::name('warehouses')->alias('w')->leftJoin('members m', 'm.member_code=w.owner_code')->leftJoin('suppliers sp', 'sp.id=w.supplier_id')->where('w.id', $id)->field('w.*,m.name AS owner_name,m.status AS owner_status,sp.supplier_code,sp.name AS supplier_name')->field('(SELECT COUNT(*) FROM product_skus ps WHERE ps.warehouse_code = w.warehouse_code) AS sku_count')->field('(SELECT COALESCE(SUM(ps.stock_quantity - ps.reserved_quantity), 0) FROM product_skus ps WHERE ps.warehouse_code = w.warehouse_code) AS available_stock')->find();
        if (!$item) throw BusinessException::notFound('仓库不存在');
        return $item;
    }

    private function activeMember(string $memberCode): array
    {
        $member = Db::name('members')->where('member_code', $memberCode)->where('status', 1)->find();
        if (!$member) throw new BusinessException('VALIDATION_ERROR', '负责人不存在或已停用', 422, ['owner_code' => ['负责人不存在或已停用']]);
        return $member;
    }

    private function closeActiveHandover(int $warehouseId, string $now): void
    {
        Db::name('warehouse_handover_records')->where('warehouse_id', $warehouseId)->where('status', 'active')->update(['status' => 'completed', 'ended_at' => $now, 'updated_at' => $now]);
    }

    private function log(int $actorId, string $requestId, string $action, int $warehouseId, string $now): void
    {
        Db::name('operation_logs')->insert(['operator_id' => $actorId, 'module' => 'warehouses', 'action' => $action, 'resource_type' => 'warehouse', 'resource_id' => (string) $warehouseId, 'result' => 'success', 'risk_level' => 'medium', 'detail' => json_encode(['request_id' => $requestId], JSON_UNESCAPED_UNICODE), 'created_at' => $now]);
    }
}
