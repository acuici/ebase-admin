<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

final class SecondaryRefundCreateValidate extends Validate { protected $rule=['refund_no'=>'require|max:40','payment_id'=>'require|integer|gt:0','order_id'=>'require|integer|gt:0','amount'=>'require|regex:/^\\d{1,10}(\\.\\d{1,2})?$/','currency'=>'require|alpha|length:3','channel'=>'require|max:32','status'=>'in:pending,processing,succeeded,failed','reason'=>'max:255']; }
final class SecondaryRefundUpdateValidate extends Validate { protected $rule=['status'=>'require|in:pending,processing,succeeded,failed','reason'=>'max:255','channel_refund_id'=>'max:128']; }
final class SecondaryCategoryCreateValidate extends Validate { protected $rule=['category_code'=>'require|max:64','name'=>'require|max:120','parent_id'=>'integer|egt:0','status'=>'in:active,disabled']; }
final class SecondaryCategoryUpdateValidate extends Validate { protected $rule=['name'=>'max:120','parent_id'=>'integer|egt:0','status'=>'in:active,disabled']; }
final class SecondarySupplierCreateValidate extends Validate { protected $rule=['supplier_code'=>'require|max:64','name'=>'require|max:160','status'=>'in:active,disabled']; }
final class SecondarySupplierUpdateValidate extends Validate { protected $rule=['name'=>'max:160','status'=>'in:active,disabled']; }
final class SecondarySegmentCreateValidate extends Validate { protected $rule=['name'=>'require|max:120','description'=>'max:255','rules'=>'require|array','status'=>'in:active,disabled']; }
final class SecondarySegmentUpdateValidate extends Validate { protected $rule=['name'=>'max:120','description'=>'max:255','rules'=>'array','status'=>'in:active,disabled']; }
final class SecondaryApprovalCreateValidate extends Validate { protected $rule=['request_type'=>'require|max:48','resource_id'=>'require|integer|gt:0','status'=>'in:pending,approved,rejected','comment'=>'max:500']; }
final class SecondaryApprovalUpdateValidate extends Validate { protected $rule=['status'=>'require|in:pending,approved,rejected','comment'=>'max:500']; }
