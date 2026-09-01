<?php
declare(strict_types=1);

namespace app\common\service\payment;

use Yansongda\Pay\Pay;

/** Real WeChat Pay adapter powered by yansongda/pay. */
final class WechatPayChannel extends AbstractPaymentChannel
{
    public function channel(): string { return 'wechat_pay'; }

    protected function requiredConfigKeys(): array
    {
        return ['WECHAT_PAY_MERCHANT_ID', 'WECHAT_PAY_MERCHANT_SERIAL', 'WECHAT_PAY_API_V3_KEY', 'WECHAT_PAY_PRIVATE_KEY_PATH', 'WECHAT_PAY_PLATFORM_CERT_PATH'];
    }

    public function createPayment(string $orderNo, string $amount, string $currency, array $context = []): array
    {
        $this->assertConfigured(); Pay::config($this->config());
        $result = Pay::wechat()->native(['out_trade_no' => $orderNo, 'description' => (string)($context['subject'] ?? 'EBASE 订单'), 'amount' => ['total' => (int) bcmul($amount, '100', 0), 'currency' => $currency]]);
        return $result->toArray();
    }

    public function queryPayment(string $transactionId): array
    {
        $this->assertConfigured(); Pay::config($this->config()); return Pay::wechat()->find(['transaction_id' => $transactionId])->toArray();
    }

    public function closePayment(string $orderNo): void
    {
        $this->assertConfigured(); Pay::config($this->config()); Pay::wechat()->close(['out_trade_no' => $orderNo]);
    }

    public function refund(string $orderNo, string $refundNo, string $amount): array
    {
        $this->assertConfigured(); Pay::config($this->config()); return Pay::wechat()->refund(['out_trade_no' => $orderNo, 'out_refund_no' => $refundNo, 'amount' => ['refund' => (int)bcmul($amount,'100',0), 'total' => (int)bcmul($amount,'100',0), 'currency' => 'CNY']])->toArray();
    }

    public function verifyCallback(array $headers, string $body): array
    {
        $this->assertConfigured(); Pay::config($this->config()); return Pay::wechat()->callback($body, $headers)->toArray();
    }

    private function assertConfigured(): void { if (!$this->isConfigured()) $this->unavailable(); }

    private function config(): array
    {
        return ['wechat' => ['default' => [
            'mch_id' => env('WECHAT_PAY_MERCHANT_ID'),
            'mch_secret_key' => env('WECHAT_PAY_API_V3_KEY'),
            'mch_secret_cert' => env('WECHAT_PAY_PRIVATE_KEY_PATH'),
            'mch_public_cert_path' => env('WECHAT_PAY_PLATFORM_CERT_PATH'),
            'serial_no' => env('WECHAT_PAY_MERCHANT_SERIAL'),
            'notify_url' => env('WECHAT_PAY_NOTIFY_URL', ''),
        ]]];
    }
}
