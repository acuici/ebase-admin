<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\service\SecondaryOperationService;
use app\common\service\WarehouseService;
use app\validate\SecondaryWarehouseCreateValidate;
use app\validate\SecondaryWarehouseUpdateValidate;
use think\App;
use think\Request;
use think\Response;

final class WarehouseController extends ApiController
{
    private WarehouseService $service;
    private SecondaryOperationService $crud;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new WarehouseService();
        $this->crud = new SecondaryOperationService();
    }

    public function index(Request $request): Response
    {
        return $this->success($this->service->list($request->get()));
    }

    public function create(Request $request): Response
    {
        $data = $request->post();
        $this->validate($data, SecondaryWarehouseCreateValidate::class);
        $this->service->assertActiveOwner($data['owner_code'] ?? null);
        $member = $this->requireMember();
        return $this->success($this->crud->create('warehouses', $data, (int) $member->id, $this->warehouseRequestId($request)), '仓库已创建', 201);
    }

    public function update(Request $request, int $id): Response
    {
        $data = $request->post();
        $this->validate($data, SecondaryWarehouseUpdateValidate::class);
        $this->service->assertActiveOwner($data['owner_code'] ?? null);
        $member = $this->requireMember();
        return $this->success($this->crud->update('warehouses', $id, $data, (int) $member->id, $this->warehouseRequestId($request)), '仓库已更新');
    }

    public function delete(Request $request, int $id): Response
    {
        $member = $this->requireMember();
        $this->service->disable($id, (int) $member->id, $this->warehouseRequestId($request));
        return $this->success(null, '仓库已停用');
    }

    public function members(): Response
    {
        return $this->success(['items' => $this->service->members()]);
    }

    public function read(int $id): Response
    {
        return $this->success($this->service->find($id));
    }

    public function assign(Request $request, int $id): Response
    {
        $data = $request->post();
        $this->validate($data, ['owner_code' => 'require|max:64']);
        $member = $this->requireMember();
        return $this->success($this->service->assign($id, (string) $data['owner_code'], (int) $member->id, $this->warehouseRequestId($request)), '负责人已分配');
    }

    public function handover(Request $request, int $id): Response
    {
        $data = $request->post();
        $this->validate($data, ['to_owner_code' => 'require|max:64', 'handover_type' => 'require|in:temporary,permanent', 'reason' => 'max:255']);
        $member = $this->requireMember();
        return $this->success($this->service->handover($id, (string) $data['to_owner_code'], (string) $data['handover_type'], $data['reason'] ?? null, (int) $member->id, $this->warehouseRequestId($request)), '负责人交接已生效');
    }

    public function restore(Request $request, int $id): Response
    {
        $member = $this->requireMember();
        return $this->success($this->service->restore($id, (int) $member->id, $this->warehouseRequestId($request)), '临时交接已恢复');
    }

    public function handovers(int $id): Response
    {
        return $this->success(['items' => $this->service->handovers($id)]);
    }

    private function warehouseRequestId(Request $request): string
    {
        return (string) ($request->requestId ?? $request->header('x-request-id', ''));
    }
}
