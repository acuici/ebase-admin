<?php
declare(strict_types=1);
namespace app\validate;
use think\Validate;
class ChannelProductSkuValidate extends Validate
{
    protected $rule = ['product_sku_id' => 'require|integer|gt:0', 'external_sku_id' => 'require|max:128', 'currency' => 'alpha|length:3', 'inventory_policy' => 'in:shared,allocated,disabled', 'listing_status' => 'in:draft,published,offline,archived', 'sync_status' => 'in:pending,syncing,synced,failed,conflict'];
    protected $message = ['product_sku_id.require' => '内部 SKU 不能为空', 'external_sku_id.require' => '平台 SKU ID 不能为空', 'inventory_policy.in' => '库存策略不合法'];
}
