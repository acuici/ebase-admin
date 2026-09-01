<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;use app\common\exception\BusinessException;use app\common\service\FulfillmentService;use app\validate\FulfillmentValidate;use think\facade\Db;use think\Request;use think\Response;
class FulfillmentController extends ApiController
{
 public function index(int $orderId):Response{return $this->success(Db::name('fulfillments')->where('order_id',$orderId)->order('id','desc')->select());}
 public function create(Request $request,int $orderId):Response{$this->validate($request->post(),FulfillmentValidate::class);return $this->success((new FulfillmentService())->create($orderId,$request->post(),(int)$this->requireMember()->id),'履约单创建成功',201);}
 public function ship(Request $request,int $id):Response{$this->validate($request->post(),['carrier_code'=>'require|max:64','tracking_no'=>'require|max:128'],['carrier_code.require'=>'物流商不能为空','tracking_no.require'=>'运单号不能为空']);return $this->success((new FulfillmentService())->ship($id,$request->post(),(int)$this->requireMember()->id),'订单已发货');}
 public function tracking(int $packageId):Response{if(!Db::name('shipment_packages')->where('id',$packageId)->find())throw BusinessException::notFound('包裹不存在');return $this->success(Db::name('shipment_tracking_events')->where('package_id',$packageId)->order('occurred_at','asc')->select());}
 public function addTracking(Request $request,int $packageId):Response{$this->validate($request->post(),['event_status'=>'require|max:64','description'=>'require|max:500','occurred_at'=>'dateFormat:Y-m-d H:i:s'],['event_status.require'=>'物流状态不能为空','description.require'=>'轨迹描述不能为空']);return $this->success((new FulfillmentService())->addTrackingEvent($packageId,$request->post()),'物流轨迹已记录',201);}
}
