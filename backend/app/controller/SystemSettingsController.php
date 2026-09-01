<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use think\facade\Db;
use think\Request;
use think\Response;

final class SystemSettingsController extends ApiController
{
    private const GROUPS = [
        'company', 'members', 'channels', 'warehouse', 'payment', 'notifications', 'security',
    ];

    private const KEYS = [
        'company' => ['company_name','company_short_name','social_credit_code','industry','address','service_phone','system_name','default_locale','default_timezone','default_currency'],
        'members' => ['default_role','invite_expiry_days','offboarding_policy','directory_visibility'],
        'channels' => ['order_sync_frequency','inventory_sync_frequency','price_sync_policy','sync_failure_notification'],
        'warehouse' => ['default_warehouse','allocation_strategy','split_order_policy','stockout_policy','default_safety_stock','stock_reservation_minutes','low_stock_notification','stocktake_sales_policy'],
        'payment' => ['alipay_enabled','wechat_pay_enabled','jd_pay_enabled','douyin_pay_enabled','small_refund_limit','refund_approval_policy','reconciliation_time','reconciliation_alert_threshold'],
        'notifications' => ['order_exception','low_stock','refund_approval','campaign_approval','notification_digest','quiet_hours','high_priority_bypass','daily_report_time'],
        'security' => ['mfa_policy','password_expiry_days','login_failure_policy','unusual_login_policy','mask_sensitive_data','export_approval_threshold','operation_log_retention_days','session_timeout_hours'],
    ];

    public function index(string $group): Response
    {
        $this->assertGroup($group);
        $rows = Db::name('system_settings')->where('setting_group', $group)->select()->toArray();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $this->isSecretKey($row['setting_key'])
                ? ['configured' => (bool) $row['setting_value']]
                : json_decode($row['setting_value'], true);
        }
        return $this->success(['group' => $group, 'settings' => $settings]);
    }

    public function save(Request $request, string $group): Response
    {
        $this->assertGroup($group);
        $input = $request->post('settings', $request->post());
        if (!is_array($input)) {
            throw BusinessException::validationError(['settings' => ['设置格式必须是对象']]);
        }

        $allowed = self::KEYS[$group];
        $unknown = array_filter(array_keys($input), fn(string $key): bool => !in_array($key, $allowed, true) && !str_starts_with($key, 'ui_'));
        if ($unknown) {
            throw BusinessException::validationError(['settings' => ['包含不支持的设置项：' . implode(', ', $unknown)]]);
        }

        $member = $this->requireMember();
        $now = date('Y-m-d H:i:s');
        Db::transaction(function () use ($group, $input, $member, $now): void {
            foreach ($input as $key => $value) {
                if ($this->isSecretKey($key)) {
                    // Secret credentials belong in .env, never in this table.
                    continue;
                }
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $exists = Db::name('system_settings')->where('setting_group', $group)->where('setting_key', $key)->find();
                $record = ['setting_value' => $encoded, 'updated_by' => $member->id, 'updated_at' => $now];
                if ($exists) {
                    Db::name('system_settings')->where('id', $exists['id'])->update($record);
                } else {
                    Db::name('system_settings')->insert($record + ['setting_group' => $group, 'setting_key' => $key, 'created_at' => $now]);
                }
            }
            Db::name('operation_logs')->insert([
                'operator_id' => $member->id, 'module' => 'system_settings', 'action' => 'update',
                'resource_type' => 'setting_group', 'resource_id' => $group, 'result' => 'success',
                'risk_level' => in_array($group, ['payment', 'security'], true) ? 'high' : 'medium',
                'ip' => request()->ip(), 'detail' => json_encode(['keys' => array_keys($input)], JSON_UNESCAPED_UNICODE), 'created_at' => $now,
            ]);
        });

        return $this->index($group);
    }

    public function operationLogs(Request $request): Response
    {
        $this->requireMember();
        $page = max(1, (int) $request->get('page', 1));
        $size = min(100, max(1, (int) $request->get('page_size', 20)));
        $query = Db::name('operation_logs');
        if ($module = $request->get('module')) $query->where('module', $module);
        if ($risk = $request->get('risk_level')) $query->where('risk_level', $risk);
        $total = $query->count();
        return $this->paginated($query->order('id', 'desc')->page($page, $size)->select(), $page, $size, $total);
    }

    private function assertGroup(string $group): void
    {
        if (!in_array($group, self::GROUPS, true)) throw BusinessException::notFound('系统设置分组不存在');
    }

    private function isSecretKey(string $key): bool
    {
        return str_contains($key, 'secret') || str_contains($key, 'private_key') || str_contains($key, 'api_key');
    }
}
