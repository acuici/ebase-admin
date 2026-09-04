<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\service\ChannelStoreService;
use app\validate\ChannelStoreValidate;
use think\App;
use think\Request;
use think\Response;
final class ChannelStoreController extends ApiController
{
    private ChannelStoreService $service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new ChannelStoreService();
    }
    public function index(Request $request): Response { return $this->success($this->service->list($request->only(['channel_type','status','authorization_status','keyword']), max(1, (int) $request->get('page', 1)), min(100, max(1, (int) $request->get('page_size', 20))))); }
    public function create(Request $request): Response { $this->validate($request->post(), ChannelStoreValidate::class); return $this->success($this->service->create($request->post()), '渠道店铺创建成功', 201); }
    public function read(int $id): Response { return $this->success($this->service->read($id)); }
    public function update(Request $request, int $id): Response { $this->validate($request->post(), ChannelStoreValidate::class, [], false); return $this->success($this->service->update($id, $request->only(['name','status','authorization_status','authorized_at','authorization_expires_at','credential_ref']))); }
    public function disable(int $id): Response { return $this->success($this->service->disable($id), '渠道店铺已停用'); }
    public function sync(int $id): Response { $this->service->read($id); return $this->error('UPSTREAM_UNAVAILABLE', '尚未配置真实平台同步适配器', 502); }
}
