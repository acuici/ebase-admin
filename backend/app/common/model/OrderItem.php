<?php
declare (strict_types=1);
namespace app\common\model;
use think\Model;
class OrderItem extends Model
{
    protected $name = 'order_items';
    public $autoWriteTimestamp = false;
}
