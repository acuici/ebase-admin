<?php
declare (strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;
use think\facade\Cache;

/**
 * 库存服务。
 * MySQL 是库存事实来源；Redis 仅作为短期互斥锁。
 */
class InventoryService
{
    private const LOCK_TTL_SECONDS = 10;

    public function adjust(int $skuId, int $delta, string $reason, ?string $referenceType = null, ?string $referenceId = null, ?int $operatorId = null): array
    {
        if ($delta === 0) throw BusinessException::validationError(['quantity' => ['库存变动数量不能为 0']]);
        return Db::transaction(function () use ($skuId, $delta, $reason, $referenceType, $referenceId, $operatorId) {
            $sku = Db::name('product_skus')->where('id', $skuId)->lock(true)->find();
            if (!$sku) throw BusinessException::notFound('SKU 不存在');
            $before = (int) $sku['stock_quantity'];
            $after = $before + $delta;
            if ($after < 0) throw BusinessException::insufficientInventory();
            Db::name('product_skus')->where('id', $skuId)->update(['stock_quantity' => $after, 'updated_at' => date('Y-m-d H:i:s')]);
            Db::name('inventory_ledgers')->insert([
                'sku_id' => $skuId, 'change_quantity' => $delta, 'before_quantity' => $before, 'after_quantity' => $after,
                'reason' => $reason, 'reference_type' => $referenceType, 'reference_id' => $referenceId,
                'operator_id' => $operatorId, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            return ['before_quantity' => $before, 'after_quantity' => $after, 'change_quantity' => $delta];
        });
    }

    public function acquireLock(int $skuId): string
    {
        $token = bin2hex(random_bytes(16));
        $key = 'inventory:lock:' . $skuId;
        $redis = Cache::store('redis')->handler();
        if (!$redis->set($key, $token, ['nx', 'ex' => self::LOCK_TTL_SECONDS])) {
            throw new BusinessException('RESOURCE_CONFLICT', '库存正在处理中，请稍后重试', 409);
        }
        return $token;
    }

    public function releaseLock(int $skuId, string $token): void
    {
        $redis = Cache::store('redis')->handler();
        $key = 'inventory:lock:' . $skuId;
        // Lua 校验持有者后释放，不会删除别人的锁。
        $redis->eval("if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end", [$key, $token], 1);
    }
}
