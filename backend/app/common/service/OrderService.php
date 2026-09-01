<?php
declare (strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;

/** 订单状态机与库存预占服务。 */
class OrderService
{
    public const PENDING_PAYMENT = 'pending_payment';
    public const PAID = 'paid';
    public const PROCESSING = 'processing';
    public const SHIPPED = 'shipped';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public function create(array $items, ?int $memberId): array
    {
        return Db::transaction(function () use ($items, $memberId) {
            $merged = [];
            foreach ($items as $item) {
                $id = (int) $item['sku_id'];
                $merged[$id] = ($merged[$id] ?? 0) + (int) $item['quantity'];
            }
            ksort($merged); // 固定锁顺序，降低并发死锁概率。
            $lineItems = []; $total = '0.00';
            foreach ($merged as $skuId => $qty) {
                $sku = Db::name('product_skus')->where('id', $skuId)->lock(true)->find();
                if (!$sku || $sku['status'] !== 'active') throw BusinessException::notFound('SKU 不存在或已下架');
                $available = (int) $sku['stock_quantity'] - (int) $sku['reserved_quantity'];
                if ($available < $qty) throw BusinessException::insufficientInventory('SKU ' . $sku['sku_code'] . ' 库存不足');
                Db::name('product_skus')->where('id', $skuId)->inc('reserved_quantity', $qty)->update(['updated_at' => date('Y-m-d H:i:s')]);
                $subtotal = bcmul((string) $sku['price'], (string) $qty, 2);
                $total = bcadd($total, $subtotal, 2);
                $lineItems[] = ['sku' => $sku, 'quantity' => $qty, 'subtotal' => $subtotal];
            }
            $now = date('Y-m-d H:i:s');
            $orderNo = 'EO' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3)));
            $orderId = Db::name('orders')->insertGetId([
                'order_no' => $orderNo, 'member_id' => $memberId, 'status' => self::PENDING_PAYMENT,
                'total_amount' => $total, 'currency' => 'CNY', 'expires_at' => date('Y-m-d H:i:s', time() + 1800),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($lineItems as $line) {
                $sku = $line['sku'];
                Db::name('order_items')->insert(['order_id' => $orderId, 'sku_id' => $sku['id'], 'sku_code' => $sku['sku_code'], 'product_name' => $sku['name'], 'quantity' => $line['quantity'], 'unit_price' => $sku['price'], 'subtotal' => $line['subtotal']]);
                $this->ledger((int) $sku['id'], 0, (int) $sku['stock_quantity'], (int) $sku['stock_quantity'], 'reserve', 'order', (string) $orderId, $memberId);
            }
            $this->log($orderId, null, self::PENDING_PAYMENT, $memberId, 'api', '创建订单并预占库存');
            return Db::name('orders')->where('id', $orderId)->find();
        });
    }

    public function transition(int $orderId, string $target, ?int $operatorId, string $source = 'api', ?string $remark = null): array
    {
        return Db::transaction(function () use ($orderId, $target, $operatorId, $source, $remark) {
            $order = Db::name('orders')->where('id', $orderId)->lock(true)->find();
            if (!$order) throw BusinessException::notFound('订单不存在');
            $from = $order['status'];
            $allowed = [self::PENDING_PAYMENT => [self::PAID, self::CANCELLED], self::PAID => [self::PROCESSING, self::CANCELLED], self::PROCESSING => [self::SHIPPED], self::SHIPPED => [self::COMPLETED]];
            if (!in_array($target, $allowed[$from] ?? [], true)) throw new BusinessException('ORDER_STATE_INVALID', "订单状态不能从 {$from} 迁移至 {$target}", 409);
            $items = Db::name('order_items')->where('order_id', $orderId)->select()->toArray();
            foreach ($items as $item) {
                $sku = Db::name('product_skus')->where('id', $item['sku_id'])->lock(true)->find();
                if ($target === self::CANCELLED) {
                    Db::name('product_skus')->where('id', $sku['id'])->dec('reserved_quantity', $item['quantity'])->update(['updated_at' => date('Y-m-d H:i:s')]);
                    $this->ledger((int) $sku['id'], 0, (int) $sku['stock_quantity'], (int) $sku['stock_quantity'], 'release', 'order', (string) $orderId, $operatorId);
                }
                if ($target === self::PAID) {
                    Db::name('product_skus')->where('id', $sku['id'])->dec('reserved_quantity', $item['quantity'])->dec('stock_quantity', $item['quantity'])->update(['updated_at' => date('Y-m-d H:i:s')]);
                    $this->ledger((int) $sku['id'], -(int) $item['quantity'], (int) $sku['stock_quantity'], (int) $sku['stock_quantity'] - (int) $item['quantity'], 'deduct', 'order', (string) $orderId, $operatorId);
                }
            }
            $update = ['status' => $target, 'updated_at' => date('Y-m-d H:i:s')];
            if ($target === self::PAID) $update['paid_at'] = date('Y-m-d H:i:s');
            if ($target === self::CANCELLED) $update['cancelled_at'] = date('Y-m-d H:i:s');
            Db::name('orders')->where('id', $orderId)->update($update);
            $this->log($orderId, $from, $target, $operatorId, $source, $remark);
            return Db::name('orders')->where('id', $orderId)->find();
        });
    }

    private function ledger(int $skuId, int $change, int $before, int $after, string $reason, string $refType, string $refId, ?int $operatorId): void
    {
        Db::name('inventory_ledgers')->insert(['sku_id'=>$skuId,'change_quantity'=>$change,'before_quantity'=>$before,'after_quantity'=>$after,'reason'=>$reason,'reference_type'=>$refType,'reference_id'=>$refId,'operator_id'=>$operatorId,'created_at'=>date('Y-m-d H:i:s')]);
    }
    private function log(int $orderId, ?string $from, string $to, ?int $operatorId, string $source, ?string $remark): void
    {
        Db::name('order_status_logs')->insert(['order_id'=>$orderId,'from_status'=>$from,'to_status'=>$to,'operator_id'=>$operatorId,'source'=>$source,'remark'=>$remark,'created_at'=>date('Y-m-d H:i:s')]);
    }
}
