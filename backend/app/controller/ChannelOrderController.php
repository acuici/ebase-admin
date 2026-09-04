<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\service\ChannelOrderImportService;
use app\common\service\OrderService;
use app\validate\ChannelOrderImportValidate;
use think\facade\Db;
use think\Request;
use think\Response;
final class ChannelOrderController extends ApiController
{
    public function import(Request $request): Response { $this->validate($request->post(), ChannelOrderImportValidate::class); return $this->success((new ChannelOrderImportService())->import($request->post(), (int) $this->requireMember()->id), '渠道订单已导入', 201); }
    public function confirmInventory(int $id): Response
    {
        return Db::transaction(function () use ($id): Response {
            $extension = Db::name('order_channel_extensions')->where('order_id', $id)->lock(true)->find();
            $order = Db::name('orders')->where('id', $id)->lock(true)->find();
            if (!$order || !$extension) return $this->error('RESOURCE_NOT_FOUND', '渠道订单不存在', 404);
            if ($extension['inventory_processed_at']) return $this->success(['order_id' => $id, 'inventory_processed' => true, 'processed_at' => $extension['inventory_processed_at']], '库存已处理，幂等成功');
            if ($extension['mapping_status'] !== 'resolved' || $order['status'] !== 'paid') return $this->error('CHANNEL_ORDER_INVENTORY_CONFIRM_INVALID', '仅允许已完成映射且状态为已支付的订单确认库存', 409);
            $items = Db::name('order_items')->where('order_id', $id)->select()->toArray();
            (new OrderService())->confirmInventory($items, $id, (int) $this->requireMember()->id);
            $now = date('Y-m-d H:i:s'); Db::name('order_channel_extensions')->where('order_id', $id)->update(['inventory_processed_at' => $now, 'updated_at' => $now]);
            Db::name('order_status_logs')->insert(['order_id' => $id, 'from_status' => 'paid', 'to_status' => 'paid', 'operator_id' => (int) $this->requireMember()->id, 'source' => 'channel_import', 'remark' => '人工确认库存处理', 'created_at' => $now]);
            return $this->success(['order_id' => $id, 'inventory_processed' => true, 'processed_at' => $now], '库存处理成功');
        });
    }
}
