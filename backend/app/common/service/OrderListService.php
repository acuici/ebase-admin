<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BusinessException;
use think\facade\Db;

final class OrderListService
{
    private const ALLOWED = ['page','page_size','keyword','order_no','status','channel_type','channel_store_id','payment_method','fulfillment_status','start_date','end_date','sort_by','sort_order'];
    private const SORTS = ['id','order_no','total_amount','status','created_at','paid_at'];

    public function list(array $input): array
    {
        $unknown = array_diff(array_keys($input), self::ALLOWED);
        if ($unknown) throw new BusinessException('VALIDATION_ERROR','存在未知查询参数',422,['unknown'=>array_values($unknown)]);
        $start = (string)($input['start_date']??''); $end=(string)($input['end_date']??'');
        if ($start && $end && (strtotime($end)-strtotime($start)>180*86400 || strtotime($end)<strtotime($start))) throw new BusinessException('VALIDATION_ERROR','日期范围不合法',422);
        $sort=(string)($input['sort_by']??'id'); $direction=strtolower((string)($input['sort_order']??'desc'));
        if(!in_array($sort,self::SORTS,true)||!in_array($direction,['asc','desc'],true)) throw new BusinessException('VALIDATION_ERROR','排序参数不合法',422);
        $q=Db::name('orders')->alias('o')->field('o.*')->distinct(true);
        if(($v=trim((string)($input['keyword']??'')))!=='')$q->whereLike('o.order_no|o.external_order_no','%'.addcslashes($v,'%_').'%');
        foreach(['order_no','status','channel_type','channel_store_id'] as $key)if(($v=$input[$key]??'')!=='')$q->where('o.'.$key,$key==='channel_store_id'?(int)$v:$v);
        if(($v=$input['payment_method']??'')!=='')$q->whereExists(function($sub)use($v){$sub->table('payments p')->whereRaw('p.order_id=o.id')->where('p.channel',$v);});
        if(($v=$input['fulfillment_status']??'')!=='')$q->whereExists(function($sub)use($v){$sub->table('fulfillments f')->whereRaw('f.order_id=o.id')->where('f.status',$v);});
        if($start)$q->where('o.created_at','>=',$start.' 00:00:00'); if($end)$q->where('o.created_at','<=',$end.' 23:59:59');
        ['page' => $page, 'page_size' => $size] = ListPagination::normalize($input);$total=(int)(clone$q)->count('o.id');
        return ListPagination::response($q->order('o.'.$sort,$direction)->page($page,$size)->select()->toArray(),$page,$size,$total);
    }
}
