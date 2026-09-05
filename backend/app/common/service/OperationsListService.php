<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BusinessException;
use think\facade\Db;

final class OperationsListService
{
    private const MODULES = ['products', 'inventory', 'customers', 'logistics', 'content', 'coupons', 'campaigns'];
    private const COMMON = ['page', 'page_size', 'keyword', 'status', 'sort_by', 'sort_order'];
    private const FILTERS = [
        'products' => ['category', 'brand', 'sales_status', 'publish_channel'],
        'inventory' => ['warehouse', 'inventory_status', 'supplier', 'turnover_days'],
        'customers' => ['member_tier', 'source_channel', 'customer_tag', 'spend_range'],
        'logistics' => ['warehouse', 'carrier_code', 'delivery_status', 'exception_type'],
        'content' => ['content_type', 'publish_channel', 'content_status', 'published_at'],
        'coupons' => ['coupon_type', 'applicable_channel', 'audience', 'validity'],
        'campaigns' => ['campaign_type', 'publish_channel', 'owner', 'start_date', 'end_date', 'campaign_date'],
    ];
    private const SORTS = [
        'products' => ['id', 'product_no', 'name', 'created_at', 'updated_at'],
        'inventory' => ['id', 'sku_code', 'stock_quantity', 'reserved_quantity', 'updated_at'],
        'customers' => ['id', 'customer_no', 'created_at', 'updated_at'],
        'logistics' => ['id', 'package_no', 'created_at', 'updated_at'],
        'content' => ['id', 'title', 'published_at', 'updated_at'],
        'coupons' => ['id', 'code', 'starts_at', 'ends_at', 'created_at'],
        'campaigns' => ['id', 'name', 'starts_at', 'ends_at', 'created_at'],
    ];

    public function list(string $module, array $input): array
    {
        if (!in_array($module, self::MODULES, true)) throw new BusinessException('INVALID_MODULE', '不支持的运营模块', 422);
        $this->assertKeys($module, $input);
        ['page' => $page, 'page_size' => $size] = ListPagination::normalize($input);
        $sort = (string) ($input['sort_by'] ?? 'id');
        if (!in_array($sort, self::SORTS[$module], true)) throw new BusinessException('VALIDATION_ERROR', '排序字段不合法', 422, ['sort_by' => ['排序字段不合法']]);
        $direction = strtolower((string) ($input['sort_order'] ?? 'desc'));
        if (!in_array($direction, ['asc', 'desc'], true)) throw new BusinessException('VALIDATION_ERROR', '排序方向不合法', 422, ['sort_order' => ['排序方向不合法']]);
        $query = $this->build($module, $input);
        $total = (int) (clone $query)->count();
        $items = $query->order($sort, $direction)->page($page, $size)->select()->toArray();
        return ListPagination::response($items, $page, $size, $total);
    }

    private function assertKeys(string $module, array $input): void
    {
        $allowed = array_merge(self::COMMON, self::FILTERS[$module], $module === 'campaigns' ? [] : []);
        $unknown = array_diff(array_keys($input), $allowed);
        if ($unknown) throw new BusinessException('VALIDATION_ERROR', '存在未知查询参数', 422, ['unknown' => array_values($unknown)]);
        if (isset($input['start_date'], $input['end_date']) && strtotime((string) $input['end_date']) - strtotime((string) $input['start_date']) > 180 * 86400) throw new BusinessException('VALIDATION_ERROR', '日期范围不能超过 180 天', 422);
    }

    private function build(string $module, array $input)
    {
        $query = match ($module) {
            'products' => Db::name('products')->alias('p')->field('p.*'),
            'inventory' => Db::name('product_skus')->alias('s')->join('products p', 'p.id=s.product_id')->field('s.*,p.name AS product_name,COALESCE((SELECT SUM(oi.quantity) FROM order_items oi JOIN orders so ON so.id=oi.order_id WHERE oi.sku_id=s.id AND so.status IN (\'paid\',\'processing\',\'shipped\',\'completed\') AND so.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)),0) AS sold_30d'),
            'customers' => Db::name('customers')->alias('c')->field('c.*'),
            'logistics' => Db::name('shipment_packages')->alias('p')->join('fulfillments f', 'f.id=p.fulfillment_id')->field('p.*,f.order_id,f.warehouse_code'),
            'content' => Db::name('storefront_content')->alias('c')->field('c.*'),
            'coupons' => Db::name('coupons')->alias('c')->field('c.*'),
            'campaigns' => Db::name('marketing_campaigns')->alias('c')->field('c.*'),
        };
        $keyword = trim((string) ($input['keyword'] ?? ''));
        if ($keyword) {
            $columns = ['products' => 'p.name|p.product_no|p.brand', 'inventory' => 's.sku_code|s.name|p.name', 'customers' => 'c.customer_no|c.name|c.email|c.phone', 'logistics' => 'p.package_no|p.tracking_no|p.carrier_code', 'content' => 'c.title|c.content_key|c.slug', 'coupons' => 'c.code|c.name', 'campaigns' => 'c.name'];
            $query->whereLike($columns[$module], '%' . addcslashes($keyword, '%_') . '%');
        }
        if (($input['status'] ?? '') !== '') {
            $statusColumn = match ($module) {
                'products' => 'p.status',
                'inventory' => 's.status',
                'customers' => 'c.status',
                'logistics' => 'p.status',
                'content' => 'c.status',
                'coupons' => 'c.status',
                'campaigns' => 'c.status',
            };
            $query->where($statusColumn, $input['status']);
        }
        if ($module === 'products') {
            foreach (['category', 'brand'] as $field) if (($input[$field] ?? '') !== '') $query->where('p.' . $field, $input[$field]);
            if (($input['sales_status'] ?? '') !== '') {
                $salesStatus = ['active' => 'active', 'draft' => 'draft', 'archived' => 'archived'][$input['sales_status']] ?? null;
                if ($salesStatus === null) throw new BusinessException('VALIDATION_ERROR', '销售状态不合法', 422);
                $query->where('p.status', $salesStatus);
            }
            if (($input['publish_channel'] ?? '') !== '') {
                $channel = $input['publish_channel'];
                $query->where(function ($q) use ($channel) {
                    $q->whereExists(function ($sub) use ($channel) { $sub->table('storefront_product_listings l')->join('storefront_sites ss', 'ss.id=l.site_id')->whereRaw('l.product_id=p.id')->where('l.status', 'published')->where('ss.site_code', $channel); })
                        ->whereOrExists(function ($sub) use ($channel) { $sub->table('channel_products cp')->join('channel_stores cs', 'cs.id=cp.channel_store_id')->whereRaw('cp.product_id=p.id')->where('cp.listing_status', 'published')->where('cs.channel_type', $channel); });
                });
            }
        }
        if ($module === 'inventory') {
            if (($input['inventory_status'] ?? '') === 'out_of_stock') $query->whereRaw('s.stock_quantity - s.reserved_quantity <= 0');
            if (($input['inventory_status'] ?? '') === 'low_stock') $query->whereRaw('s.stock_quantity - s.reserved_quantity BETWEEN 1 AND 20');
            if (($input['warehouse'] ?? '') !== '') $query->where('s.warehouse_code', $input['warehouse']);
            if (($input['supplier'] ?? '') !== '') $query->whereExists(function ($sub) use ($input) { $sub->table('suppliers sp')->whereRaw('sp.id=s.supplier_id')->where('sp.supplier_code', $input['supplier']); });
            if (($input['turnover_days'] ?? '') !== '') {
                $days = $input['turnover_days'];
                $condition = match ($days) {
                    'lte_7' => '<= 7',
                    '8_to_30' => '> 7 AND <= 30',
                    'gt_30' => '> 30',
                    default => throw new BusinessException('VALIDATION_ERROR', '周转天数不合法', 422),
                };
                $sales = "(COALESCE((SELECT SUM(oi.quantity) FROM order_items oi JOIN orders so ON so.id=oi.order_id WHERE oi.sku_id=s.id AND so.status IN ('paid','processing','shipped','completed') AND so.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)),0) / 30)";
                $query->whereRaw(match ($days) {
                    'lte_7' => "CASE WHEN {$sales} <= 0 THEN 999999 ELSE (s.stock_quantity - s.reserved_quantity) / {$sales} END <= 7",
                    '8_to_30' => "CASE WHEN {$sales} <= 0 THEN 999999 ELSE (s.stock_quantity - s.reserved_quantity) / {$sales} END > 7 AND CASE WHEN {$sales} <= 0 THEN 999999 ELSE (s.stock_quantity - s.reserved_quantity) / {$sales} END <= 30",
                    'gt_30' => "CASE WHEN {$sales} <= 0 THEN 999999 ELSE (s.stock_quantity - s.reserved_quantity) / {$sales} END > 30",
                });
            }
        }
        if ($module === 'customers') { if (($input['source_channel'] ?? '') !== '') $query->where('c.source_channel', $input['source_channel']); if (($input['customer_tag'] ?? '') !== '') $query->whereExists(function ($q) use ($input) { $q->table('customer_tag_relations r')->join('customer_tags t', 't.id=r.tag_id')->whereRaw('r.customer_id=c.id')->where('t.name', $input['customer_tag']); }); }
        if ($module === 'logistics') { foreach (['warehouse' => 'f.warehouse_code', 'carrier_code' => 'p.carrier_code'] as $key => $column) if (($input[$key] ?? '') !== '') $query->where($column, $input[$key]); if (($input['delivery_status'] ?? '') !== '') { $delivery = ['on_time' => 'delivered', 'at_risk' => 'processing', 'overdue' => 'pending'][$input['delivery_status']] ?? null; if ($delivery === null) throw new BusinessException('VALIDATION_ERROR', '配送状态不合法', 422); $query->where('p.status', $delivery); } if (($input['exception_type'] ?? '') !== '') $query->whereExists(function ($q) use ($input) { $q->table('logistics_exceptions e')->whereRaw('e.package_id=p.id')->where('e.exception_type', $input['exception_type']); }); }
        if ($module === 'content') { foreach (['content_type' => 'c.content_type', 'content_status' => 'c.status'] as $key => $column) if (($input[$key] ?? '') !== '') $query->where($column, $input[$key]); if (($input['published_at'] ?? '') !== '') { $date = match ($input['published_at']) { 'today' => date('Y-m-d'), 'yesterday' => date('Y-m-d', strtotime('-1 day')), default => $input['published_at'] }; $query->whereDate('c.published_at', $date); } }
        if ($module === 'coupons') {
            foreach (['coupon_type' => 'c.discount_type', 'applicable_channel' => 'c.applicable_channel', 'audience' => 'c.audience'] as $key => $column) if (($input[$key] ?? '') !== '') $query->where($column, $input[$key]);
            if (($input['validity'] ?? '') !== '') {
                $validity = $input['validity'];
                if ($validity === 'active') $query->where('c.status', 'active')->where(function ($q) { $q->whereNull('c.starts_at')->whereOr('c.starts_at', '<=', date('Y-m-d H:i:s')); })->where(function ($q) { $q->whereNull('c.ends_at')->whereOr('c.ends_at', '>=', date('Y-m-d H:i:s')); });
                elseif ($validity === 'expiring') $query->where('c.status', 'active')->whereNotNull('c.ends_at')->whereBetween('c.ends_at', [date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+7 days'))]);
                elseif ($validity === 'ended') $query->where(function ($q) { $q->where('c.status', 'disabled')->whereOr('c.ends_at', '<', date('Y-m-d H:i:s')); });
                else throw new BusinessException('VALIDATION_ERROR', '优惠券有效期不合法', 422);
            }
        }
        if ($module === 'campaigns') { if (($input['campaign_type'] ?? '') !== '') $query->where('c.campaign_type', $input['campaign_type']); if (($input['owner'] ?? '') !== '') $query->whereExists(function ($sub) use ($input) { $sub->table('members m')->whereRaw('m.id=c.created_by')->where('m.member_code',$input['owner']); }); if (($input['campaign_date'] ?? '') !== '') { $date = match ($input['campaign_date']) { 'today' => date('Y-m-d'), 'yesterday' => date('Y-m-d', strtotime('-1 day')), default => $input['campaign_date'] }; $query->whereDate('c.starts_at', $date); } foreach (['start_date' => 'c.starts_at', 'end_date' => 'c.ends_at'] as $key => $column) if (($input[$key] ?? '') !== '') $query->whereDate($column, $input[$key]); }
        return $query;
    }
}
