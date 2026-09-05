<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

abstract class SecondaryOperationValidate extends Validate
{
    protected function rejectUnknown(array $data, array $allowed): void
    {
        $unknown=array_diff(array_keys($data),$allowed);
        if($unknown)$this->error=['unknown'=>['存在未声明字段：'.implode(',',array_values($unknown))]];
    }
}
