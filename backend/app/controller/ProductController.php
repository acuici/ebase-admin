<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\Product;
use app\validate\ProductValidate;
use think\facade\Db;
use think\Request;
use think\Response;

final class ProductController extends ApiController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('page_size', 20)));
        $query = Product::with('skus');

        if ($keyword = trim((string) $request->get('keyword', ''))) {
            $query->whereLike('name|product_no|brand', '%' . addcslashes($keyword, '%_') . '%');
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $items = $query->order('id', 'desc')->page($page, $pageSize)->select()->toArray();
        foreach ($items as &$item) {
            $item['asset_count'] = Db::name('asset_relations')
                ->where('relation_type', 'product')
                ->where('relation_id', $item['id'])
                ->count();
        }

        return $this->paginated($items, $page, $pageSize, $total);
    }

    public function read(int $id): Response
    {
        $product = Product::with('skus')->find($id);
        if (!$product) {
            throw BusinessException::notFound('商品不存在');
        }

        $data = $product->toArray();
        $data['assets'] = Db::name('asset_relations')
            ->alias('relation')
            ->join('assets asset', 'asset.id = relation.asset_id')
            ->where('relation.relation_type', 'product')
            ->where('relation.relation_id', $id)
            ->field('relation.purpose,relation.sort_order,asset.id,asset.original_name,asset.mime_type,asset.size_bytes')
            ->order('relation.sort_order', 'asc')
            ->select();
        $data['storefront_listings'] = Db::name('storefront_product_listings')
            ->alias('listing')
            ->join('storefront_sites site', 'site.id = listing.site_id')
            ->where('listing.product_id', $id)
            ->field('listing.*,site.name as site_name,site.site_code')
            ->select();

        return $this->success($data);
    }

    public function create(Request $request): Response
    {
        $this->validate($request->post(), ProductValidate::class);
        $data = $request->only(['product_no', 'name', 'brand', 'description', 'status']);
        $product = Product::create($data);
        return $this->success($product, '商品创建成功', 201);
    }

    public function update(Request $request, int $id): Response
    {
        $product = Product::find($id);
        if (!$product) {
            throw BusinessException::notFound('商品不存在');
        }

        $data = $request->only(['name', 'brand', 'description', 'status']);
        $this->validate([...$product->toArray(), ...$data], ProductValidate::class);
        $product->save($data);

        return $this->success($product, '商品更新成功');
    }

    /** Archive rather than hard-delete to preserve orders and channel history. */
    public function delete(int $id): Response
    {
        $product = Product::find($id);
        if (!$product) {
            throw BusinessException::notFound('商品不存在');
        }

        $product->save(['status' => 'archived']);
        Db::name('storefront_product_listings')
            ->where('product_id', $id)
            ->whereIn('status', ['published', 'scheduled'])
            ->update(['status' => 'archived', 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->success(null, '商品已归档，所有独立站发布已下架');
    }
}
