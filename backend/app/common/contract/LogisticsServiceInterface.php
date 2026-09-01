<?php
declare (strict_types=1);
namespace app\common\contract;
interface LogisticsServiceInterface
{
    public function createShipment(string $orderNo, array $recipient, array $items): array;
    public function cancelShipment(string $shipmentNo): void;
    public function track(string $trackingNo, ?string $carrier = null): array;
    public function subscribeTracking(string $trackingNo, string $callbackUrl): void;
}
