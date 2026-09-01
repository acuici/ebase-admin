<?php
declare (strict_types=1);
namespace app\common\service\payment;
final class DouyinPayChannel extends AbstractPaymentChannel
{
    public function channel(): string { return 'douyin_pay'; }
    protected function requiredConfigKeys(): array { return ['DOUYIN_PAY_APP_ID','DOUYIN_PAY_MCH_ID','DOUYIN_PAY_SECRET','DOUYIN_PAY_GATEWAY']; }
}
