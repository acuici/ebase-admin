<?php
declare(strict_types=1);
namespace app\validate;
use think\Validate;
class ChannelProductValidate extends Validate
{
    protected $rule = ['channel_store_id' => 'require|integer|gt:0', 'product_id' => 'require|integer|gt:0', 'external_product_id' => 'require|max:128', 'listing_status' => 'in:draft,pending_review,published,offline,rejected,archived', 'sync_status' => 'in:pending,syncing,synced,failed,conflict'];
    protected $message = ['channel_store_id.require' => '渠道店铺不能为空', 'product_id.require' => '内部商品不能为空', 'external_product_id.require' => '平台商品 ID 不能为空'];
}
