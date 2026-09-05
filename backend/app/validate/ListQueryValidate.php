<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

final class ListQueryValidate extends Validate
{
    protected $rule = [
        'page' => 'integer|egt:1',
        'page_size' => 'integer|between:1,100',
        'sort_order' => 'in:asc,desc',
        'start_date' => 'dateFormat:Y-m-d',
        'end_date' => 'dateFormat:Y-m-d',
    ];

    protected $message = [
        'page_size.between' => '每页数量必须在 1 到 100 之间',
        'sort_order.in' => '排序方向不合法',
    ];

    public function checkDateRange(array $data): bool
    {
        if (empty($data['start_date']) || empty($data['end_date'])) return true;
        return strtotime($data['end_date']) >= strtotime($data['start_date'])
            && (strtotime($data['end_date']) - strtotime($data['start_date'])) <= 180 * 86400;
    }
}
