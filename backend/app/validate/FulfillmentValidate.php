<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class FulfillmentValidate extends Validate
{
 protected $rule=['recipient_snapshot'=>'require|array','warehouse_code'=>'max:64','shipping_method'=>'max:80','carrier_code'=>'require|max:64','tracking_no'=>'require|max:128'];
 protected $message=['recipient_snapshot.require'=>'收件信息快照不能为空','recipient_snapshot.array'=>'收件信息快照格式不合法','carrier_code.require'=>'物流商不能为空','tracking_no.require'=>'运单号不能为空'];
}
