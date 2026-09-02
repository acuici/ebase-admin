<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use think\facade\Db;
use think\Request;
use think\Response;

/** Read-only, paginated operational views for the admin dashboard. */
final class OperationsController extends ApiController
{
    public function module(Request $request, string $module): Response
    {
        $page = max(1, (int) $request->get('page', 1));
        $size = min(100, max(1, (int) $request->get('page_size', 20)));
        $keyword = trim((string) $request->get('keyword', ''));
        $status = trim((string) $request->get('status', ''));
        $category = trim((string) $request->get('category', ''));
        $source = trim((string) $request->get('source_channel', ''));
        $carrier = trim((string) $request->get('carrier_code', ''));
        $handlers = [
            'products' => fn () => $this->products($keyword, $status, $category, $page, $size),
            'inventory' => fn () => $this->inventory($keyword, $status, $page, $size),
            'customers' => fn () => $this->customers($keyword, $status, $source, $page, $size),
            'logistics' => fn () => $this->logistics($keyword, $status, $carrier, $page, $size),
            'content' => fn () => $this->content($keyword, $page, $size),
            'coupons' => fn () => $this->simple('coupons', $keyword, ['code', 'name', 'status'], $page, $size),
            'campaigns' => fn () => $this->simple('marketing_campaigns', $keyword, ['name', 'campaign_type', 'status'], $page, $size),
        ];
        if (!isset($handlers[$module])) return $this->error('INVALID_MODULE', '不支持的运营模块', 422);
        return $this->success($handlers[$module]());
    }

    public function stats(string $module): Response
    {
        $definitions = [
            'products' => [
                'total' => Db::name('products')->count(),
                'secondary' => Db::name('product_skus')->count(),
                'risk' => Db::query('SELECT COUNT(*) AS total FROM products p WHERE NOT EXISTS (SELECT 1 FROM asset_relations r WHERE r.relation_type = "product" AND r.relation_id = p.id)')[0]['total'],
                'short_description' => Db::query('SELECT COUNT(*) AS total FROM products WHERE CHAR_LENGTH(COALESCE(description, "")) < 30')[0]['total'],
                'unmapped' => Db::query('SELECT COUNT(*) AS total FROM products p WHERE NOT EXISTS (SELECT 1 FROM storefront_product_listings l WHERE l.product_id = p.id)')[0]['total'],
                'optimized' => Db::name('product_quality_reports')->where('checked_at', '>=', date('Y-m-d 00:00:00', strtotime('monday this week')))->count(),
                'title' => '商品资料质量',
            ],
            'inventory' => [
                'total' => Db::name('product_skus')->count(),
                'secondary' => Db::query('SELECT COALESCE(SUM(stock_quantity), 0) AS total FROM product_skus')[0]['total'],
                'risk' => Db::query('SELECT COUNT(*) AS total FROM product_skus WHERE stock_quantity - reserved_quantity <= 20')[0]['total'],
                'out_of_stock' => Db::query('SELECT COUNT(*) AS total FROM product_skus WHERE stock_quantity - reserved_quantity <= 0')[0]['total'],
                'low_stock' => Db::query('SELECT COUNT(*) AS total FROM product_skus WHERE stock_quantity - reserved_quantity BETWEEN 1 AND 20')[0]['total'],
                'title' => '智能补货建议',
            ],
            'customers' => [
                'total' => Db::name('customers')->count(),
                'secondary' => Db::query('SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE customer_id IS NOT NULL AND status <> "cancelled"')[0]['total'],
                'risk' => Db::query('SELECT COUNT(*) AS total FROM customers c WHERE NOT EXISTS (SELECT 1 FROM customer_tag_relations r WHERE r.customer_id = c.id)')[0]['total'],
                'high_value' => Db::query('SELECT COUNT(*) AS total FROM customers c WHERE (SELECT COALESCE(SUM(o.total_amount),0) FROM orders o WHERE o.customer_id=c.id AND o.status <> "cancelled") >= 5000')[0]['total'],
                'title' => '用户画像',
            ],
            'logistics' => [
                'total' => Db::name('shipment_packages')->count(),
                'secondary' => Db::name('shipment_tracking_events')->count(),
                'risk' => Db::name('logistics_exceptions')->whereIn('status', ['open', 'processing'])->count(),
                'title' => '物流异常队列',
            ],
            'campaigns' => [
                'total' => Db::name('marketing_campaigns')->count(),
                'secondary' => Db::name('marketing_campaigns')->whereIn('status', ['active', 'running'])->count(),
                'risk' => Db::name('approval_requests')->where('request_type', 'campaign')->where('status', 'pending')->count(),
                'title' => '营销审批队列',
            ],
            'content' => ['total' => Db::name('storefront_content')->count(), 'secondary' => Db::name('storefront_content')->where('status', 'published')->count(), 'risk' => Db::name('content_review_requests')->where('status', 'pending')->count(), 'title' => '内容发布质量'],
            'coupons' => ['total' => Db::name('coupons')->count(), 'secondary' => Db::name('coupon_claims')->count(), 'risk' => Db::name('coupons')->where('status', 'draft')->count(), 'title' => '优惠券运营'],
        ];

        if (!isset($definitions[$module])) {
            return $this->error('INVALID_MODULE', '该模块没有独立统计接口', 422);
        }

        $item = $definitions[$module];
        return $this->success([
            'metrics' => [
                ['label' => '总量', 'value' => $item['total'], 'note' => '全量数据库数据'],
                ['label' => '业务指标', 'value' => $item['secondary'], 'note' => '独立聚合统计'],
                ['label' => '待处理', 'value' => $item['risk'], 'note' => '风险数据'],
                ['label' => '数据更新时间', 'value' => date('Y-m-d H:i:s'), 'note' => '实时查询'],
            ],
            'panel' => [
                'eyebrow' => 'REALTIME INSIGHT',
                'title' => $item['title'],
                'description' => '独立于列表筛选的全量统计',
                'score_label' => '当前健康度',
                'score' => $item['total'] > 0 ? '实时' : '暂无数据',
                'score_width' => $item['total'] > 0 ? 100 : 0,
                'items' => $this->panelItems($module, $item),
            ],
        ]);
    }

    function panelItems(string $module, array $item): array
    {
        return match ($module) {
            'products' => [
                ['title' => '缺少商品主图', 'meta' => (string) $item['risk'] . ' 个商品', 'tone' => 'danger'],
                ['title' => '卖点描述较短', 'meta' => (string) $item['short_description'] . ' 个商品', 'tone' => 'warning'],
                ['title' => '渠道属性待映射', 'meta' => (string) $item['unmapped'] . ' 个商品', 'tone' => 'warning'],
                ['title' => '本周完成优化', 'meta' => (string) $item['optimized'] . ' 个商品', 'tone' => 'success'],
            ],
            'inventory' => [
                ['title' => '缺货 SKU', 'meta' => (string) $item['out_of_stock'] . ' 个', 'tone' => 'danger'],
                ['title' => '低库存 SKU', 'meta' => (string) $item['low_stock'] . ' 个', 'tone' => 'warning'],
                ['title' => '库存总量', 'meta' => (string) $item['secondary'] . ' 件现货', 'tone' => 'primary'],
                ['title' => '补货依据', 'meta' => '销量、库存与周转天数', 'tone' => 'success'],
            ],
            'customers' => [
                ['title' => '未打标签用户', 'meta' => (string) $item['risk'] . ' 个需要画像完善', 'tone' => 'warning'],
                ['title' => '高价值用户', 'meta' => (string) $item['high_value'] . ' 个', 'tone' => 'primary'],
                ['title' => '累计消费', 'meta' => '全量有效订单统计', 'tone' => 'primary'],
                ['title' => '画像来源', 'meta' => '订单、标签、触达记录', 'tone' => 'success'],
            ],
            'logistics' => [
                ['title' => '物流异常待处理', 'meta' => (string) $item['risk'] . ' 个包裹', 'tone' => 'danger'],
                ['title' => '物流轨迹', 'meta' => (string) $item['secondary'] . ' 条事件', 'tone' => 'primary'],
                ['title' => '异常通知', 'meta' => '异常创建后发送通知中心', 'tone' => 'success'],
            ],
            default => [],
        };
    }


    public function dashboard(): Response
    {
        return $this->success([
            'products' => Db::name('products')->where('status', 'active')->count(),
            'skus' => Db::name('product_skus')->where('status', 'active')->count(),
            'customers' => Db::name('customers')->where('status', 'active')->count(),
            'orders' => Db::name('orders')->count(),
            'today_revenue' => Db::name('orders')->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->whereTime('created_at', 'today')->sum('total_amount'),
            'paid_orders' => Db::name('orders')->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->whereTime('created_at', 'today')->count(),
            'average_order_value' => Db::name('orders')->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->whereTime('created_at', 'today')->avg('total_amount'),
            'refund_rate' => Db::name('refunds')->whereIn('status', ['succeeded', 'processing'])->whereTime('created_at', 'today')->count() / max(1, Db::name('orders')->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->whereTime('created_at', 'today')->count()) * 100,
            'pending_shipment_orders' => Db::name('orders')->whereIn('status', ['paid', 'processing'])->count(),
            'pending_orders' => Db::name('orders')->whereIn('status', ['pending_payment', 'paid', 'processing'])->count(),
            'low_stock_skus' => Db::query('SELECT COUNT(*) AS total FROM product_skus WHERE status = "active" AND stock_quantity - reserved_quantity <= 20')[0]['total'],
            'open_logistics_exceptions' => Db::name('logistics_exceptions')->whereIn('status', ['open', 'processing'])->count(),
            'unread_notifications' => Db::name('notifications')->where('member_id', $this->requireMember()->id)->whereNull('read_at')->count(),
        ]);
    }

    private function products(string $keyword, string $status, string $category, int $page, int $size): array
    {
        $query = Db::name('products')->alias('p')->field("p.id,p.product_no,p.name,p.brand,p.category,p.status,p.created_at,COALESCE((SELECT MIN(s1.price) FROM product_skus s1 WHERE s1.product_id=p.id),0) AS min_price,COALESCE((SELECT MAX(s2.price) FROM product_skus s2 WHERE s2.product_id=p.id),0) AS max_price,(SELECT COUNT(*) FROM product_skus s3 WHERE s3.product_id=p.id) AS sku_count,COALESCE((SELECT SUM(s4.stock_quantity-s4.reserved_quantity) FROM product_skus s4 WHERE s4.product_id=p.id),0) AS available_stock,(SELECT COUNT(*) FROM storefront_product_listings l WHERE l.product_id=p.id AND l.status IN ('published','scheduled')) AS published_channels");
        if ($keyword) $query->whereLike('p.name|p.product_no|p.brand', '%' . addcslashes($keyword, '%_') . '%');
        if ($status) $query->where('p.status', $status);
        if ($category) $query->where('p.category', $category);
        return $this->result($query, $page, $size);
    }

    private function inventory(string $keyword, string $status, int $page, int $size): array
    {
        $query = Db::name('product_skus')->alias('s')->join('products p', 'p.id = s.product_id')->field('s.id,s.sku_code,s.name AS sku_name,p.name AS product_name,s.stock_quantity,s.reserved_quantity,(s.stock_quantity-s.reserved_quantity) AS available_stock,s.price,s.status');
        if ($keyword) $query->whereLike('s.sku_code|s.name|p.name', '%' . addcslashes($keyword, '%_') . '%');
        if ($status === 'out_of_stock') $query->whereRaw('s.stock_quantity - s.reserved_quantity <= 0');
        if ($status === 'low_stock') $query->whereRaw('s.stock_quantity - s.reserved_quantity > 0 AND s.stock_quantity - s.reserved_quantity <= 20');
        if ($status === 'active') $query->where('s.status', 'active');
        return $this->result($query, $page, $size);
    }

    private function customers(string $keyword, string $status, string $source, int $page, int $size): array
    {
        $query = Db::name('customers')->alias('c')->field("c.id,c.customer_no,c.name,c.email,c.phone,c.status,c.source_channel,c.created_at,(SELECT COUNT(*) FROM orders o WHERE o.customer_id=c.id) AS order_count,COALESCE((SELECT SUM(o2.total_amount) FROM orders o2 WHERE o2.customer_id=c.id AND o2.status NOT IN ('cancelled')),0) AS total_spend,(SELECT MAX(o3.created_at) FROM orders o3 WHERE o3.customer_id=c.id AND o3.status NOT IN ('cancelled')) AS last_order_at,(SELECT GROUP_CONCAT(t.name ORDER BY t.id SEPARATOR '、') FROM customer_tag_relations r JOIN customer_tags t ON t.id=r.tag_id WHERE r.customer_id=c.id) AS tags");
        if ($keyword) $query->whereLike('customer_no|name|email|phone', '%' . addcslashes($keyword, '%_') . '%');
        if ($status) $query->where('c.status', $status);
        if ($source) $query->where('c.source_channel', $source);
        return $this->result($query, $page, $size);
    }

    private function logistics(string $keyword, string $status, string $carrier, int $page, int $size): array
    {
        $query = Db::name('shipment_packages')->alias('p')->join('fulfillments f', 'f.id = p.fulfillment_id')->leftJoin('logistics_exceptions e', 'e.package_id = p.id AND e.status IN ("open","processing")')->field('p.id,p.package_no,p.carrier_code,p.tracking_no,p.status,f.order_id,e.exception_type,e.severity,e.description');
        if ($keyword) $query->whereLike('p.package_no|p.tracking_no|p.carrier_code', '%' . addcslashes($keyword, '%_') . '%');
        if ($status) $query->where('p.status', $status);
        if ($carrier) $query->where('p.carrier_code', $carrier);
        return $this->result($query, $page, $size);
    }

    private function content(string $keyword, int $page, int $size): array
    {
        $query = Db::name('storefront_content')->field('id,site_id,content_type,content_key,title,slug,status,published_at,updated_at');
        if ($keyword) $query->whereLike('title|content_key|slug', '%' . addcslashes($keyword, '%_') . '%');
        return $this->result($query, $page, $size);
    }

    private function simple(string $table, string $keyword, array $columns, int $page, int $size): array
    {
        $query = Db::name($table);
        if ($keyword) $query->whereLike(implode('|', $columns), '%' . addcslashes($keyword, '%_') . '%');
        return $this->result($query, $page, $size);
    }

    private function result($query, int $page, int $size): array
    {
        $total = (clone $query)->count();
        $items = $query->order('id', 'desc')->page($page, $size)->select()->toArray();
        return ['items' => $items, 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total, 'pages' => max(1, (int) ceil($total / $size))]];
    }
}
