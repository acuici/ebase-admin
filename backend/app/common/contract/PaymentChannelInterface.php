<?php
declare (strict_types=1);
namespace app\common\contract;
/** Each payment adapter must fail closed on config or signature errors. */
interface PaymentChannelInterface extends PaymentServiceInterface
{
    public function channel(): string;
    public function isConfigured(): bool;
}
