<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

final class ChannelProductSkuUpdateValidate extends Validate
{
    protected $rule = [
        'product_sku_id' => 'integer|gt:0',
        'external_sku_id' => 'max:128',
        'merchant_sku_code' => 'max:128',
        'channel_price' => 'float|egt:0',
        'currency' => 'alpha|length:3',
        'inventory_policy' => 'in:shared,allocated,disabled',
        'allocated_quantity' => 'integer|egt:0',
        'listing_status' => 'in:draft,published,offline,archived',
        'sync_status' => 'in:pending,syncing,synced,failed,conflict',
    ];

    protected $message = [
        'channel_price.float' => '渠道价格必须是数字',
        'channel_price.egt' => '渠道价格不能为负数',
        'inventory_policy.in' => '库存策略不合法',
    ];

    protected function sceneUpdate(): self
    {
        return $this->only(array_keys($this->rule));
    }
}
