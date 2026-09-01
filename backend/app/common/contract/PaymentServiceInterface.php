<?php
declare (strict_types=1);
namespace app\common\contract;
/** Payment provider boundary: WeChat Pay is the first planned adapter. */
interface PaymentServiceInterface
{
    public function createPayment(string $orderNo, string $amount, string $currency, array $context = []): array;
    public function queryPayment(string $transactionId): array;
    public function closePayment(string $orderNo): void;
    public function refund(string $orderNo, string $refundNo, string $amount): array;
    public function verifyCallback(array $headers, string $body): array;
}
