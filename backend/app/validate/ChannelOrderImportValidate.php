<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class ChannelOrderImportValidate extends Validate
{
 protected $rule=['channel_type'=>'require|in:storefront,tmall,jd,douyin,wechat_miniapp','external_order_no'=>'require|max:128','total_amount'=>'require|float|egt:0','currency'=>'require|alpha|length:3','raw_payload'=>'require|array'];
 protected $message=['channel_type.in'=>'渠道类型不合法','external_order_no.require'=>'外部订单号不能为空','total_amount.require'=>'订单金额不能为空','currency.length'=>'币种必须是 3 位代码','raw_payload.require'=>'渠道原始数据不能为空'];
}
