<?php
declare (strict_types=1);
namespace app\common\service\payment;
use app\common\contract\PaymentChannelInterface;
use app\common\exception\PaymentProviderException;
/**
 * Base for channel adapters. It deliberately never simulates payment success.
 * A concrete SDK adapter is enabled only when required merchant credentials are present.
 */
abstract class AbstractPaymentChannel implements PaymentChannelInterface
{
    abstract public function channel(): string;
    abstract protected function requiredConfigKeys(): array;
    public function isConfigured(): bool
    {
        foreach ($this->requiredConfigKeys() as $key) if ((string) env($key, '') === '') return false;
        return true;
    }
    protected function unavailable(): never { throw PaymentProviderException::unavailable($this->channel()); }
    public function createPayment(string $orderNo, string $amount, string $currency, array $context = []): array { $this->unavailable(); }
    public function queryPayment(string $transactionId): array { $this->unavailable(); }
    public function closePayment(string $orderNo): void { $this->unavailable(); }
    public function refund(string $orderNo, string $refundNo, string $amount): array { $this->unavailable(); }
    public function verifyCallback(array $headers, string $body): array { $this->unavailable(); }
}
