<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\service\LogisticsSubscriptionService;
use think\Request;
use think\Response;
final class LogisticsSubscriptionController extends ApiController
{
    public function subscribe(Request $request, int $packageId): Response
    {
        $this->validate($request->post(), ['provider'=>'require|in:kuaidi100','callback_url'=>'require|url'], ['provider.require'=>'物流服务商不能为空','callback_url.require'=>'回调地址不能为空','callback_url.url'=>'回调地址格式不正确']);
        $result=(new LogisticsSubscriptionService())->subscribe($packageId,(string)$request->post('provider'),(string)$request->post('callback_url'),(int)$this->requireMember()->id);
        return $this->success($result,'物流轨迹订阅已创建',201);
    }
}
