<?php
declare(strict_types=1);
namespace app\validate;
use think\Validate;
class ChannelOrderExceptionResolveValidate extends Validate
{
    protected $rule = ['product_sku_id' => 'require|integer|gt:0', 'note' => 'max:500'];
    protected $message = ['product_sku_id.require' => '内部 SKU 不能为空'];
}
