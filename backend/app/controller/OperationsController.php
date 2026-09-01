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
        $handlers = [
            'products' => fn () => $this->products($keyword, $page, $size),
            'inventory' => fn () => $this->inventory($keyword, $page, $size),
            'customers' => fn () => $this->customers($keyword, $page, $size),
            'logistics' => fn () => $this->logistics($keyword, $page, $size),
            'content' => fn () => $this->content($keyword, $page, $size),
            'coupons' => fn () => $this->simple('coupons', $keyword, ['code', 'name', 'status'], $page, $size),
            'campaigns' => fn () => $this->simple('marketing_campaigns', $keyword, ['name', 'campaign_type', 'status'], $page, $size),
        ];
        if (!isset($handlers[$module])) return $this->error('INVALID_MODULE', '不支持的运营模块', 422);
        return $this->success($handlers[$module]());
    }

    public function dashboard(): Response
    {
        return $this->success([
            'products' => Db::name('products')->where('status', 'active')->count(),
            'skus' => Db::name('product_skus')->where('status', 'active')->count(),
            'customers' => Db::name('customers')->where('status', 'active')->count(),
            'orders' => Db::name('orders')->count(),
            'pending_orders' => Db::name('orders')->whereIn('status', ['pending_payment', 'paid', 'processing'])->count(),
            'low_stock_skus' => Db::query('SELECT COUNT(*) AS total FROM product_skus WHERE status = "active" AND stock_quantity - reserved_quantity <= 20')[0]['total'],
            'open_logistics_exceptions' => Db::name('logistics_exceptions')->whereIn('status', ['open', 'processing'])->count(),
            'unread_notifications' => Db::name('notifications')->where('member_id', $this->requireMember()->id)->whereNull('read_at')->count(),
        ]);
    }

    private function products(string $keyword, int $page, int $size): array
    {
        $query = Db::name('products')->alias('p')->field("p.id,p.product_no,p.name,p.brand,p.category,p.status,p.created_at,COALESCE((SELECT MIN(s1.price) FROM product_skus s1 WHERE s1.product_id=p.id),0) AS min_price,COALESCE((SELECT MAX(s2.price) FROM product_skus s2 WHERE s2.product_id=p.id),0) AS max_price,(SELECT COUNT(*) FROM product_skus s3 WHERE s3.product_id=p.id) AS sku_count,COALESCE((SELECT SUM(s4.stock_quantity-s4.reserved_quantity) FROM product_skus s4 WHERE s4.product_id=p.id),0) AS available_stock,(SELECT COUNT(*) FROM storefront_product_listings l WHERE l.product_id=p.id AND l.status IN ('published','scheduled')) AS published_channels");
        if ($keyword) $query->whereLike('p.name|p.product_no|p.brand', '%' . addcslashes($keyword, '%_') . '%');
        return $this->result($query, $page, $size);
    }

    private function inventory(string $keyword, int $page, int $size): array
    {
        $query = Db::name('product_skus')->alias('s')->join('products p', 'p.id = s.product_id')->field('s.id,s.sku_code,s.name AS sku_name,p.name AS product_name,s.stock_quantity,s.reserved_quantity,(s.stock_quantity-s.reserved_quantity) AS available_stock,s.price,s.status');
        if ($keyword) $query->whereLike('s.sku_code|s.name|p.name', '%' . addcslashes($keyword, '%_') . '%');
        return $this->result($query, $page, $size);
    }

    private function customers(string $keyword, int $page, int $size): array
    {
        $query = Db::name('customers')->alias('c')->field("c.id,c.customer_no,c.name,c.email,c.phone,c.status,c.source_channel,c.created_at,(SELECT COUNT(*) FROM orders o WHERE o.customer_id=c.id) AS order_count,COALESCE((SELECT SUM(o2.total_amount) FROM orders o2 WHERE o2.customer_id=c.id AND o2.status NOT IN ('cancelled')),0) AS total_spend,(SELECT MAX(o3.created_at) FROM orders o3 WHERE o3.customer_id=c.id AND o3.status NOT IN ('cancelled')) AS last_order_at,(SELECT GROUP_CONCAT(t.name ORDER BY t.id SEPARATOR '、') FROM customer_tag_relations r JOIN customer_tags t ON t.id=r.tag_id WHERE r.customer_id=c.id) AS tags");
        if ($keyword) $query->whereLike('customer_no|name|email|phone', '%' . addcslashes($keyword, '%_') . '%');
        return $this->result($query, $page, $size);
    }

    private function logistics(string $keyword, int $page, int $size): array
    {
        $query = Db::name('shipment_packages')->alias('p')->join('fulfillments f', 'f.id = p.fulfillment_id')->leftJoin('logistics_exceptions e', 'e.package_id = p.id AND e.status IN ("open","processing")')->field('p.id,p.package_no,p.carrier_code,p.tracking_no,p.status,f.order_id,e.exception_type,e.severity,e.description');
        if ($keyword) $query->whereLike('p.package_no|p.tracking_no|p.carrier_code', '%' . addcslashes($keyword, '%_') . '%');
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
