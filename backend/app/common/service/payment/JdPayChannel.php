<?php
declare (strict_types=1);
namespace app\common\service\payment;
final class JdPayChannel extends AbstractPaymentChannel
{
    public function channel(): string { return 'jd_pay'; }
    protected function requiredConfigKeys(): array { return ['JD_PAY_MERCHANT_NO','JD_PAY_DES_KEY','JD_PAY_RSA_PRIVATE_KEY_PATH','JD_PAY_RSA_PUBLIC_KEY_PATH','JD_PAY_GATEWAY']; }
}
