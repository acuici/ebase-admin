<?php
declare (strict_types=1);
namespace app\common\service\payment;
final class WechatPayChannel extends AbstractPaymentChannel
{
    public function channel(): string { return 'wechat_pay'; }
    protected function requiredConfigKeys(): array { return ['WECHAT_PAY_MERCHANT_ID','WECHAT_PAY_MERCHANT_SERIAL','WECHAT_PAY_API_V3_KEY','WECHAT_PAY_PRIVATE_KEY_PATH','WECHAT_PAY_PLATFORM_CERT_PATH']; }
}
