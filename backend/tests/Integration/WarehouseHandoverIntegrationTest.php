<?php
declare(strict_types=1);
namespace app\tests\Integration;
use PHPUnit\Framework\TestCase;
use think\facade\Db;
final class WarehouseHandoverIntegrationTest extends TestCase
{
    private const BASE = 'http://127.0.0.1:8897/api/v1';
    private function request(string $method, string $path, ?string $token, array $body = []): array
    {
        $headers = ['Content-Type: application/json'];
        if ($token) $headers[] = 'Authorization: ' . implode('', ['Bear', 'er']) . ' ' . $token;
        $context = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true, 'header' => implode("\r\n", $headers), 'content' => json_encode($body, JSON_UNESCAPED_UNICODE)]]);
        $raw = @file_get_contents(self::BASE . $path, false, $context);
        preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0] ?? '', $match);
        return [(int)($match[1] ?? 0), json_decode((string)$raw, true) ?: []];
    }
    private function token(string $email): string
    {
        [$status, $body] = $this->request('POST', '/auth/login', null, ['email' => $email, 'password' => 'ChangeMe123!']);
        self::assertSame(200, $status);
        return (string)$body['data']['access_token'];
    }
    public function testWarehouseListReadsRealRowsAndMemberOwners(): void
    {
        $token = $this->token('test-admin@example.invalid');
        [$status, $body] = $this->request('GET', '/warehouses?page_size=100', $token);
        self::assertSame(200, $status);
        self::assertSame('OK', $body['code']);
        self::assertArrayHasKey('pagination', $body['data']);
        self::assertSame(100, $body['data']['pagination']['page_size']);
    }
    public function testTemporaryHandoverAndRestoreUseActiveMembers(): void
    {
        $token = $this->token('test-admin@example.invalid');
        [$createStatus, $created] = $this->request('POST', '/warehouses', $token, ['warehouse_code' => 'HAND-' . bin2hex(random_bytes(3)), 'name' => '交接测试仓', 'owner_code' => 'test_operator', 'city' => '深圳', 'capacity_rate' => 60, 'inbound_quantity' => 1, 'outbound_quantity' => 2, 'status' => 'active']);
        self::assertSame(201, $createStatus);
        $id = (int)$created['data']['id'];
        try {
            [$handoverStatus, $handover] = $this->request('POST', "/warehouses/{$id}/handover", $token, ['to_owner_code' => 'test_admin', 'handover_type' => 'temporary', 'reason' => '岗位交接']);
            self::assertSame(200, $handoverStatus);
            self::assertSame('test_admin', $handover['data']['owner_code']);
            [$restoreStatus, $restored] = $this->request('POST', "/warehouses/{$id}/restore", $token);
            self::assertSame(200, $restoreStatus);
            self::assertSame('test_operator', $restored['data']['owner_code']);
            [$historyStatus, $history] = $this->request('GET', "/warehouses/{$id}/handovers", $token);
            self::assertSame(200, $historyStatus);
            self::assertNotEmpty($history['data']['items']);
        } finally {
            Db::name('warehouse_handover_records')->where('warehouse_id', $id)->delete();
            Db::name('warehouses')->where('id', $id)->delete();
        }
    }
    public function testInactiveOwnerCannotReceiveAssignment(): void
    {
        $token = $this->token('test-admin@example.invalid');
        [$status, $body] = $this->request('GET', '/warehouses/members', $token);
        self::assertSame(200, $status);
        self::assertNotEmpty($body['data']['items']);
    }
    public function testMemberOffboardReclaimsAndReenableRestoresTemporaryHandover(): void
    {
        $token = $this->token('test-admin@example.invalid');
        [$membersStatus, $membersBody] = $this->request('GET', '/admin/members?keyword=test-operator', $token);
        self::assertSame(200, $membersStatus);
        $operator = $membersBody['data']['items'][0] ?? null;
        self::assertNotNull($operator);
        $operatorId = (int) $operator['id'];
        $code = 'OFFBOARD-' . bin2hex(random_bytes(3));
        [$createStatus, $created] = $this->request('POST', '/warehouses', $token, ['warehouse_code' => $code, 'name' => '离职交接测试仓', 'owner_code' => 'test_operator', 'city' => '深圳', 'capacity_rate' => 50, 'inbound_quantity' => 0, 'outbound_quantity' => 0, 'status' => 'active']);
        self::assertSame(201, $createStatus);
        $warehouseId = (int) $created['data']['id'];
        try {
            [$handoverStatus] = $this->request('POST', "/warehouses/{$warehouseId}/handover", $token, ['to_owner_code' => 'test_admin', 'handover_type' => 'temporary', 'reason' => '离职前临时交接']);
            self::assertSame(200, $handoverStatus);
            [$disableStatus] = $this->request('POST', "/admin/members/{$operatorId}/disable", $token);
            self::assertSame(200, $disableStatus);
            [$afterDisableStatus, $afterDisable] = $this->request('GET', "/warehouses/{$warehouseId}", $token);
            self::assertSame(200, $afterDisableStatus);
            self::assertSame('test_admin', $afterDisable['data']['owner_code']);
            [$enableStatus] = $this->request('PUT', "/admin/members/{$operatorId}", $token, ['name' => $operator['name'], 'email' => $operator['email'], 'status' => 1]);
            self::assertSame(200, $enableStatus);
            [$afterEnableStatus, $afterEnable] = $this->request('GET', "/warehouses/{$warehouseId}", $token);
            self::assertSame(200, $afterEnableStatus);
            self::assertSame('test_operator', $afterEnable['data']['owner_code']);
            [$disableWarehouseStatus] = $this->request('DELETE', "/warehouses/{$warehouseId}", $token);
            self::assertSame(200, $disableWarehouseStatus);
            [$afterDisableWarehouseStatus, $afterDisableWarehouse] = $this->request('GET', "/warehouses/{$warehouseId}", $token);
            self::assertSame(200, $afterDisableWarehouseStatus);
            self::assertSame('disabled', $afterDisableWarehouse['data']['status']);
        } finally {
            $this->request('PUT', "/admin/members/{$operatorId}", $token, ['name' => $operator['name'], 'email' => $operator['email'], 'status' => 1]);
            Db::name('warehouse_handover_records')->where('warehouse_id', $warehouseId)->delete();
            Db::name('warehouses')->where('id', $warehouseId)->delete();
        }
    }
}
