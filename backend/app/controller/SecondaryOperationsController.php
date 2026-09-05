<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\service\SecondaryOperationService;
use think\App;
use think\Request;
use think\Response;

final class SecondaryOperationsController extends ApiController
{
    private const PERMISSIONS = [
        'refunds' => 'refund.refund.manage',
        'warehouses' => 'inventory.warehouse.manage',
        'categories' => 'catalog.category.manage',
        'suppliers' => 'supply.supplier.manage',
        'segments' => 'customer.segment.manage',
        'approvals' => 'marketing.approval.manage',
    ];
    private SecondaryOperationService $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service=new SecondaryOperationService();
    }
    public function create(Request $request,string $type):Response
    {
        $this->authorizeType($type);$this->assertKnownFields($type,$request->post(),'create');$this->validatePayload($type,$request->post(),'create');$member=$this->requireMember();$requestId=(string)($request->requestId??$request->header('x-request-id',''));
        return $this->success($this->service->create($type,$request->post(),(int)$member->id,$requestId),'记录已创建',201);
    }
    public function update(Request $request,string $type,int $id):Response
    {
        $this->authorizeType($type);$this->assertKnownFields($type,$request->post(),'update');$this->validatePayload($type,$request->post(),'update');$member=$this->requireMember();$requestId=(string)($request->requestId??$request->header('x-request-id',''));
        return $this->success($this->service->update($type,$id,$request->post(),(int)$member->id,$requestId),'记录已更新');
    }
    public function delete(Request $request,string $type,int $id):Response
    {
        $this->authorizeType($type);$member=$this->requireMember();$requestId=(string)($request->requestId??$request->header('x-request-id',''));$this->service->delete($type,$id,(int)$member->id,$requestId);return $this->success(null,'记录已删除');
    }
    private function authorizeType(string $type):void
    {
        if(!isset(self::PERMISSIONS[$type]))$this->error('RESOURCE_NOT_FOUND','资源类型不存在',404);
        $member=$this->requireMember();$codes=$member->getPermissionCodes();if(!in_array('*',$codes,true)&&!in_array(self::PERMISSIONS[$type],$codes,true))throw new \app\common\exception\BusinessException('FORBIDDEN','无权操作该资源',403);
    }
    private function assertKnownFields(string $type,array $data,string $operation):void
    {
        $fields=['refunds'=>['create'=>['refund_no','payment_id','order_id','amount','currency','channel','status','reason'],'update'=>['status','reason','channel_refund_id']],'warehouses'=>['create'=>['warehouse_code','name','supplier_id','status'],'update'=>['name','supplier_id','status']],'categories'=>['create'=>['category_code','name','parent_id','status'],'update'=>['name','parent_id','status']],'suppliers'=>['create'=>['supplier_code','name','status'],'update'=>['name','status']],'segments'=>['create'=>['name','description','rules','status'],'update'=>['name','description','rules','status']],'approvals'=>['create'=>['request_type','resource_id','status','comment'],'update'=>['status','comment']]];
        $unknown=array_diff(array_keys($data),$fields[$type][$operation]??[]);if($unknown)throw new \app\common\exception\BusinessException('VALIDATION_ERROR','存在未声明字段',422,['unknown'=>array_values($unknown)]);
    }

    private function validatePayload(string $type,array $data,string $operation):void
    {
        $classes=['refunds'=>['create'=>\app\validate\SecondaryRefundCreateValidate::class,'update'=>\app\validate\SecondaryRefundUpdateValidate::class],'warehouses'=>['create'=>\app\validate\SecondaryWarehouseCreateValidate::class,'update'=>\app\validate\SecondaryWarehouseUpdateValidate::class],'categories'=>['create'=>\app\validate\SecondaryCategoryCreateValidate::class,'update'=>\app\validate\SecondaryCategoryUpdateValidate::class],'suppliers'=>['create'=>\app\validate\SecondarySupplierCreateValidate::class,'update'=>\app\validate\SecondarySupplierUpdateValidate::class],'segments'=>['create'=>\app\validate\SecondarySegmentCreateValidate::class,'update'=>\app\validate\SecondarySegmentUpdateValidate::class],'approvals'=>['create'=>\app\validate\SecondaryApprovalCreateValidate::class,'update'=>\app\validate\SecondaryApprovalUpdateValidate::class]];
        $this->validate($data,$classes[$type][$operation]);
    }
}
