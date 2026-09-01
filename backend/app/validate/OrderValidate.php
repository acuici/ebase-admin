<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class OrderValidate extends Validate
{
    protected $rule = [
        'items' => 'require|array|min:1|max:50',
        'items.*.sku_id' => 'require|integer|gt:0',
        'items.*.quantity' => 'require|integer|between:1,9999',
    ];
    protected $message = [
        'items.require' => '订单商品不能为空',
        'items.array' => '订单商品格式不合法',
        'items.min' => '订单至少需要一件商品',
        'items.*.sku_id.require' => 'SKU 不能为空',
        'items.*.quantity.between' => '购买数量必须在 1 到 9999 之间',
    ];
}
