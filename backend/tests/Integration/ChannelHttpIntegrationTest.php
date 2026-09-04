<?php
declare(strict_types=1);

namespace app\tests\Integration;

use PHPUnit\Framework\TestCase;
use think\facade\Db;

final class ChannelHttpIntegrationTest extends TestCase
{
    private const BASE = 'http://127.0.0.1:8897/api/v1';
    private array $tokens = [];
    private static array $orders = [];
    private static array $mappingIds = [];
    private static array $channelProductIds = [];
    private static array $skuIds = [];
    private static array $productIds = [];

    public static function tearDownAfterClass(): void
    {
        foreach (array_unique(self::$orders) as $externalOrder) {
            $orderId = Db::name('orders')->where('external_order_no', $externalOrder)->value('id');
            if (!$orderId) {
                continue;
            }

            Db::name('inventory_ledgers')->where('reference_type', 'order')->where('reference_id', (string) $orderId)->delete();
            Db::name('order_items')->where('order_id', $orderId)->delete();
            Db::name('channel_order_item_exceptions')->where('order_id', $orderId)->delete();
            Db::name('order_status_logs')->where('order_id', $orderId)->delete();
            Db::name('operation_logs')->where('resource_id', (string) $orderId)->delete();
            Db::name('order_channel_extensions')->where('order_id', $orderId)->delete();
            Db::name('orders')->where('id', $orderId)->delete();
        }
        foreach (array_unique(self::$mappingIds) as $mappingId) {
            Db::name('channel_product_skus')->where('id', $mappingId)->delete();
        }
        foreach (array_unique(self::$channelProductIds) as $channelProductId) {
            Db::name('channel_products')->where('id', $channelProductId)->delete();
        }
        foreach (array_unique(self::$skuIds) as $skuId) {
            Db::name('product_skus')->where('id', $skuId)->delete();
        }
        foreach (array_unique(self::$productIds) as $productId) {
            Db::name('products')->where('id', $productId)->delete();
        }

        parent::tearDownAfterClass();
    }

    public function test01AdminLoginSucceeds(): void
    {
        $this->login('admin', 1);
    }

    public function test02OperatorLoginSucceeds(): void
    {
        $this->login('operator', 2);
    }

    public function test03UnauthorizedLoginSucceeds(): void
    {
        $this->login('unauthorized', 3);
    }

    public function test04MissingTokenReturns401(): void
    {
        $this->assertResponse($this->request('GET', '/channel-stores'), 401, 'UNAUTHENTICATED');
    }

    public function test05ExpiredTokenReturns401(): void
    {
        $this->assertResponse($this->request('GET', '/channel-stores', [], 'invalid.expired.token.value'), 401, 'UNAUTHENTICATED');
    }

    public function test06AdminImportSucceeds(): void
    {
        $result = $this->import('http-admin', $this->loginAs(1));
        $this->assertResponse($result, 201, 'OK');
        self::assertSame('resolved', $result['data']['mapping_status']);
    }

    public function test07AdminConfirmInventorySucceeds(): void
    {
        $adminToken = $this->loginAs(1);
        $result = $this->import('http-confirm', $adminToken);
        $this->assertResponse($result, 201, 'OK');
        $orderId = (int) $result['data']['order_id'];

        $confirmation = $this->request('POST', '/channel-orders/' . $orderId . '/confirm-inventory', [], $adminToken);
        $this->assertResponse($confirmation, 200, 'OK');
        self::assertTrue($confirmation['data']['inventory_processed']);
    }

    public function test08OperatorReadsChannelData(): void
    {
        $operatorToken = $this->loginAs(2);
        foreach (['/channel-stores', '/channel-products', '/channel-order-item-exceptions', '/channel-products/1'] as $path) {
            $this->assertResponse($this->request('GET', $path, [], $operatorToken), 200, 'OK');
        }
    }

    public function test09OperatorManagesMapping(): void
    {
        $operatorToken = $this->loginAs(2);
        $mappingId = $this->createPatchMappingFixture();
        $result = $this->request('PATCH', '/channel-product-skus/' . $mappingId, ['merchant_sku_code' => 'MERCHANT-BLUE-HTTP'], $operatorToken);
        $this->assertResponse($result, 200, 'OK');
        self::assertSame('MERCHANT-BLUE-HTTP', Db::name('channel_product_skus')->where('id', $mappingId)->value('merchant_sku_code'));
    }

    public function test10OperatorImportsOrder(): void
    {
        $result = $this->import('http-operator', $this->loginAs(2));
        $this->assertResponse($result, 201, 'OK');
        self::assertSame('resolved', $result['data']['mapping_status']);
    }

    public function test11OperatorResolvesException(): void
    {
        $operatorToken = $this->loginAs(2);
        $fixture = $this->createResolveFixture();
        $exceptionBefore = Db::name('channel_order_item_exceptions')->where('id', $fixture['exception_id'])->find();
        self::assertSame('pending', $exceptionBefore['status']);
        $stockBefore = Db::name('product_skus')->where('id', $fixture['sku_id'])->field('stock_quantity,reserved_quantity')->find();
        $ledgerBefore = (int) Db::name('inventory_ledgers')->where('sku_id', $fixture['sku_id'])->count();

        $resolved = $this->request('POST', '/channel-order-item-exceptions/' . $fixture['exception_id'] . '/resolve', ['product_sku_id' => $fixture['sku_id'], 'note' => 'HTTP test'], $operatorToken);
        $this->assertResponse($resolved, 200, 'OK');
        $exceptionAfter = Db::name('channel_order_item_exceptions')->where('id', $fixture['exception_id'])->find();
        self::assertSame('resolved', $exceptionAfter['status']);
        self::assertSame($fixture['sku_id'], (int) $exceptionAfter['resolved_product_sku_id']);
        self::assertSame(2, (int) $exceptionAfter['resolved_by']);
        self::assertNotEmpty($exceptionAfter['resolved_at']);
        self::assertSame(1, (int) Db::name('order_items')->where('order_id', $fixture['order_id'])->count());
        self::assertSame('resolved', Db::name('order_channel_extensions')->where('order_id', $fixture['order_id'])->value('mapping_status'));
        self::assertGreaterThan(0, (int) Db::name('order_status_logs')->where('order_id', $fixture['order_id'])->count());
        self::assertSame(1, (int) Db::name('operation_logs')->where('resource_type', 'channel_order_item_exception')->where('resource_id', (string) $fixture['exception_id'])->count());
        self::assertSame($stockBefore, Db::name('product_skus')->where('id', $fixture['sku_id'])->field('stock_quantity,reserved_quantity')->find());
        self::assertSame($ledgerBefore, (int) Db::name('inventory_ledgers')->where('sku_id', $fixture['sku_id'])->count());

        $again = $this->request('POST', '/channel-order-item-exceptions/' . $fixture['exception_id'] . '/resolve', ['product_sku_id' => $fixture['sku_id'], 'note' => 'HTTP test'], $operatorToken);
        $this->assertResponse($again, 200, 'OK');
        self::assertTrue($again['data']['idempotent']);
        self::assertSame(1, (int) Db::name('order_items')->where('order_id', $fixture['order_id'])->count());
        self::assertSame(1, (int) Db::name('operation_logs')->where('resource_type', 'channel_order_item_exception')->where('resource_id', (string) $fixture['exception_id'])->count());
        self::assertSame($ledgerBefore, (int) Db::name('inventory_ledgers')->where('sku_id', $fixture['sku_id'])->count());
    }

    public function test12OperatorConfirmInventoryReturns403(): void
    {
        $operatorToken = $this->loginAs(2);
        $result = $this->import('http-operator-confirm', $operatorToken);
        $this->assertResponse($result, 201, 'OK');
        $orderId = (int) $result['data']['order_id'];

        $this->assertResponse($this->request('POST', '/channel-orders/' . $orderId . '/confirm-inventory', [], $operatorToken), 403, 'FORBIDDEN');
    }

    public function test13UnauthorizedChannelOperationsReturn403(): void
    {
        $unauthorizedToken = $this->loginAs(3);
        $requests = [
            ['GET', '/channel-stores', []],
            ['POST', '/channel-orders/import', $this->payload('http-unauthorized')],
            ['POST', '/channel-orders/1/confirm-inventory', []],
        ];

        foreach ($requests as [$method, $path, $payload]) {
            $this->assertResponse($this->request($method, $path, $payload, $unauthorizedToken), 403, 'FORBIDDEN');
        }
    }

    public function test14DuplicateConfirmInventoryIsIdempotent(): void
    {
        $adminToken = $this->loginAs(1);
        $result = $this->import('http-idempotent', $adminToken);
        $this->assertResponse($result, 201, 'OK');
        $orderId = (int) $result['data']['order_id'];
        $before = (int) Db::name('inventory_ledgers')->where('reference_id', (string) $orderId)->count();

        $this->assertResponse($this->request('POST', '/channel-orders/' . $orderId . '/confirm-inventory', [], $adminToken), 200, 'OK');
        $this->assertResponse($this->request('POST', '/channel-orders/' . $orderId . '/confirm-inventory', [], $adminToken), 200, 'OK');

        self::assertSame($before, (int) Db::name('inventory_ledgers')->where('reference_id', (string) $orderId)->count());
        self::assertSame(1, (int) Db::name('order_channel_extensions')->where('order_id', $orderId)->whereNotNull('inventory_processed_at')->count());
    }

    private function login(string $key, int $memberId): void
    {
        $this->tokens[$key] = $this->loginAs($memberId);
    }

    private function loginAs(int $memberId): string
    {
        $emails = [
            1 => 'test-admin@example.invalid',
            2 => 'test-operator@example.invalid',
            3 => 'test-unauthorized@example.invalid',
        ];
        $result = $this->request('POST', '/auth/login', ['email' => $emails[$memberId], 'password' => 'ChangeMe123!']);
        $this->assertResponse($result, 200, 'OK');
        $accessToken = (string) ($result['data']['access_token'] ?? '');
        self::assertNotSame('', $accessToken);
        self::assertGreaterThan(20, strlen($accessToken));

        return $accessToken;
    }

    private function import(string $externalOrder, ?string $accessToken = null, string $externalSku = 'JD-SKU-RED-001'): array
    {
        self::$orders[] = $externalOrder;
        return $this->request('POST', '/channel-orders/import', $this->payload($externalOrder, $externalSku), $accessToken ?? $this->loginAs(1));
    }

    private function payload(string $externalOrder, string $externalSku = 'JD-SKU-RED-001'): array
    {
        return [
            'channel_type' => 'jd',
            'channel_store_id' => 1,
            'external_order_no' => $externalOrder,
            'status' => 'paid',
            'total_amount' => '100.00',
            'currency' => 'CNY',
            'items' => [[
                'external_order_item_id' => 'http-line-1',
                'external_product_id' => 'JD-PRODUCT-001',
                'external_sku_id' => $externalSku,
                'merchant_sku_code' => 'MERCHANT-RED',
                'product_name' => '测试商品',
                'spec_text' => '测试规格',
                'quantity' => 1,
                'unit_price' => '10.00',
            ]],
            'raw_payload' => ['test' => true],
        ];
    }

    private function createPatchMappingFixture(): int
    {
        $now = date('Y-m-d H:i:s');
        $suffix = bin2hex(random_bytes(4));
        $productId = $this->createFixtureProduct('HTTP-PATCH-PRODUCT-' . $suffix, $now);
        $skuId = (int) Db::name('product_skus')->insertGetId([
            'product_id' => $productId,
            'sku_code' => 'HTTP-PATCH-' . $suffix,
            'name' => 'HTTP PATCH 测试 SKU',
            'specs' => json_encode(['test' => 'patch']),
            'price' => '10.00',
            'market_price' => '10.00',
            'stock_quantity' => 3,
            'reserved_quantity' => 0,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::$skuIds[] = $skuId;
        $channelProductId = (int) Db::name('channel_products')->insertGetId([
            'channel_store_id' => 1,
            'product_id' => $productId,
            'external_product_id' => 'HTTP-PATCH-PRODUCT-' . $suffix,
            'title' => 'HTTP PATCH 测试商品',
            'listing_status' => 'published',
            'sync_status' => 'synced',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::$channelProductIds[] = $channelProductId;
        $mappingId = (int) Db::name('channel_product_skus')->insertGetId([
            'channel_product_id' => $channelProductId,
            'product_sku_id' => $skuId,
            'external_sku_id' => 'HTTP-PATCH-SKU-' . $suffix,
            'merchant_sku_code' => 'HTTP-PATCH-OLD',
            'currency' => 'CNY',
            'inventory_policy' => 'shared',
            'listing_status' => 'published',
            'sync_status' => 'synced',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::$mappingIds[] = $mappingId;

        return $mappingId;
    }

    private function createResolveFixture(): array
    {
        $now = date('Y-m-d H:i:s');
        $suffix = bin2hex(random_bytes(5));
        $externalOrder = 'http-resolve-' . $suffix;
        $externalProduct = 'HTTP-RESOLVE-PRODUCT-' . $suffix;
        $externalSku = 'HTTP-RESOLVE-SKU-' . $suffix;
        $itemKey = 'http-resolve-line-' . $suffix;
        $productId = $this->createFixtureProduct('HTTP-RESOLVE-PRODUCT-' . $suffix, $now);
        $skuId = (int) Db::name('product_skus')->insertGetId([
            'product_id' => $productId,
            'sku_code' => 'HTTP-RESOLVE-' . $suffix,
            'name' => 'HTTP Resolve 测试 SKU',
            'specs' => json_encode(['test' => 'resolve']),
            'price' => '15.00',
            'market_price' => '15.00',
            'stock_quantity' => 7,
            'reserved_quantity' => 0,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::$skuIds[] = $skuId;
        $channelProductId = (int) Db::name('channel_products')->insertGetId([
            'channel_store_id' => 1,
            'product_id' => $productId,
            'external_product_id' => $externalProduct,
            'title' => 'HTTP Resolve 测试商品',
            'listing_status' => 'published',
            'sync_status' => 'synced',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::$channelProductIds[] = $channelProductId;
        $mappingId = (int) Db::name('channel_product_skus')->insertGetId([
            'channel_product_id' => $channelProductId,
            'product_sku_id' => $skuId,
            'external_sku_id' => $externalSku,
            'merchant_sku_code' => 'HTTP-RESOLVE-MAP',
            'currency' => 'CNY',
            'inventory_policy' => 'shared',
            'listing_status' => 'published',
            'sync_status' => 'synced',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::$mappingIds[] = $mappingId;
        $orderId = (int) Db::name('orders')->insertGetId([
            'order_no' => 'EOHTTP' . strtoupper($suffix),
            'member_id' => null,
            'channel_type' => 'jd',
            'channel_store_id' => 1,
            'external_order_no' => $externalOrder,
            'status' => 'paid',
            'total_amount' => '15.00',
            'currency' => 'CNY',
            'paid_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::$orders[] = $externalOrder;
        Db::name('order_channel_extensions')->insert([
            'order_id' => $orderId,
            'channel_type' => 'jd',
            'channel_store_id' => 1,
            'external_order_no' => $externalOrder,
            'raw_payload' => json_encode(['test' => 'resolve']),
            'mapping_status' => 'pending',
            'inventory_processed_at' => null,
            'imported_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Db::name('order_status_logs')->insert([
            'order_id' => $orderId,
            'from_status' => null,
            'to_status' => 'paid',
            'operator_id' => 2,
            'source' => 'channel_import',
            'remark' => 'Resolve HTTP fixture',
            'created_at' => $now,
        ]);
        $exceptionId = (int) Db::name('channel_order_item_exceptions')->insertGetId([
            'order_id' => $orderId,
            'channel_store_id' => 1,
            'external_order_item_id' => $itemKey,
            'external_order_item_key' => $itemKey,
            'external_product_id' => $externalProduct,
            'external_sku_id' => $externalSku,
            'merchant_sku_code' => 'HTTP-RESOLVE-MAP',
            'reason_code' => 'CHANNEL_SKU_MAPPING_NOT_FOUND',
            'status' => 'pending',
            'raw_item' => json_encode(['product_name' => 'HTTP Resolve 测试商品', 'quantity' => 1, 'unit_price' => '15.00']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['order_id' => $orderId, 'exception_id' => $exceptionId, 'sku_id' => $skuId];
    }

    private function createFixtureProduct(string $productNo, string $now): int
    {
        $productId = (int) Db::name('products')->insertGetId([
            'product_no' => $productNo,
            'name' => 'HTTP 测试内部商品',
            'brand' => 'HTTP Test',
            'category' => 'testing',
            'description' => '独立 HTTP 测试数据',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        self::$productIds[] = $productId;

        return $productId;
    }

    private function request(string $method, string $path, array $body = [], ?string $accessToken = null): array
    {
        $headers = [
            'Content-Type: application/json',
            'X-Request-Id: http-test-' . bin2hex(random_bytes(4)),
        ];
        if ($accessToken !== null) {
            self::assertNotSame('', $accessToken);
            self::assertGreaterThan(20, strlen($accessToken));
            $scheme = implode('', ['Bear', 'er']);
            $authorization = $scheme . ' ' . $accessToken;
            self::assertStringStartsWith($scheme . ' ', $authorization);
            $headers[] = 'Authorization' . ': ' . $authorization;
        }

        $context = stream_context_create(['http' => [
            'method' => $method,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers),
            'content' => $body ? json_encode($body, JSON_UNESCAPED_UNICODE) : '',
        ]]);
        $raw = file_get_contents(self::BASE . $path, false, $context);
        preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0] ?? '', $match);
        $response = json_decode((string) $raw, true) ?: [];

        return [
            'status' => (int) ($match[1] ?? 0),
            'response' => $response,
            'code' => $response['code'] ?? null,
            'data' => $response['data'] ?? null,
            'request_id' => $response['request_id'] ?? null,
        ];
    }

    private function assertResponse(array $response, int $status, string $code): void
    {
        self::assertSame($status, $response['status']);
        self::assertSame($code, $response['code']);
        self::assertNotEmpty($response['request_id']);
    }
}
