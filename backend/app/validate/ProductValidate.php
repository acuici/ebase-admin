<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class ProductValidate extends Validate
{
    protected $rule = [
        'product_no' => 'require|max:32|alphaDash',
        'name' => 'require|max:160',
        'brand' => 'max:80',
        'status' => 'in:draft,active,archived',
    ];
    protected $message = [
        'product_no.require' => '商品编号不能为空',
        'product_no.alphaDash' => '商品编号只能包含字母、数字、下划线和短横线',
        'name.require' => '商品名称不能为空',
        'status.in' => '商品状态不合法',
    ];
}
