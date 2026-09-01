<?php
declare (strict_types=1);
namespace app\common\model;
use think\Model;
class Order extends Model
{
    protected $name = 'orders';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    public function items() { return $this->hasMany(OrderItem::class, 'order_id'); }
}
