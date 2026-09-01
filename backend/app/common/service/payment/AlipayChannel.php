<?php
declare (strict_types=1);
namespace app\common\service\payment;
final class AlipayChannel extends AbstractPaymentChannel
{
    public function channel(): string { return 'alipay'; }
    protected function requiredConfigKeys(): array { return ['ALIPAY_APP_ID','ALIPAY_APP_PRIVATE_KEY_PATH','ALIPAY_PUBLIC_KEY_PATH','ALIPAY_GATEWAY']; }
}
