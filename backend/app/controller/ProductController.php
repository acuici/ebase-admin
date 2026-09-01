<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\Product;
use think\Request;
use think\Response;
class ProductController extends ApiController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('page_size', 20)));
        $query = Product::with('skus');
        if ($keyword = trim((string) $request->get('keyword', ''))) {
            $query->whereLike('name|product_no|brand', '%' . addcslashes($keyword, '%_') . '%');
        }
        if ($status = $request->get('status')) $query->where('status', $status);
        $total = $query->count();
        $items = $query->order('id', 'desc')->page($page, $pageSize)->select();
        return $this->paginated($items, $page, $pageSize, $total);
    }
    public function read(int $id): Response
    {
        $product = Product::with('skus')->find($id);
        if (!$product) throw BusinessException::notFound('商品不存在');
        return $this->success($product);
    }
    public function create(Request $request): Response
    {
        $data = $this->validate($request->post(), ProductValidate::class);
        $product = Product::create($data);
        return $this->success($product, '商品创建成功', 201);
    }
    public function update(Request $request, int $id): Response
    {
        $product = Product::find($id);
        if (!$product) throw BusinessException::notFound('商品不存在');
        $data = $request->only(['name', 'brand', 'description', 'status']);
        $this->validate(array_merge(['product_no' => $product->product_no], $data), ProductValidate::class);
        $product->save($data);
        return $this->success($product, '商品更新成功');
    }
    public function delete(int $id): Response
    {
        $product = Product::find($id);
        if (!$product) throw BusinessException::notFound('商品不存在');
        $product->status = 'archived';
        $product->save();
        return $this->success(null, '商品已归档');
    }
}
