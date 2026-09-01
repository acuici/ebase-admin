<?php
declare (strict_types=1);
namespace app\common\model;
use think\Model;
class ProductSku extends Model
{
    protected $name = 'product_skus';
    protected $json = ['specs'];
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
