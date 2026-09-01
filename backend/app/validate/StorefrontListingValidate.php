<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class StorefrontListingValidate extends Validate
{
 protected $rule=['product_id'=>'require|integer|gt:0','title'=>'require|max:255','slug'=>'require|max:255|alphaDash','status'=>'in:draft,published,scheduled,archived','price'=>'float|egt:0','inventory_policy'=>'in:shared,allocated'];
 protected $message=['product_id.require'=>'商品不能为空','title.require'=>'独立站标题不能为空','slug.require'=>'URL Slug 不能为空','slug.alphaDash'=>'Slug 只能包含字母、数字、下划线和短横线','status.in'=>'发布状态不合法','inventory_policy.in'=>'库存策略不合法'];
}
