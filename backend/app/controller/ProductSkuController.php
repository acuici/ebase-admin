<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\Product;
use app\common\model\ProductSku;
use app\common\service\InventoryService;
use app\validate\ProductSkuValidate;
use think\Request;
use think\Response;
class ProductSkuController extends ApiController
{
    public function index(int $productId): Response
    {
        if (!Product::find($productId)) throw BusinessException::notFound('商品不存在');
        return $this->success(ProductSku::where('product_id', $productId)->order('id')->select());
    }
    public function create(Request $request, int $productId): Response
    {
        if (!Product::find($productId)) throw BusinessException::notFound('商品不存在');
        $this->validate($request->post(), ProductSkuValidate::class);
        $data = $request->only(['sku_code','name','specs','price','market_price','stock_quantity','status']);
        $initialStock = (int) ($data['stock_quantity'] ?? 0);
        $data['product_id'] = $productId;
        $data['reserved_quantity'] = 0;
        // 必须从 0 入库，通过库存服务写入首条流水。
        $data['stock_quantity'] = 0;
        $sku = ProductSku::create($data);
        if ($initialStock > 0) {
            (new InventoryService())->adjust((int) $sku->id, $initialStock, 'restock', 'sku_create', (string) $sku->id, $this->requireMember()->id);
            $sku = ProductSku::find($sku->id);
        }
        return $this->success($sku, 'SKU 创建成功', 201);
    }
    public function update(Request $request, int $productId, int $id): Response
    {
        $sku = ProductSku::where('product_id', $productId)->where('id', $id)->find();
        if (!$sku) throw BusinessException::notFound('SKU 不存在');
        $data = $request->only(['sku_code','name','specs','price','market_price','status']);
        $this->validate(array_merge($sku->toArray(), $data), ProductSkuValidate::class);
        $sku->save($data);
        return $this->success($sku, 'SKU 更新成功');
    }
    public function adjustStock(Request $request, int $productId, int $id): Response
    {
        $sku = ProductSku::where('product_id', $productId)->where('id', $id)->find();
        if (!$sku) throw BusinessException::notFound('SKU 不存在');
        $this->validate($request->post(), ['quantity' => 'require|integer|notIn:0', 'reason' => 'require|in:restock,correction'], ['quantity.require' => '库存变动数量不能为空', 'quantity.notIn' => '库存变动数量不能为 0', 'reason.in' => '库存原因不合法']);
        $data = $request->post();
        $service = new InventoryService();
        $token = $service->acquireLock($id);
        try {
            $result = $service->adjust($id, (int) $data['quantity'], $data['reason'], 'manual_adjustment', null, $this->requireMember()->id);
        } finally {
            $service->releaseLock($id, $token);
        }
        return $this->success($result, '库存调整成功');
    }
}
