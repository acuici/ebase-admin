<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\exception\PaymentProviderException;
use app\common\service\payment\PaymentChannelRegistry;
use app\common\service\payment\PaymentService;
use think\facade\Db;
use think\Request;
use think\Response;

final class PaymentController extends ApiController
{
    public function channels(): Response
    {
        return $this->success((new PaymentChannelRegistry())->availability());
    }

    public function create(Request $request, int $orderId): Response
    {
        $this->validate($request->post(), ['channel' => 'require|in:wechat_pay,alipay,douyin_pay,jd_pay']);
        $key = trim((string) $request->header('Idempotency-Key', ''));
        $result = (new PaymentService())->create($orderId, $request->post('channel'), $key);
        return $this->success($result, '支付单创建成功', 201);
    }

    /** Provider callback boundary. Raw payload is audited before business processing. */
    public function callback(Request $request, string $channel): Response
    {
        $adapter = (new PaymentChannelRegistry())->get($channel);
        $body = (string) $request->getContent();
        $headers = $request->header();
        $eventId = $headers['x-event-id'] ?? hash('sha256', $body);
        $existing = Db::name('payment_callback_audits')->where('channel', $channel)->where('event_id', $eventId)->find();
        if ($existing && $existing['processed_at']) return $this->success(null, '回调已处理');
        $verified = false;
        try {
            $result = $adapter->verifyCallback($headers, $body);
            $verified = true;
        } catch (PaymentProviderException $exception) {
            throw $exception;
        }
        Db::name('payment_callback_audits')->insert([
            'channel' => $channel, 'event_id' => $eventId, 'payment_no' => $result['payment_no'] ?? null,
            'signature_valid' => $verified ? 1 : 0, 'headers_json' => json_encode($headers),
            'payload_json' => json_encode($result, JSON_UNESCAPED_UNICODE), 'processed_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'),
        ], true);
        return $this->success(null, '回调已接收');
    }

    public function refunds(int $orderId): Response
    {
        return $this->success(Db::name('refunds')->where('order_id', $orderId)->order('id', 'desc')->select());
    }
}
