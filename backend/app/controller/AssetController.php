<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\service\storage\LocalAssetStorageService;
use think\facade\Db;
use think\Request;
use think\Response;

final class AssetController extends ApiController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('page_size', 20)));
        $query = Db::name('assets');

        if ($mime = $request->get('mime_type')) {
            $query->where('mime_type', $mime);
        }

        $total = $query->count();
        $items = $query->order('id', 'desc')->page($page, $pageSize)->select()->toArray();
        foreach ($items as &$asset) {
            $asset['download_url'] = '/api/v1/assets/' . $asset['id'] . '/download';
        }

        return $this->paginated($items, $page, $pageSize, $total);
    }

    public function upload(Request $request): Response
    {
        $file = $request->file('file');
        if (!$file) {
            throw BusinessException::validationError(['file' => ['请选择要上传的文件']]);
        }

        $assetData = (new LocalAssetStorageService())->store($file);
        $assetData['visibility'] = $request->post('visibility', 'private');
        if (!in_array($assetData['visibility'], ['private', 'public'], true)) {
            throw BusinessException::validationError(['visibility' => ['可见性必须是 private 或 public']]);
        }
        $assetData['uploaded_by'] = (int) $this->requireMember()->id;
        $assetData['created_at'] = date('Y-m-d H:i:s');
        $assetData['updated_at'] = date('Y-m-d H:i:s');

        $assetId = Db::name('assets')->insertGetId($assetData);
        return $this->success($this->assetResponse($assetId), '素材上传成功', 201);
    }

    public function attach(Request $request, int $id): Response
    {
        if (!Db::name('assets')->where('id', $id)->find()) {
            throw BusinessException::notFound('素材不存在');
        }

        $this->validate($request->post(), [
            'relation_type' => 'require|in:product,product_listing,content',
            'relation_id' => 'require|integer|gt:0',
            'purpose' => 'max:64',
            'sort_order' => 'integer|egt:0',
        ]);

        $data = $request->post();
        Db::name('asset_relations')->insert([
            'asset_id' => $id,
            'relation_type' => $data['relation_type'],
            'relation_id' => (int) $data['relation_id'],
            'purpose' => $data['purpose'] ?? 'default',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        return $this->success(null, '素材关联成功');
    }

    public function download(int $id): Response
    {
        $asset = Db::name('assets')->where('id', $id)->find();
        if (!$asset) {
            throw BusinessException::notFound('素材不存在');
        }

        // Public assets may be downloaded without a future storefront token;
        // admin API requires current authentication in all cases.
        if ($asset['storage_driver'] !== 'local') {
            throw new BusinessException('UPSTREAM_UNAVAILABLE', '当前存储驱动下载尚未配置', 503);
        }

        $path = (new LocalAssetStorageService())->absolutePath($asset['storage_path']);
        return download($path, $asset['original_name'], true)
            ->header(['Content-Type' => $asset['mime_type']]);
    }

    public function delete(int $id): Response
    {
        $asset = Db::name('assets')->where('id', $id)->find();
        if (!$asset) {
            throw BusinessException::notFound('素材不存在');
        }

        $relationCount = Db::name('asset_relations')->where('asset_id', $id)->count();
        if ($relationCount > 0) {
            throw new BusinessException('RESOURCE_CONFLICT', '素材仍被业务内容引用，不能删除', 409);
        }

        if ($asset['storage_driver'] === 'local') {
            (new LocalAssetStorageService())->delete($asset['storage_path']);
        }
        Db::name('assets')->where('id', $id)->delete();

        return $this->success(null, '素材已删除');
    }

    private function assetResponse(int $assetId): array
    {
        $asset = Db::name('assets')->where('id', $assetId)->find();
        $asset['download_url'] = '/api/v1/assets/' . $assetId . '/download';
        return $asset;
    }
}
