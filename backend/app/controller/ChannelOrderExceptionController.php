<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\service\ChannelOrderExceptionService;
use app\validate\ChannelOrderExceptionResolveValidate;
use think\App;
use think\Request;
use think\Response;
final class ChannelOrderExceptionController extends ApiController
{
    private ChannelOrderExceptionService $service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new ChannelOrderExceptionService();
    }
    public function index(Request $request): Response { return $this->success($this->service->list($request->get(), max(1, (int) $request->get('page', 1)), min(100, max(1, (int) $request->get('page_size', 20))))); }
    public function read(int $id): Response { return $this->success($this->service->read($id)); }
    public function resolve(Request $request, int $id): Response { $this->validate($request->post(), ChannelOrderExceptionResolveValidate::class); return $this->success($this->service->resolve($id, (int) $request->post('product_sku_id'), (int) $this->requireMember()->id, (string) $request->post('note', ''))); }
    public function ignore(Request $request, int $id): Response { return $this->success($this->service->ignore($id, (int) $this->requireMember()->id, (string) $request->post('note', '')), '异常已忽略'); }
}
