<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\StorefrontSite;
use app\validate\StorefrontContentValidate;
use think\facade\Db;
use think\Request;
use think\Response;

/** Site themes, navigation, pages, policies, campaigns and SEO redirect rules. */
final class StorefrontContentController extends ApiController
{
    public function index(int $siteId, Request $request): Response
    {
        $this->requireSite($siteId);
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('page_size', 20)));
        $query = Db::name('storefront_content')->where('site_id', $siteId);

        if ($type = $request->get('type')) {
            $query->where('content_type', $type);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($keyword = trim((string) $request->get('keyword', ''))) {
            $query->whereLike('title|content_key|slug', '%' . addcslashes($keyword, '%_') . '%');
        }

        $total = $query->count();
        $items = $query->order('id', 'desc')->page($page, $pageSize)->select();

        return $this->paginated($items, $page, $pageSize, $total);
    }

    public function read(int $siteId, int $id): Response
    {
        $this->requireSite($siteId);
        $content = Db::name('storefront_content')->where('site_id', $siteId)->where('id', $id)->find();
        if (!$content) {
            throw BusinessException::notFound('独立站内容不存在');
        }
        return $this->success($content);
    }

    /** Upsert by a site-scoped content type + business key. */
    public function upsert(Request $request, int $siteId): Response
    {
        $this->requireSite($siteId);
        $this->validate($request->post(), StorefrontContentValidate::class);

        $input = $request->post();
        $data = [
            'content_type' => $input['content_type'],
            'content_key' => $input['content_key'],
            'title' => $input['title'],
            'slug' => $input['slug'] ?? null,
            'status' => $input['status'],
            'payload' => json_encode($input['payload'], JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($data['status'] === 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        $existing = Db::name('storefront_content')
            ->where('site_id', $siteId)
            ->where('content_type', $data['content_type'])
            ->where('content_key', $data['content_key'])
            ->find();

        if ($existing) {
            Db::name('storefront_content')->where('id', $existing['id'])->update($data);
            $id = $existing['id'];
        } else {
            $id = Db::name('storefront_content')->insertGetId([
                ...$data,
                'site_id' => $siteId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success(
            Db::name('storefront_content')->where('id', $id)->find(),
            '独立站内容已保存',
        );
    }

    public function publish(int $siteId, int $id): Response
    {
        $this->requireSite($siteId);
        $affected = Db::name('storefront_content')
            ->where('site_id', $siteId)
            ->where('id', $id)
            ->update([
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($affected === 0) {
            throw BusinessException::notFound('独立站内容不存在');
        }
        return $this->success(null, '独立站内容已发布');
    }

    public function delete(int $siteId, int $id): Response
    {
        $this->requireSite($siteId);
        $affected = Db::name('storefront_content')->where('site_id', $siteId)->where('id', $id)->delete();
        if ($affected === 0) {
            throw BusinessException::notFound('独立站内容不存在');
        }
        return $this->success(null, '独立站内容已删除');
    }

    private function requireSite(int $siteId): void
    {
        if (!StorefrontSite::find($siteId)) {
            throw BusinessException::notFound('站点不存在');
        }
    }
}
