<?php
declare(strict_types=1);
namespace app\validate;
use think\Validate;
class ChannelStoreValidate extends Validate
{
    protected $rule = ['store_code' => 'require|max:64', 'channel_type' => 'require|in:taobao,tmall,jd,pdd,douyin', 'external_store_id' => 'require|max:128', 'name' => 'require|max:160', 'status' => 'in:pending_auth,active,expired,disabled', 'authorization_status' => 'in:unbound,valid,expiring,expired,revoked'];
    protected $message = ['store_code.require' => '店铺编码不能为空', 'channel_type.in' => '平台类型不合法', 'external_store_id.require' => '平台店铺 ID 不能为空', 'name.require' => '店铺名称不能为空'];
}
