<?php
declare (strict_types=1);
namespace app\common\service;
use app\common\exception\BusinessException;
use think\facade\Db;

/** Fulfillment state machine. Each order can have multiple fulfillments/packages. */
class FulfillmentService
{
    public function create(int $orderId, array $data, ?int $operatorId): array
    {
        return Db::transaction(function () use ($orderId, $data, $operatorId) {
            $order = Db::name('orders')->where('id', $orderId)->lock(true)->find();
            if (!$order) throw BusinessException::notFound('订单不存在');
            if (!in_array($order['status'], ['paid', 'processing'], true)) throw new BusinessException('ORDER_STATE_INVALID', '仅已付款或处理中订单可创建履约单', 409);
            $now = date('Y-m-d H:i:s');
            $id = Db::name('fulfillments')->insertGetId([
                'fulfillment_no' => 'EF' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3))), 'order_id' => $orderId,
                'warehouse_code' => $data['warehouse_code'] ?? null, 'status' => 'processing',
                'recipient_snapshot' => json_encode($data['recipient_snapshot'], JSON_UNESCAPED_UNICODE),
                'shipping_method' => $data['shipping_method'] ?? null, 'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($order['status'] === 'paid') $this->setOrderStatus($orderId, 'paid', 'processing', $operatorId, '创建履约单');
            return Db::name('fulfillments')->where('id', $id)->find();
        });
    }

    /** Add a package; allows split shipments under one fulfillment. */
    public function ship(int $fulfillmentId, array $data, ?int $operatorId): array
    {
        return Db::transaction(function () use ($fulfillmentId, $data, $operatorId) {
            $f = Db::name('fulfillments')->where('id', $fulfillmentId)->lock(true)->find();
            if (!$f) throw BusinessException::notFound('履约单不存在');
            if (!in_array($f['status'], ['processing', 'shipped'], true)) throw new BusinessException('ORDER_STATE_INVALID', '当前履约单不能发货', 409);
            $now = date('Y-m-d H:i:s');
            $packageId = Db::name('shipment_packages')->insertGetId([
                'fulfillment_id' => $fulfillmentId, 'package_no' => 'EP' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3))),
                'carrier_code' => $data['carrier_code'], 'tracking_no' => $data['tracking_no'], 'status' => 'shipped',
                'carrier_payload' => isset($data['carrier_payload']) ? json_encode($data['carrier_payload'], JSON_UNESCAPED_UNICODE) : null,
                'shipped_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($f['status'] === 'processing') {
                Db::name('fulfillments')->where('id', $fulfillmentId)->update(['status' => 'shipped', 'shipped_at' => $now, 'updated_at' => $now]);
                $order = Db::name('orders')->where('id', $f['order_id'])->lock(true)->find();
                if ($order['status'] === 'processing') $this->setOrderStatus((int)$f['order_id'], 'processing', 'shipped', $operatorId, '履约包裹发货');
            }
            return Db::name('shipment_packages')->where('id', $packageId)->find();
        });
    }

    /** Insert a carrier tracking event exactly once; delivered closes its package and fulfillment when complete. */
    public function addTrackingEvent(int $packageId, array $data): array
    {
        return Db::transaction(function () use ($packageId, $data) {
            $package = Db::name('shipment_packages')->where('id', $packageId)->lock(true)->find();
            if (!$package) throw BusinessException::notFound('包裹不存在');
            $externalId = $data['external_event_id'] ?? null;
            if ($externalId && ($old = Db::name('shipment_tracking_events')->where('package_id', $packageId)->where('external_event_id', $externalId)->find())) return $old;
            $now = date('Y-m-d H:i:s');
            $id = Db::name('shipment_tracking_events')->insertGetId([
                'package_id' => $packageId, 'event_code' => $data['event_code'] ?? null, 'external_event_id' => $externalId,
                'event_status' => $data['event_status'], 'description' => $data['description'], 'location' => $data['location'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? $now,
                'raw_payload' => isset($data['raw_payload']) ? json_encode($data['raw_payload'], JSON_UNESCAPED_UNICODE) : null, 'created_at' => $now,
            ]);
            if (in_array($data['event_status'], ['delivered', 'signed'], true)) {
                Db::name('shipment_packages')->where('id', $packageId)->update(['status' => 'delivered', 'delivered_at' => $now, 'updated_at' => $now]);
                $this->refreshFulfillmentDelivery((int)$package['fulfillment_id']);
            }
            return Db::name('shipment_tracking_events')->where('id', $id)->find();
        });
    }

    private function refreshFulfillmentDelivery(int $fulfillmentId): void
    {
        $f = Db::name('fulfillments')->where('id', $fulfillmentId)->lock(true)->find();
        $open = Db::name('shipment_packages')->where('fulfillment_id', $fulfillmentId)->where('status', '<>', 'delivered')->count();
        if ($open > 0) return;
        $now = date('Y-m-d H:i:s');
        Db::name('fulfillments')->where('id', $fulfillmentId)->update(['status' => 'delivered', 'delivered_at' => $now, 'updated_at' => $now]);
        $openFulfillments = Db::name('fulfillments')->where('order_id', $f['order_id'])->where('status', '<>', 'delivered')->where('status', '<>', 'cancelled')->count();
        if ($openFulfillments === 0) {
            $order = Db::name('orders')->where('id', $f['order_id'])->lock(true)->find();
            if ($order['status'] === 'shipped') $this->setOrderStatus((int)$f['order_id'], 'shipped', 'completed', null, '全部履约包裹已签收');
        }
    }

    private function setOrderStatus(int $orderId, string $from, string $to, ?int $operatorId, string $remark): void
    {
        $now = date('Y-m-d H:i:s');
        Db::name('orders')->where('id', $orderId)->where('status', $from)->update(['status' => $to, 'updated_at' => $now]);
        Db::name('order_status_logs')->insert(['order_id' => $orderId, 'from_status' => $from, 'to_status' => $to, 'operator_id' => $operatorId, 'source' => 'fulfillment', 'remark' => $remark, 'created_at' => $now]);
    }
}
