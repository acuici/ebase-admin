<?php
declare (strict_types=1);
namespace app\common\service\payment;
use app\common\contract\PaymentChannelInterface;
use app\common\exception\BusinessException;
final class PaymentChannelRegistry
{
    /** @return array<string,PaymentChannelInterface> */
    public function all(): array
    {
        $channels=[new WechatPayChannel(),new AlipayChannel(),new DouyinPayChannel(),new JdPayChannel()];
        return array_column($channels,null,'channel');
    }
    public function get(string $channel): PaymentChannelInterface
    {
        $all=$this->all();
        if (!isset($all[$channel])) throw BusinessException::validationError(['channel'=>['不支持的支付渠道']]);
        return $all[$channel];
    }
    public function availability(): array
    {
        return array_map(fn(PaymentChannelInterface $channel)=>['channel'=>$channel->channel(),'configured'=>$channel->isConfigured()],$this->all());
    }
}
