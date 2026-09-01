<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\Order;
use app\common\service\OrderService;
use app\validate\OrderValidate;
use think\Request;
use think\Response;
class OrderController extends ApiController
{
    public function index(Request $request): Response
    {
        $page=max(1,(int)$request->get('page',1)); $size=min(100,max(1,(int)$request->get('page_size',20)));
        $query=Order::with('items');
        if ($status=$request->get('status')) $query->where('status',$status);
        if ($channel=$request->get('channel_type')) $query->where('channel_type',$channel);
        if ($storeId=$request->get('channel_store_id')) $query->where('channel_store_id',(int)$storeId);
        if ($no=trim((string)$request->get('order_no',''))) $query->whereLike('order_no|external_order_no','%'.addcslashes($no,'%_').'%');
        $total=$query->count(); $items=$query->order('id','desc')->page($page,$size)->select();
        return $this->paginated($items,$page,$size,$total);
    }
    public function read(int $id): Response
    {
        $order=Order::with('items')->find($id); if(!$order) throw BusinessException::notFound('订单不存在');
        $data=$order->toArray();
        // 敏感/渠道原始字段仅作为独立扩展返回，不混入订单主字段。
        $data['channel_extension']=\think\facade\Db::name('order_channel_extensions')->where('order_id',$id)->find();
        $data['payments']=\think\facade\Db::name('payments')->where('order_id',$id)->order('id','desc')->select();
        $data['fulfillments']=\think\facade\Db::name('fulfillments')->where('order_id',$id)->order('id','desc')->select();
        $data['status_logs']=\think\facade\Db::name('order_status_logs')->where('order_id',$id)->order('id','asc')->select();
        return $this->success($data);
    }
    public function create(Request $request): Response
    {
        $this->validate($request->post(),OrderValidate::class);
        $order=(new OrderService())->create($request->post('items'),(int)$this->requireMember()->id);
        return $this->success($order,'订单创建成功，库存已预占',201);
    }
    public function transition(Request $request,int $id): Response
    {
        $this->validate($request->post(),['status'=>'require|in:paid,processing,shipped,completed,cancelled','remark'=>'max:255'],['status.require'=>'目标状态不能为空','status.in'=>'目标状态不合法']);
        $data=$request->post();
        $order=(new OrderService())->transition($id,$data['status'],(int)$this->requireMember()->id,'api',$data['remark']??null);
        return $this->success($order,'订单状态已更新');
    }
    public function cancel(Request $request,int $id): Response
    {
        $order=(new OrderService())->transition($id,OrderService::CANCELLED,(int)$this->requireMember()->id,'api',trim((string)$request->post('remark','手动取消')));
        return $this->success($order,'订单已取消，库存已释放');
    }
}
