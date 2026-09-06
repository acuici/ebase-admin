<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

final class SecondaryWarehouseCreateValidate extends Validate
{
    protected $rule = [
        'warehouse_code' => 'require|max:64',
        'name' => 'require|max:120',
        'owner_code' => 'max:64',
        'city' => 'max:80',
        'capacity_rate' => 'float|between:0,100',
        'inbound_quantity' => 'integer|egt:0',
        'outbound_quantity' => 'integer|egt:0',
        'status' => 'require|in:active,disabled',
    ];
}
