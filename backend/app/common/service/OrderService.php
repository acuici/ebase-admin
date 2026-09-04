<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BusinessException;
use think\facade\Db;

/**
 * Order state machine.
 *
 * MySQL row locks protect stock reservations. Payment confirmation converts a
 * reservation to stock deduction; cancellation releases reservations exactly once.
 */
class OrderService
{
    public const PENDING_PAYMENT = 'pending_payment';
    public const PAID = 'paid';
    public const PROCESSING = 'processing';
    public const SHIPPED = 'shipped';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    private const PAYMENT_TIMEOUT_SECONDS = 1800;

    public function create(array $items, ?int $memberId): array
    {
        return Db::transaction(function () use ($items, $memberId): array {
            $mergedItems = $this->mergeItemQuantities($items);
            ksort($mergedItems); // Stable lock order avoids concurrent deadlocks.

            $lineItems = [];
            $total = '0.00';

            foreach ($mergedItems as $skuId => $quantity) {
                $sku = Db::name('product_skus')->where('id', $skuId)->lock(true)->find();
                if (!$sku || $sku['status'] !== 'active') {
                    throw BusinessException::notFound('SKU 不存在或已下架');
                }

                $available = (int) $sku['stock_quantity'] - (int) $sku['reserved_quantity'];
                if ($available < $quantity) {
                    throw BusinessException::insufficientInventory("SKU {$sku['sku_code']} 库存不足");
                }

                Db::name('product_skus')
                    ->where('id', $skuId)
                    ->inc('reserved_quantity', $quantity)
                    ->update(['updated_at' => date('Y-m-d H:i:s')]);

                $subtotal = bcmul((string) $sku['price'], (string) $quantity, 2);
                $total = bcadd($total, $subtotal, 2);
                $lineItems[] = compact('sku', 'quantity', 'subtotal');
            }

            $now = date('Y-m-d H:i:s');
            $expiresAt = new \DateTimeImmutable('+' . self::PAYMENT_TIMEOUT_SECONDS . ' seconds');
            $orderId = Db::name('orders')->insertGetId([
                'order_no' => 'EO' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3))),
                'member_id' => $memberId,
                'status' => self::PENDING_PAYMENT,
                'total_amount' => $total,
                'currency' => 'CNY',
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($lineItems as $line) {
                $sku = $line['sku'];
                Db::name('order_items')->insert([
                    'order_id' => $orderId,
                    'sku_id' => $sku['id'],
                    'sku_code' => $sku['sku_code'],
                    'product_name' => $sku['name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $sku['price'],
                    'subtotal' => $line['subtotal'],
                ]);
                $this->writeLedger(
                    (int) $sku['id'],
                    0,
                    (int) $sku['stock_quantity'],
                    (int) $sku['stock_quantity'],
                    'reserve',
                    (string) $orderId,
                    $memberId,
                );
            }

            $this->writeStatusLog($orderId, null, self::PENDING_PAYMENT, $memberId, 'api', '创建订单并预占库存');

            // Same transaction as reservation: no lost timeout cancellation task.
            (new JobOutboxService())->schedule(
                "order:cancel_timeout:{$orderId}",
                'order.cancel_timeout',
                ['order_id' => $orderId],
                $expiresAt,
            );

            return Db::name('orders')->where('id', $orderId)->find();
        });
    }

    public function confirmInventory(array $lines, int $orderId, ?int $operatorId): void
    {
        Db::transaction(function () use ($lines, $orderId, $operatorId): void {
            foreach ($lines as $line) {
                $sku = Db::name('product_skus')->where('id', $line['sku_id'])->lock(true)->find();
                if (!$sku) throw BusinessException::notFound('订单 SKU 不存在');
                $available = (int) $sku['stock_quantity'] - (int) $sku['reserved_quantity'];
                if ($available < (int) $line['quantity']) throw BusinessException::insufficientInventory("SKU {$sku['sku_code']} 库存不足");
                $before = (int) $sku['stock_quantity'];
                $after = $before - (int) $line['quantity'];
                Db::name('product_skus')->where('id', $line['sku_id'])->update(['stock_quantity' => $after, 'updated_at' => date('Y-m-d H:i:s')]);
                $this->writeLedger((int) $line['sku_id'], -(int) $line['quantity'], $before, $after, 'deduct', (string) $orderId, $operatorId);
            }
        });
    }

    public function transition(
        int $orderId,
        string $target,
        ?int $operatorId,
        string $source = 'api',
        ?string $remark = null,
    ): array {
        return Db::transaction(function () use ($orderId, $target, $operatorId, $source, $remark): array {
            $order = Db::name('orders')->where('id', $orderId)->lock(true)->find();
            if (!$order) {
                throw BusinessException::notFound('订单不存在');
            }

            $from = $order['status'];
            $this->assertTransitionAllowed($from, $target);

            $items = Db::name('order_items')->where('order_id', $orderId)->select()->toArray();
            foreach ($items as $item) {
                $this->applyInventoryTransition($item, $target, $orderId, $operatorId);
            }

            $now = date('Y-m-d H:i:s');
            $update = ['status' => $target, 'updated_at' => $now];
            if ($target === self::PAID) {
                $update['paid_at'] = $now;
            }
            if ($target === self::CANCELLED) {
                $update['cancelled_at'] = $now;
            }

            Db::name('orders')->where('id', $orderId)->update($update);
            $this->writeStatusLog($orderId, $from, $target, $operatorId, $source, $remark);

            return Db::name('orders')->where('id', $orderId)->find();
        });
    }

    /** Runs safely more than once: only pending-payment orders are cancelled. */
    public function cancelExpired(int $orderId): void
    {
        $order = Db::name('orders')->where('id', $orderId)->find();
        if (!$order || $order['status'] !== self::PENDING_PAYMENT) {
            return;
        }

        if (strtotime($order['expires_at']) > time()) {
            return;
        }

        $this->transition($orderId, self::CANCELLED, null, 'job', '订单超时自动取消');
    }

    private function mergeItemQuantities(array $items): array
    {
        $merged = [];
        foreach ($items as $item) {
            $skuId = (int) $item['sku_id'];
            $merged[$skuId] = ($merged[$skuId] ?? 0) + (int) $item['quantity'];
        }
        return $merged;
    }

    private function assertTransitionAllowed(string $from, string $target): void
    {
        $allowed = [
            self::PENDING_PAYMENT => [self::PAID, self::CANCELLED],
            self::PAID => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED],
            self::SHIPPED => [self::COMPLETED],
        ];

        if (!in_array($target, $allowed[$from] ?? [], true)) {
            throw new BusinessException('ORDER_STATE_INVALID', "订单状态不能从 {$from} 迁移至 {$target}", 409);
        }
    }

    private function applyInventoryTransition(array $item, string $target, int $orderId, ?int $operatorId): void
    {
        $sku = Db::name('product_skus')->where('id', $item['sku_id'])->lock(true)->find();
        if (!$sku) {
            throw BusinessException::notFound('订单 SKU 不存在');
        }

        $quantity = (int) $item['quantity'];
        $stock = (int) $sku['stock_quantity'];

        if ($target === self::CANCELLED) {
            Db::name('product_skus')
                ->where('id', $sku['id'])
                ->dec('reserved_quantity', $quantity)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);
            $this->writeLedger((int) $sku['id'], 0, $stock, $stock, 'release', (string) $orderId, $operatorId);
        }

        if ($target === self::PAID) {
            Db::name('product_skus')
                ->where('id', $sku['id'])
                ->dec('reserved_quantity', $quantity)
                ->dec('stock_quantity', $quantity)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);
            $this->writeLedger((int) $sku['id'], -$quantity, $stock, $stock - $quantity, 'deduct', (string) $orderId, $operatorId);
        }
    }

    private function writeLedger(int $skuId, int $change, int $before, int $after, string $reason, string $orderId, ?int $operatorId): void
    {
        Db::name('inventory_ledgers')->insert([
            'sku_id' => $skuId,
            'change_quantity' => $change,
            'before_quantity' => $before,
            'after_quantity' => $after,
            'reason' => $reason,
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'operator_id' => $operatorId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function writeStatusLog(int $orderId, ?string $from, string $to, ?int $operatorId, string $source, ?string $remark): void
    {
        Db::name('order_status_logs')->insert([
            'order_id' => $orderId,
            'from_status' => $from,
            'to_status' => $to,
            'operator_id' => $operatorId,
            'source' => $source,
            'remark' => $remark,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
