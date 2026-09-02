<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\service\NotificationService;
use think\facade\Db;
use think\Request;
use think\Response;

/** CRUD endpoints for secondary operations backed by domain tables. */
final class SecondaryOperationsController extends ApiController
{
    private const TABLES = [
        'refunds' => 'refunds',
        'warehouses' => 'warehouses',
        'categories' => 'categories',
        'suppliers' => 'suppliers',
        'segments' => 'customer_segments',
        'assets' => 'assets',
        'approvals' => 'approval_requests',
        'audit-logs' => 'operation_logs',
    ];

    public function create(Request $request, string $type): Response
    {
        $this->assertWritable($type);
        $data = $this->clean($type, $request->post());
        $this->requireMember();
        $id = Db::name(self::TABLES[$type])->insertGetId($data + ['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        return $this->success(Db::name(self::TABLES[$type])->where('id', $id)->find(), '记录已创建', 201);
    }

    public function update(Request $request, string $type, int $id): Response
    {
        $this->assertWritable($type);
        $table = self::TABLES[$type];
        if (!Db::name($table)->where('id', $id)->find()) throw BusinessException::notFound('记录不存在');
        $data = $this->clean($type, $request->post());
        $data['updated_at'] = date('Y-m-d H:i:s');
        Db::name($table)->where('id', $id)->update($data);
        return $this->success(Db::name($table)->where('id', $id)->find(), '记录已更新');
    }

    public function delete(string $type, int $id): Response
    {
        $this->assertWritable($type);
        $table = self::TABLES[$type];
        if (!Db::name($table)->where('id', $id)->find()) throw BusinessException::notFound('记录不存在');
        Db::name($table)->where('id', $id)->delete();
        return $this->success(null, '记录已删除');
    }

    private function assertWritable(string $type): void
    {
        if (!isset(self::TABLES[$type]) || in_array($type, ['assets', 'audit-logs'], true)) throw BusinessException::validationError(['type' => ['该功能暂不支持写入']]);
    }

    private function clean(string $type, array $data): array
    {
        $allowed = match ($type) {
            'segments' => ['name', 'description', 'rules', 'status'],
            'approvals' => ['request_type', 'resource_id', 'status', 'comment'],
            default => array_values(array_filter(array_keys($data), fn ($key) => !in_array($key, ['id', 'created_at', 'updated_at'], true))),
        };
        $clean = array_intersect_key($data, array_flip($allowed));
        if (isset($clean['rules']) && is_array($clean['rules'])) $clean['rules'] = json_encode($clean['rules'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        return $clean;
    }
}
