<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

final class StorefrontSiteListValidate extends Validate
{
    protected $rule = ['page'=>'integer|egt:1','page_size'=>'integer|between:1,100','status'=>'in:draft,active,maintenance,disabled','locale'=>'max:16','currency'=>'alpha|length:3','sort_order'=>'in:asc,desc'];
}
