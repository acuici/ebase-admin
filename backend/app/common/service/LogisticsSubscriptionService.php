<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BusinessException;
use app\common\service\logistics\Kuaidi100Service;
use think\facade\Db;

/** Schedules carrier tracking polls; actual execution is delegated to jobs:process. */
final class LogisticsSubscriptionService
{
    public function subscribe(int $packageId, string $provider, string $callbackUrl, ?int $operatorId): array
    {
        if (!Db::name('shipment_packages')->where('id', $packageId)->find()) throw BusinessException::notFound('包裹不存在');
        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) throw BusinessException::validationError(['callback_url' => ['回调地址格式不正确']]);
        $package = Db::name('shipment_packages')->where('id', $packageId)->find();
        $now = date('Y-m-d H:i:s');
        Db::name('logistics_subscriptions')->insert(['package_id'=>$packageId,'provider'=>$provider,'callback_url'=>$callbackUrl,'status'=>'active','created_at'=>$now,'updated_at'=>$now], true);
        (new JobOutboxService())->schedule("logistics:track:{$provider}:{$packageId}",'logistics.track',['package_id'=>$packageId,'provider'=>$provider],new \DateTimeImmutable('+1 minute'));
        return Db::name('logistics_subscriptions')->where('package_id',$packageId)->where('provider',$provider)->find();
    }
}
