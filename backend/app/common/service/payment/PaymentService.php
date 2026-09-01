<?php
declare (strict_types=1);
namespace app\common\service\payment;
use app\common\exception\BusinessException;
use think\facade\Db;

/** Payment persistence + idempotency. Channel SDK execution stays inside adapters. */
final class PaymentService
{
    public function create(int $orderId, string $channel, string $idempotencyKey): array
    {
        if ($idempotencyKey === '') throw BusinessException::validationError(['Idempotency-Key'=>['支付请求必须携带 Idempotency-Key']]);
        return Db::transaction(function() use($orderId,$channel,$idempotencyKey) {
            $existing=Db::name('idempotency_keys')->where('scope','payment:create')->where('idempotency_key',$idempotencyKey)->lock(true)->find();
            if ($existing && $existing['expires_at'] > date('Y-m-d H:i:s')) return json_decode($existing['response_body'],true);
            $order=Db::name('orders')->where('id',$orderId)->lock(true)->find();
            if (!$order) throw BusinessException::notFound('订单不存在');
            if ($order['status'] !== 'pending_payment') throw new BusinessException('ORDER_STATE_INVALID','仅待付款订单可发起支付',409);
            $adapter=(new PaymentChannelRegistry())->get($channel);
            if (!$adapter->isConfigured()) throw \app\common\exception\PaymentProviderException::unavailable($channel);
            $paymentNo='EP'.date('YmdHis').strtoupper(bin2hex(random_bytes(3)));
            $payload=$adapter->createPayment($order['order_no'],(string)$order['total_amount'],$order['currency'],['payment_no'=>$paymentNo]);
            $response=['payment_no'=>$paymentNo,'channel'=>$channel,'payment_data'=>$payload];
            Db::name('payments')->insert(['payment_no'=>$paymentNo,'order_id'=>$orderId,'channel'=>$channel,'status'=>'pending','amount'=>$order['total_amount'],'currency'=>$order['currency'],'channel_payload'=>json_encode($payload),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
            Db::name('idempotency_keys')->insert(['scope'=>'payment:create','idempotency_key'=>$idempotencyKey,'response_code'=>'OK','response_body'=>json_encode($response),'expires_at'=>date('Y-m-d H:i:s',time()+86400),'created_at'=>date('Y-m-d H:i:s')]);
            return $response;
        });
    }
}
