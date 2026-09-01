<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use think\facade\Db;
use think\Request;
use think\Response;

final class CustomerController extends ApiController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->get('page', 1));
        $size = min(100, max(1, (int) $request->get('page_size', 20)));
        $query = Db::name('customers');
        if ($keyword = trim((string) $request->get('keyword', ''))) {
            $query->whereLike('customer_no|email|phone|name', '%' . addcslashes($keyword, '%_') . '%');
        }
        $total = $query->count();
        return $this->paginated($query->order('id', 'desc')->page($page, $size)->select(), $page, $size, $total);
    }

    public function read(int $id): Response
    {
        $customer = Db::name('customers')->where('id', $id)->find();
        if (!$customer) throw BusinessException::notFound('消费者不存在');
        $customer['addresses'] = Db::name('customer_addresses')->where('customer_id', $id)->order('is_default', 'desc')->select();
        $customer['tags'] = Db::name('customer_tag_relations')->alias('r')->join('customer_tags t', 't.id=r.tag_id')->where('r.customer_id', $id)->column('t.name');
        return $this->success($customer);
    }

    public function create(Request $request): Response
    {
        $this->validate($request->post(), ['email' => 'email|max:190', 'phone' => 'max:32', 'name' => 'max:120']);
        $data = $request->post();
        $id = Db::name('customers')->insertGetId(['customer_no' => 'CU' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3))), 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null, 'name' => $data['name'] ?? null, 'source_channel' => $data['source_channel'] ?? 'admin', 'status' => 'active', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        return $this->success(Db::name('customers')->where('id', $id)->find(), '消费者创建成功', 201);
    }

    public function update(Request $request, int $id): Response
    {
        if (!Db::name('customers')->where('id', $id)->find()) throw BusinessException::notFound('消费者不存在');
        $this->validate($request->post(), ['email' => 'email|max:190', 'phone' => 'max:32', 'name' => 'max:120', 'status' => 'in:active,disabled']);
        Db::name('customers')->where('id', $id)->update([...$request->only(['email', 'phone', 'name', 'status']), 'updated_at' => date('Y-m-d H:i:s')]);
        return $this->success(Db::name('customers')->where('id', $id)->find(), '消费者资料已更新');
    }

    public function addresses(int $id): Response
    {
        if (!Db::name('customers')->where('id', $id)->find()) throw BusinessException::notFound('消费者不存在');
        return $this->success(Db::name('customer_addresses')->where('customer_id', $id)->order('is_default', 'desc')->select());
    }
}
