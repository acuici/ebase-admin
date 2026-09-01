<?php
declare(strict_types=1);

namespace app\common\service\payment;

use Yansongda\Pay\Pay;

/** Real Alipay adapter powered by yansongda/pay. */
final class AlipayChannel extends AbstractPaymentChannel
{
    public function channel(): string { return 'alipay'; }

    protected function requiredConfigKeys(): array
    {
        return ['ALIPAY_APP_ID', 'ALIPAY_APP_PRIVATE_KEY_PATH', 'ALIPAY_PUBLIC_KEY_PATH', 'ALIPAY_GATEWAY'];
    }

    public function createPayment(string $orderNo, string $amount, string $currency, array $context = []): array
    {
        $this->assertConfigured();
        Pay::config($this->config());
        $result = Pay::alipay()->web([
            'out_trade_no' => $orderNo,
            'total_amount' => $amount,
            'subject' => (string) ($context['subject'] ?? 'EBASE 订单'),
        ]);
        return ['channel' => $this->channel(), 'payload' => (string) $result];
    }

    public function queryPayment(string $transactionId): array
    {
        $this->assertConfigured(); Pay::config($this->config());
        return Pay::alipay()->query(['trade_no' => $transactionId])->toArray();
    }

    public function closePayment(string $orderNo): void
    {
        $this->assertConfigured(); Pay::config($this->config()); Pay::alipay()->close(['out_trade_no' => $orderNo]);
    }

    public function refund(string $orderNo, string $refundNo, string $amount): array
    {
        $this->assertConfigured(); Pay::config($this->config());
        return Pay::alipay()->refund(['out_trade_no' => $orderNo, 'refund_amount' => $amount, 'out_request_no' => $refundNo])->toArray();
    }

    public function verifyCallback(array $headers, string $body): array
    {
        $this->assertConfigured(); Pay::config($this->config());
        return Pay::alipay()->callback(json_decode($body, true) ?: $_POST)->toArray();
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) $this->unavailable();
    }

    private function config(): array
    {
        return ['alipay' => ['default' => [
            'app_id' => env('ALIPAY_APP_ID'),
            'app_secret_cert' => env('ALIPAY_APP_PRIVATE_KEY_PATH'),
            'app_public_cert_path' => env('ALIPAY_APP_PUBLIC_CERT_PATH', ''),
            'alipay_public_cert_path' => env('ALIPAY_PUBLIC_KEY_PATH'),
            'alipay_root_cert_path' => env('ALIPAY_ROOT_CERT_PATH', ''),
            'gateway_url' => env('ALIPAY_GATEWAY'),
            'notify_url' => env('ALIPAY_NOTIFY_URL', ''),
        ]]];
    }
}
