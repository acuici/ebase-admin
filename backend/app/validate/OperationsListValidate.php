<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

final class OperationsListValidate extends Validate
{
    protected $rule = [
        'inventory_status' => 'in:active,low_stock,out_of_stock',
        'sales_status' => 'in:active,draft,archived',
        'validity' => 'in:active,expiring,ended',
        'turnover_days' => 'in:lte_7,8_to_30,gt_30',
        'content_status' => 'in:draft,pending_review,published,archived',
        'start_date' => 'dateFormat:Y-m-d', 'end_date' => 'dateFormat:Y-m-d',
    ];
}
