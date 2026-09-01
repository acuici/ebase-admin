<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController; use app\common\service\ChannelOrderImportService; use app\validate\ChannelOrderImportValidate; use think\Request; use think\Response;
class ChannelOrderController extends ApiController
{
 public function import(Request $request):Response{$this->validate($request->post(),ChannelOrderImportValidate::class);return $this->success((new ChannelOrderImportService())->import($request->post(),(int)$this->requireMember()->id),'渠道订单已导入',201);}
}
