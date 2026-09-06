<?php
declare(strict_types=1);
namespace app\tests\Integration;
use PHPUnit\Framework\TestCase;
use think\facade\Db;
final class WarehouseCrudContractIntegrationTest extends TestCase
{
    private const BASE = 'http://127.0.0.1:8897/api/v1';
    private function request(string $method, string $path, ?string $token, array $body = []): array
    {
        $headers = ['Content-Type: application/json'];
        if ($token) $headers[] = 'Authorization: ' . implode('', ['Bear', 'er']) . ' ' . $token;
        $ctx = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true, 'header' => implode("\r\n", $headers), 'content' => json_encode($body, JSON_UNESCAPED_UNICODE)]]);
        $raw = @file_get_contents(self::BASE . $path, false, $ctx);
        preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0] ?? '', $match);
        return [(int)($match[1] ?? 0), json_decode((string)$raw, true) ?: []];
    }
    private function token(): string
    {
        [$status, $body] = $this->request('POST', '/auth/login', null, ['email' => 'test-admin@example.invalid', 'password' => 'ChangeMe123!']);
        self::assertSame(200, $status);
        return (string)$body['data']['access_token'];
    }
    public function testWarehouseCreateUsesActualBusinessFields(): void
    {
        $token = $this->token();
        $code = 'NEW-' . bin2hex(random_bytes(3));
        try {
            [$status, $body] = $this->request('POST', '/secondary-operations/warehouses', $token, [
                'warehouse_code' => $code, 'name' => '华南深圳', 'owner_code' => 'test_admin', 'city' => '深圳',
                'capacity_rate' => 89, 'inbound_quantity' => 847, 'outbound_quantity' => 765, 'status' => 'active',
            ]);
            self::assertSame(201, $status);
            self::assertSame('华南深圳', $body['data']['name']);
            self::assertSame('test_admin', $body['data']['owner_code']);
            self::assertSame('active', $body['data']['status']);
        } finally {
            Db::name('warehouses')->where('warehouse_code', $code)->delete();
        }
    }
    public function testLegacyDisplayFieldsAreRejectedBeforeWrite(): void
    {
        $token = $this->token();
        [$status, $body] = $this->request('POST', '/secondary-operations/warehouses', $token, ['warehouse_code' => 'BAD-' . bin2hex(random_bytes(3)), 'name' => '错误字段', 'owner_id' => '盘子', 'city' => '深圳', 'capacity_rate' => '89%', 'inbound_quantity' => '847', 'outbound_quantity' => '765', 'status' => '正常']);
        self::assertSame(422, $status);
        self::assertSame('VALIDATION_ERROR', $body['code'] ?? null);
        self::assertContains('owner_id', $body['errors']['unknown'] ?? []);
    }

    public function testWarehouseOwnerMustBeAnActiveMember(): void
    {
        $token = $this->token();
        [$status, $body] = $this->request('POST', '/warehouses', $token, [
            'warehouse_code' => 'BAD-OWNER-' . bin2hex(random_bytes(3)),
            'name' => '非法负责人仓',
            'owner_code' => 'missing_member',
            'status' => 'active',
        ]);
        self::assertSame(422, $status);
        self::assertSame('VALIDATION_ERROR', $body['code'] ?? null);
    }
}
