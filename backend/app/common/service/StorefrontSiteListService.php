<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BusinessException;
use think\facade\Db;

final class StorefrontSiteListService
{
    private const ALLOWED=['page','page_size','keyword','status','locale','currency','sort_by','sort_order'];
    private const SORTS=['id','site_code','name','status','created_at','updated_at'];
    public function list(array $input):array
    {
        $unknown=array_diff(array_keys($input),self::ALLOWED);if($unknown)throw new BusinessException('VALIDATION_ERROR','存在未知查询参数',422,['unknown'=>array_values($unknown)]);
        $sort=(string)($input['sort_by']??'id');$direction=strtolower((string)($input['sort_order']??'desc'));if(!in_array($sort,self::SORTS,true)||!in_array($direction,['asc','desc'],true))throw new BusinessException('VALIDATION_ERROR','排序参数不合法',422);
        $q=Db::name('storefront_sites')->alias('s')->field('s.*');
        if(($v=trim((string)($input['keyword']??'')))!=='')$q->whereLike('s.site_code|s.name|s.brand_name','%'.addcslashes($v,'%_').'%');
        foreach (['status' => 'status', 'locale' => 'default_locale', 'currency' => 'currency'] as $inputKey => $column) {
            if (($value = $input[$inputKey] ?? '') !== '') $q->where('s.' . $column, $value);
        }
        ['page' => $page, 'page_size' => $size] = ListPagination::normalize($input);
        $total=(int)(clone $q)->count('s.id');
        return ListPagination::response($q->order('s.'.$sort,$direction)->page($page,$size)->select()->toArray(),$page,$size,$total);
    }
}
