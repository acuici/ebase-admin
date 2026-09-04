<?php
declare(strict_types=1);

namespace app\tests\Integration;

use app\common\exception\BusinessException;
use app\common\model\Member;
use app\common\service\ChannelOrderExceptionService;
use app\common\service\ChannelOrderImportService;
use PHPUnit\Framework\TestCase;
use think\facade\Db;

final class ChannelOrderMappingIntegrationTest extends TestCase
{
    private array $externalOrders = [];
    private array $stockSnapshots = [];

    protected function setUp(): void
    {
        putenv('DB_DRIVER=testing');
        putenv('TEST_DB_HOST=127.0.0.1');
        putenv('TEST_DB_PORT=3307');
        putenv('TEST_DB_NAME=ebase_test');
        putenv('TEST_DB_USER=ebase_test');
        putenv('TEST_DB_PASS=ebase_test_local');
        parent::setUp();
        self::assertSame('ebase_test', (string) getenv('TEST_DB_NAME'));
        Db::name('product_skus')->where('id', 1)->update(['stock_quantity' => 10, 'reserved_quantity' => 0]);
        foreach ([1 => 10, 2 => 1] as $id => $stock) Db::name('product_skus')->where('id', $id)->update(['stock_quantity' => $stock, 'reserved_quantity' => 0]);
    }

    protected function tearDown(): void
    {
        foreach ($this->externalOrders as $externalOrder) {
            $orderId = Db::name('orders')->where('external_order_no', $externalOrder)->value('id');
            if (!$orderId) continue;
            Db::name('inventory_ledgers')->where('reference_type', 'order')->where('reference_id', (string) $orderId)->delete();
            Db::name('order_items')->where('order_id', $orderId)->delete();
            Db::name('channel_order_item_exceptions')->where('order_id', $orderId)->delete();
            Db::name('order_status_logs')->where('order_id', $orderId)->delete();
            Db::name('operation_logs')->where('resource_id', (string) $orderId)->delete();
            Db::name('order_channel_extensions')->where('order_id', $orderId)->delete();
            Db::name('orders')->where('id', $orderId)->delete();
        }
        Db::name('channel_product_skus')->where('id', 1)->update(['external_sku_id' => 'JD-SKU-RED-001']);
        Db::name('channel_product_skus')->where('id', 2)->update(['external_sku_id' => 'JD-SKU-BLUE-001']);
        parent::tearDown();
    }

    public function testPaidMappedOrderDirectlyDeductsAvailableStockWithoutReservation(): void
    {
        $before = $this->sku(1);
        self::assertSame(0, (int) $before['reserved_quantity']);
        $result = $this->import('paid-direct', [$this->item('JD-SKU-RED-001', 'line-1', 2)]);
        $after = $this->sku(1);
        self::assertSame(8, (int) $after['stock_quantity']);
        self::assertSame(0, (int) $after['reserved_quantity']);
        self::assertSame(1, (int) Db::name('orders')->where('id', $result['order_id'])->count());
        self::assertSame(1, (int) Db::name('order_items')->where('order_id', $result['order_id'])->count());
        self::assertSame(1, (int) Db::name('inventory_ledgers')->where('reference_id', (string) $result['order_id'])->where('reason', 'deduct')->count());
        self::assertSame('resolved', Db::name('order_channel_extensions')->where('order_id', $result['order_id'])->value('mapping_status'));
        self::assertSame(1, (int) Db::name('order_status_logs')->where('order_id', $result['order_id'])->where('from_status')->where('to_status', 'paid')->where('source', 'channel_import')->count());
    }

    public function testInsufficientInventoryRollsBackEntireImport(): void
    {
        $external = 'insufficient-' . bin2hex(random_bytes(4));
        $this->externalOrders[] = $external;
        try {
            (new ChannelOrderImportService())->import($this->payload($external, [$this->item('JD-SKU-RED-001', 'line-1', 11)]), 1);
            self::fail('库存不足应抛出异常');
        } catch (BusinessException $exception) {
            self::assertSame('INVENTORY_INSUFFICIENT', $exception->getErrorCode());
        }
        self::assertSame(0, (int) Db::name('orders')->where('external_order_no', $external)->count());
        self::assertSame(10, (int) $this->sku(1)['stock_quantity']);
        self::assertSame(0, (int) Db::name('inventory_ledgers')->where('reference_id', $external)->count());
    }

    public function testOneUnmappedLinePreventsAllItemsAndInventoryMutation(): void
    {
        $before = $this->sku(1);
        $result = $this->import('unmapped', [$this->item('JD-SKU-RED-001', 'line-1', 1), $this->item('JD-SKU-NOT-MAPPED', 'line-2', 1)]);
        self::assertSame('pending', $result['mapping_status']);
        self::assertSame(0, (int) Db::name('order_items')->where('order_id', $result['order_id'])->count());
        self::assertSame(0, (int) Db::name('inventory_ledgers')->where('reference_id', (string) $result['order_id'])->count());
        self::assertSame((int) $before['stock_quantity'], (int) $this->sku(1)['stock_quantity']);
        self::assertSame(1, (int) Db::name('channel_order_item_exceptions')->where('order_id', $result['order_id'])->count());
        self::assertSame(0, (int) Db::name('operation_logs')->where('resource_id', (string) $result['order_id'])->count());
    }

    public function testDuplicateImportIsIdempotent(): void
    {
        $payload = $this->payload('duplicate', [$this->item('JD-SKU-RED-001', 'line-1', 1)]);
        $first = $this->import('duplicate', $payload['items']);
        $second = (new ChannelOrderImportService())->import($payload, 1);
        self::assertSame($first['order_id'], $second['order_id']);
        self::assertTrue($second['idempotent']);
        self::assertSame(1, (int) Db::name('orders')->where('external_order_no', 'duplicate')->count());
        self::assertSame(1, (int) Db::name('order_items')->where('order_id', $first['order_id'])->count());
        self::assertSame(1, (int) Db::name('inventory_ledgers')->where('reference_id', (string) $first['order_id'])->count());
        self::assertSame(1, (int) Db::name('order_status_logs')->where('order_id', $first['order_id'])->count());
    }

    public function testResolveIsIdempotentAndDoesNotDeductInventory(): void
    {
        $result = $this->import('resolve', [$this->item('JD-SKU-NOT-MAPPED', 'line-1', 1)]);
        $exceptionId = (int) Db::name('channel_order_item_exceptions')->where('order_id', $result['order_id'])->value('id');
        $service = new ChannelOrderExceptionService();
        Db::name('channel_product_skus')->where('id', 1)->update(['external_sku_id' => 'JD-SKU-NOT-MAPPED']);
        $resolved = $service->resolve($exceptionId, 1, 1, '人工确认');
        $again = $service->resolve($exceptionId, 1, 1, '重复确认');
        self::assertSame('resolved', $resolved['status']);
        self::assertTrue($again['idempotent']);
        self::assertSame(1, (int) Db::name('order_items')->where('order_id', $result['order_id'])->count());
        self::assertSame(0, (int) Db::name('inventory_ledgers')->where('reference_id', (string) $result['order_id'])->count());
        self::assertSame('resolved', Db::name('order_channel_extensions')->where('order_id', $result['order_id'])->value('mapping_status'));
        self::assertSame(1, (int) Db::name('operation_logs')->where('resource_type', 'channel_order_item_exception')->where('resource_id', (string) $exceptionId)->count());
    }

    public function testAllNewPermissionsAreOnlyAvailableToSuperAdmin(): void
    {
        $admin = (new Member())->find(1);
        $operator = (new Member())->find(2);
        self::assertContains('*', $admin->getPermissionCodes());
        self::assertNotContains('channel.order.inventory_confirm', $operator->getPermissionCodes());
        self::assertContains('channel.order.import', $operator->getPermissionCodes());
        self::assertContains('channel.mapping.manage', $operator->getPermissionCodes());
        self::assertContains('channel.order_exception.resolve', $operator->getPermissionCodes());
        self::assertNotContains('channel.order.inventory_confirm', $operator->getPermissionCodes());
    }

    private function import(string $external, array $items): array
    {
        $this->externalOrders[] = $external;
        return (new ChannelOrderImportService())->import($this->payload($external, $items), 1);
    }

    private function payload(string $external, array $items): array
    {
        return ['channel_type' => 'jd', 'channel_store_id' => 1, 'external_order_no' => $external, 'status' => 'paid', 'total_amount' => '100.00', 'currency' => 'CNY', 'items' => $items, 'raw_payload' => ['test' => true]];
    }

    private function item(string $externalSku, string $line, int $quantity): array
    {
        return ['external_order_item_id' => $line, 'external_product_id' => 'JD-PRODUCT-001', 'external_sku_id' => $externalSku, 'merchant_sku_code' => 'MERCHANT-DO-NOT-MAP', 'product_name' => '测试商品', 'spec_text' => '测试规格', 'quantity' => $quantity, 'unit_price' => '10.00'];
    }

    private function sku(int $id): array
    {
        return Db::name('product_skus')->where('id', $id)->find();
    }
}
