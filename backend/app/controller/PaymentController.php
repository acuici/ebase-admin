<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\service\payment\PaymentChannelRegistry;
use app\common\service\payment\PaymentService;
use think\Request;
use think\Response;
class PaymentController extends ApiController
{
    public function channels(): Response { return $this->success((new PaymentChannelRegistry())->availability()); }
    public function create(Request $request,int $orderId): Response
    {
        $this->validate($request->post(),['channel'=>'require|in:wechat_pay,alipay,douyin_pay,jd_pay'],['channel.require'=>'请选择支付渠道','channel.in'=>'不支持的支付渠道']);
        $data=$request->post();
        $result=(new PaymentService())->create($orderId,$data['channel'],(string)$request->header('Idempotency-Key',''));
        return $this->success($result,'支付单创建成功',201);
    }
}
