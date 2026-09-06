<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BusinessException;
use think\facade\Db;

final class SecondaryOperationService
{
    private const CONFIG = [
        'refunds' => ['table'=>'refunds','create'=>['refund_no','payment_id','order_id','amount','currency','channel','status','reason'],'update'=>['status','reason','channel_refund_id']],
        'warehouses' => ['table'=>'warehouses','create'=>['warehouse_code','name','owner_code','city','capacity_rate','inbound_quantity','outbound_quantity','status'],'update'=>['name','owner_code','city','capacity_rate','inbound_quantity','outbound_quantity','status']],
        'categories' => ['table'=>'categories','create'=>['category_code','name','parent_id','status'],'update'=>['name','parent_id','status']],
        'suppliers' => ['table'=>'suppliers','create'=>['supplier_code','name','status'],'update'=>['name','status']],
        'segments' => ['table'=>'customer_segments','create'=>['name','description','rules','status'],'update'=>['name','description','rules','status']],
        'approvals' => ['table'=>'approval_requests','create'=>['request_type','resource_id','status','comment'],'update'=>['status','comment']],
    ];

    public function create(string $type, array $data, int $memberId, string $requestId): array
    {
        $config=$this->config($type);$this->assertFields($type,$data,'create');$this->assertWarehouseOwner($type,$data);$clean=$this->clean($config['create'],$data);$now=date('Y-m-d H:i:s');$clean['created_at']=$now;$clean['updated_at']=$now;
        if($type==='approvals')$clean['submitted_by']=$memberId;
        $id=Db::name($config['table'])->insertGetId($clean);$this->log($memberId,$requestId,'create',$type,(int)$id,$now);return Db::name($config['table'])->where('id',$id)->find();
    }
    public function update(string $type,int $id,array $data,int $memberId,string $requestId):array
    {
        $config=$this->config($type);$this->assertFields($type,$data,'update');$this->assertWarehouseOwner($type,$data);if(!Db::name($config['table'])->where('id',$id)->find())throw BusinessException::notFound('记录不存在');$clean=$this->clean($config['update'],$data);if(!$clean)throw new BusinessException('VALIDATION_ERROR','至少提交一个可更新字段',422);$clean['updated_at']=date('Y-m-d H:i:s');Db::name($config['table'])->where('id',$id)->update($clean);$this->log($memberId,$requestId,'update',$type,$id,$clean['updated_at']);return Db::name($config['table'])->where('id',$id)->find();
    }
    public function delete(string $type,int $id,int $memberId,string $requestId):void
    {
        $config=$this->config($type);if(!Db::name($config['table'])->where('id',$id)->find())throw BusinessException::notFound('记录不存在');$now=date('Y-m-d H:i:s');if($type==='warehouses'){Db::name($config['table'])->where('id',$id)->update(['status'=>'disabled','updated_at'=>$now]);}else{Db::name($config['table'])->where('id',$id)->delete();}$this->log($memberId,$requestId,'delete',$type,$id,$now);
    }
    private function config(string $type):array{if(!isset(self::CONFIG[$type]))throw new BusinessException('RESOURCE_NOT_FOUND','资源类型不存在',404);return self::CONFIG[$type];}
    private function assertFields(string $type,array $data,string $operation):void{ $config=$this->config($type);$unknown=array_diff(array_keys($data),$config[$operation]);if($unknown)throw new BusinessException('VALIDATION_ERROR','存在未声明字段',422,['unknown'=>array_values($unknown)]);if($operation==='create'){foreach($config['create'] as $field)if(in_array($field,['refund_no','payment_id','order_id','amount','currency','channel','request_type','resource_id','warehouse_code','name','category_code','supplier_code'],true)&&(!array_key_exists($field,$data)||$data[$field]===''))throw new BusinessException('VALIDATION_ERROR','缺少必填字段',422,['field'=>[$field]]);}}
    private function clean(array $allowed,array $data):array{$clean=array_intersect_key($data,array_flip($allowed));if(isset($clean['rules'])&&is_array($clean['rules']))$clean['rules']=json_encode($clean['rules'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);return $clean;}
    private function assertWarehouseOwner(string $type,array $data):void{if($type!=='warehouses'||!isset($data['owner_code'])||$data['owner_code']==='')return;$member=Db::name('members')->where('member_code',$data['owner_code'])->where('status',1)->find();if(!$member)throw new BusinessException('VALIDATION_ERROR','负责人不存在或已停用',422,['owner_code'=>['负责人不存在或已停用']]);}
    private function log(int $memberId,string $requestId,string $action,string $type,int $id,string $now):void{Db::name('operation_logs')->insert(['operator_id'=>$memberId,'module'=>'secondary_operations','action'=>$action,'resource_type'=>$type,'resource_id'=>(string)$id,'result'=>'success','risk_level'=>'medium','detail'=>json_encode(['request_id'=>$requestId],JSON_UNESCAPED_UNICODE),'created_at'=>$now]);}
}
