<?php
declare (strict_types=1);
namespace app\common\exception;
class PaymentProviderException extends BusinessException
{
    public static function unavailable(string $channel): self
    {
        return new self('UPSTREAM_UNAVAILABLE', "支付渠道 {$channel} 暂未配置或不可用", 503);
    }
    public static function invalidCallback(string $channel): self
    {
        return new self('PAYMENT_CALLBACK_INVALID', "支付渠道 {$channel} 回调验签失败", 400);
    }
}
