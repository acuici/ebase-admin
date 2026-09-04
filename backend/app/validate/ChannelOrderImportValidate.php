<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

class ChannelOrderImportValidate extends Validate
{
    protected $rule = [
        'channel_type' => 'require|in:storefront,taobao,tmall,jd,pdd,douyin,wechat_miniapp',
        'channel_store_id' => 'integer|gt:0',
        'external_order_no' => 'require|max:128',
        'status' => 'in:pending_payment,paid,processing,shipped,completed,cancelled',
        'total_amount' => 'require|regex:^\\d{1,10}(\\.\\d{1,2})?$',
        'currency' => 'require|alpha|length:3',
        'raw_payload' => 'array',
        'items' => 'array',
    ];

    protected $message = [
        'channel_type.in' => '渠道类型不合法',
        'external_order_no.require' => '外部订单号不能为空',
        'total_amount.regex' => '订单金额必须是两位以内小数',
        'currency.length' => '币种必须是 3 位代码',
    ];
}
