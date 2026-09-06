<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

final class SecondaryWarehouseUpdateValidate extends Validate
{
    protected $rule = [
        'name' => 'max:120',
        'owner_code' => 'max:64',
        'city' => 'max:80',
        'capacity_rate' => 'float|between:0,100',
        'inbound_quantity' => 'integer|egt:0',
        'outbound_quantity' => 'integer|egt:0',
        'status' => 'in:active,disabled',
    ];
}
