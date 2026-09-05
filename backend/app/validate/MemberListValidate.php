<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

final class MemberListValidate extends Validate
{
    protected $rule = ['page'=>'integer|egt:1','page_size'=>'integer|between:1,100','status'=>'in:0,1','sort_order'=>'in:asc,desc'];
}
