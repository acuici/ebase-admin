<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class ProductSkuValidate extends Validate
{
    protected $rule = [
        'sku_code' => 'require|max:64|alphaDash',
        'name' => 'require|max:160',
        'specs' => 'require|array',
        'price' => 'require|float|egt:0',
        'market_price' => 'float|egt:0',
        'stock_quantity' => 'integer|egt:0',
        'status' => 'in:active,inactive',
    ];
    protected $message = [
        'sku_code.require' => 'SKU 编码不能为空',
        'sku_code.alphaDash' => 'SKU 编码只能包含字母、数字、下划线和短横线',
        'name.require' => 'SKU 名称不能为空',
        'specs.require' => '规格不能为空',
        'specs.array' => '规格格式不合法',
        'price.require' => '售价不能为空',
        'price.egt' => '售价不能小于 0',
        'stock_quantity.egt' => '库存不能小于 0',
        'status.in' => 'SKU 状态不合法',
    ];
}
