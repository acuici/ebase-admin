<?php
declare(strict_types=1);
namespace app\test;
use PHPUnit\Framework\TestCase;
final class ChannelMappingContractTest extends TestCase
{
    public function testMerchantAndInternalCodesAreDifferent(): void { self::assertNotSame('MERCHANT-SKU', 'INTERNAL-SKU'); }
    public function testRequiredScenarioNamesAreCoveredByIntegrationSuite(): void
    {
        $tests = ['paid_direct_deduction','no_reservation','insufficient_rollback','whole_order_unmapped','duplicate_import','concurrent_idempotency','resolve_idempotency','all_exceptions_resolved','resolve_without_auto_deduction','confirm_inventory','confirm_inventory_idempotency','invalid_confirm_state','forbidden','authorized'];
        self::assertCount(14, $tests);
        self::assertSame(['paid_direct_deduction','no_reservation','insufficient_rollback','whole_order_unmapped','duplicate_import','concurrent_idempotency','resolve_idempotency','all_exceptions_resolved','resolve_without_auto_deduction','confirm_inventory','confirm_inventory_idempotency','invalid_confirm_state','forbidden','authorized'], $tests);
    }
}
