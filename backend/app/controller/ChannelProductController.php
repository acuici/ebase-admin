<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\service\ChannelProductService;
use app\common\service\ChannelSkuMappingService;
use app\validate\ChannelProductValidate;
use app\validate\ChannelProductSkuUpdateValidate;
use app\validate\ChannelProductSkuValidate;
use think\App;
use think\Request;
use think\Response;
final class ChannelProductController extends ApiController
{
    private ChannelProductService $products; private ChannelSkuMappingService $mappings;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->products = new ChannelProductService();
        $this->mappings = new ChannelSkuMappingService();
    }
    public function index(Request $request): Response { return $this->success($this->products->list($request->only(['channel_type','channel_store_id','product_id','listing_status','sync_status','keyword']), max(1, (int) $request->get('page', 1)), min(100, max(1, (int) $request->get('page_size', 20))))); }
    public function create(Request $request): Response { $this->validate($request->post(), ChannelProductValidate::class); return $this->success($this->products->create($request->post()), '平台商品创建成功', 201); }
    public function read(int $id): Response { return $this->success($this->products->read($id)); }
    public function update(Request $request, int $id): Response { return $this->success($this->products->update($id, $request->only(['title','merchant_product_code','category_id','category_name','listing_status','sync_status','platform_payload']))); }
    public function archive(int $id): Response { return $this->success($this->products->archive($id), '平台商品已归档'); }
    public function sync(int $id): Response { $this->products->read($id); return $this->error('UPSTREAM_UNAVAILABLE', '尚未配置真实平台同步适配器', 502); }
    public function createSku(Request $request, int $id): Response { $this->validate($request->post(), ChannelProductSkuValidate::class); return $this->success($this->mappings->create($id, $request->post()), '平台 SKU 映射成功', 201); }
    public function updateSku(Request $request, int $id): Response
    {
        $data = $request->post();
        if ($data === []) {
            return $this->error('VALIDATION_ERROR', '至少提交一个可更新字段', 422);
        }
        $this->validate($data, ChannelProductSkuUpdateValidate::class);
        return $this->success($this->mappings->update($id, $data));
    }
    public function archiveSku(int $id): Response { return $this->success($this->mappings->archive($id), '平台 SKU 映射已归档'); }
}
