<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Fulfillment\Contracts\FulfillmentProviderInterface;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\DataObjects\SupplierOrderResult;
use Webkul\Fulfillment\DataObjects\SupplierOrderStatus;
use Webkul\Fulfillment\Models\FinancialTimeline;
use Webkul\Fulfillment\Models\LedgerEntry;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Services\Application\FulfillmentSagaCoordinator;
use Webkul\Fulfillment\Services\Application\OutboxEventProcessor;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class MockAliExpressProvider implements FulfillmentProviderInterface
{
    public static bool $shouldSucceed = true;
    public static ?string $lastExternalId = 'ae-ext-9921';
    public static ?string $errorCode = 'OUT_OF_STOCK';

    public function code(): string
    {
        return 'aliexpress';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function createSupplierOrder(SupplierOrderRequest $request): SupplierOrderResult
    {
        if (self::$shouldSucceed) {
            return SupplierOrderResult::success(self::$lastExternalId ?? 'ae-ext-9921');
        } else {
            return SupplierOrderResult::failure(false, self::$errorCode ?? 'OUT_OF_STOCK', 'AliExpress error simulated.');
        }
    }

    public function getSupplierOrderStatus(string $externalOrderId, ?int $providerAccountId = null): SupplierOrderStatus
    {
        return new SupplierOrderStatus('shipped', []);
    }

    public function findByReference(string $internalReference, ?int $providerAccountId = null): ?string
    {
        return null;
    }

    public function cancelSupplierOrder(string $externalOrderId, ?int $providerAccountId = null): SupplierOrderResult
    {
        return SupplierOrderResult::success($externalOrderId);
    }
}

class FulfillmentSagaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        // Register Mock AliExpress provider class in config
        config(['fulfillment.providers.aliexpress' => MockAliExpressProvider::class]);

        // Clear tables using delete() instead of truncate() to preserve transaction savepoints
        DB::table('domain_outbox_event_attempts')->delete();
        DB::table('domain_outbox_events')->delete();
        DB::table('financial_timeline')->delete();
        DB::table('ledger_entries')->delete();
        DB::table('processed_events')->delete();
        OrderAllocation::query()->delete();
        PurchaseOrder::query()->delete();
    }

    /**
     * Test idempotency checks prevent duplicate processing.
     */
    public function test_saga_coordinator_idempotency_prevents_duplicate_processing(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $coordinator = app(FulfillmentSagaCoordinator::class);

        MockAliExpressProvider::$shouldSucceed = true;

        // First run - Accept
        $result = $coordinator->coordinate($order->id, $orderItem->id, 2, 'event_init_1', 'corr_1', ['has_local_stock' => true]);
        $this->assertEquals('success', $result['status']);

        // Second run - Duplicate status returned
        $result2 = $coordinator->coordinate($order->id, $orderItem->id, 2, 'event_init_1', 'corr_1', ['has_local_stock' => true]);
        $this->assertEquals('duplicate', $result2['status']);
    }

    /**
     * Test successful local routing decision, outbox generation, and listener execution.
     */
    public function test_saga_coordinator_successful_local_routing(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $coordinator = app(FulfillmentSagaCoordinator::class);
        $outboxProcessor = app(OutboxEventProcessor::class);

        // Run coordinator with local stock context
        $result = $coordinator->coordinate($order->id, $orderItem->id, 3, 'evt_local_1', 'corr_local_1', ['has_local_stock' => true]);
        
        $this->assertEquals('success', $result['status']);
        $this->assertNotNull($result['data']['allocation']);
        $this->assertEquals('warehouse_riyadh', $result['data']['allocation']->source_code);

        // Assert domain event was written to outbox table
        $outbox = DB::table('domain_outbox_events')->where('status', 'pending')->first();
        $this->assertNotNull($outbox);
        $this->assertEquals('OrderAllocationReserved', $outbox->event_name);
        $this->assertEquals('OrderAllocation', $outbox->aggregate_type);

        // Run outbox processor to trigger side-effect listeners (ledger and financial timeline)
        $processed = $outboxProcessor->processPending();
        $this->assertEquals(1, $processed);

        // Verify ledger entries created (Debit Cash 1010, Credit Revenue 4010)
        $debit = LedgerEntry::where('order_id', $order->id)->where('account_code', '1010')->first();
        $credit = LedgerEntry::where('order_id', $order->id)->where('account_code', '4010')->first();
        
        $this->assertNotNull($debit);
        $this->assertNotNull($credit);
        $this->assertEquals(60.00, $debit->debit); // 3 qty * $20.00
        $this->assertEquals(60.00, $credit->credit);

        // Verify timeline entry created
        $timeline = FinancialTimeline::where('order_id', $order->id)->where('event_type', 'allocation_reserved')->first();
        $this->assertNotNull($timeline);
        $this->assertEquals(60.00, $timeline->amount);
    }

    /**
     * Test compensation flow triggers when supplier dropshipping fails due to stockout.
     */
    public function test_supplier_fulfillment_saga_compensates_on_stock_out(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $coordinator = app(FulfillmentSagaCoordinator::class);
        $outboxProcessor = app(OutboxEventProcessor::class);

        // Set supplier mock to return OOS error
        MockAliExpressProvider::$shouldSucceed = false;
        MockAliExpressProvider::$errorCode = 'OUT_OF_STOCK';

        // Run coordinator (should attempt supplier procurement and catch failure)
        $result = $coordinator->coordinate($order->id, $orderItem->id, 2, 'evt_supp_fail_1', 'corr_supp_fail_1', ['has_local_stock' => false]);
        
        $this->assertEquals('compensated', $result['status']);

        // Assert all allocations reserved initially were canceled/released by compensation handler
        $alloc = OrderAllocation::where('order_id', $order->id)->first();
        $this->assertNotNull($alloc);
        $this->assertEquals('canceled', $alloc->state);
        $this->assertEquals(0, $alloc->reserved_qty);

        // Assert outbox events: PurchaseOrderCreated, SupplierOrderFailed, OrderAllocationReleased, CustomerOrderFlagged
        $events = DB::table('domain_outbox_events')->pluck('event_name')->toArray();
        $this->assertContains('PurchaseOrderCreated', $events);
        $this->assertContains('SupplierOrderFailed', $events);
        $this->assertContains('OrderAllocationReleased', $events);
        $this->assertContains('CustomerOrderFlagged', $events);

        // Run outbox processor to trigger reversing entries and failure logs
        $outboxProcessor->processPending();

        // Ledger must reverse to net zero (allocation reserve + allocation release reversing entries)
        $ledgerEntries = LedgerEntry::where('order_id', $order->id)->get();
        $this->assertCount(4, $ledgerEntries); // 2 reserve (debit/credit) + 2 release (debit/credit reversed)
        $this->assertEquals(0.00, LedgerEntry::where('order_id', $order->id)->sum('debit') - LedgerEntry::where('order_id', $order->id)->sum('credit'));

        // Financial Timeline must contain failure timeline entries
        $timelineEntries = FinancialTimeline::where('order_id', $order->id)->pluck('event_type')->toArray();
        $this->assertContains('supplier_order_failed', $timelineEntries);
        $this->assertContains('allocation_released', $timelineEntries);
    }

    /**
     * Test outbox processing handles listener failure and increments attempt/error logging.
     */
    public function test_outbox_event_delivery_failure_and_retry(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $coordinator = app(FulfillmentSagaCoordinator::class);
        $outboxProcessor = app(OutboxEventProcessor::class);

        // Run coordinator to trigger OrderAllocationReserved event
        $coordinator->coordinate($order->id, $orderItem->id, 2, 'evt_fail_retry_1', 'corr_fail_retry_1', ['has_local_stock' => true]);

        // Break LedgerEntry validation constraint temporarily using database connection bypass in test
        // By inserting a ledger listener mock that fails
        config(['app.ledger_fail_sim' => true]);
        
        // Custom listener mock that throws exception to test retry attempts
        $outboxProcessor->processPending(maxAttempts: 2);

        $outbox = DB::table('domain_outbox_events')->where('event_name', 'OrderAllocationReserved')->first();
        $this->assertEquals('pending', $outbox->status);
        $this->assertEquals(1, $outbox->attempts);

        // Verify attempt error trace was saved in domain_outbox_event_attempts
        $attempt = DB::table('domain_outbox_event_attempts')->where('outbox_event_id', $outbox->id)->first();
        $this->assertNotNull($attempt);
        $this->assertEquals('Webkul\Fulfillment\Listeners\LedgerListener', $attempt->listener);
        $this->assertNotNull($attempt->error_message);
    }

    /**
     * Test replaying outbox events is completely idempotent.
     */
    public function test_outbox_event_replay_is_idempotent(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $coordinator = app(FulfillmentSagaCoordinator::class);
        
        // Trigger allocation event
        $coordinator->coordinate($order->id, $orderItem->id, 2, 'evt_replay_idemp_1', 'corr_replay_idemp_1', ['has_local_stock' => true]);

        // Run outbox event dispatch the first time
        $processor = app(OutboxEventProcessor::class);
        $processor->processPending();

        $ledgerCount = LedgerEntry::where('order_id', $order->id)->count();
        $timelineCount = FinancialTimeline::where('order_id', $order->id)->count();
        $this->assertEquals(2, $ledgerCount);
        $this->assertEquals(1, $timelineCount);

        // Simulate Replay: reset outbox event back to pending status
        DB::table('domain_outbox_events')->update(['status' => 'pending']);

        // Run outbox event dispatch a second time
        $processor->processPending();

        // Total counts should be exactly the same because deduplication blocks duplicates on listeners level
        $this->assertEquals(2, LedgerEntry::where('order_id', $order->id)->count());
        $this->assertEquals(1, FinancialTimeline::where('order_id', $order->id)->count());

        config(['app.ledger_fail_sim' => false]);
    }

    /**
     * Test that provider failure does not corrupt customer financial state.
     */
    public function test_provider_failure_does_not_corrupt_customer_financial_state(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $coordinator = app(FulfillmentSagaCoordinator::class);
        $outboxProcessor = app(OutboxEventProcessor::class);

        // Force provider failure (stockout)
        MockAliExpressProvider::$shouldSucceed = false;
        MockAliExpressProvider::$errorCode = 'OUT_OF_STOCK';

        $result = $coordinator->coordinate($order->id, $orderItem->id, 2, 'evt_prov_fail_fin_1', 'corr_prov_fail_fin_1', ['has_local_stock' => false]);
        $this->assertEquals('compensated', $result['status']);

        // Run outbox event dispatches
        $outboxProcessor->processPending();

        // Verify that financial ledger is balanced (debits minus credits is zero)
        $ledgerSum = LedgerEntry::where('order_id', $order->id)->sum('debit') - LedgerEntry::where('order_id', $order->id)->sum('credit');
        $this->assertEquals(0.00, $ledgerSum);

        // Verify that we have 4 ledger entries (2 for reserve, 2 for reversing release)
        $this->assertEquals(4, LedgerEntry::where('order_id', $order->id)->count());

        // Verify that PO is marked with manual review state or cancelled
        $po = PurchaseOrder::where('order_id', $order->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals(PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW, $po->state); // PO creation succeeded, API dispatch failed.

        // Verify that CustomerOrderFlagged domain event was written to outbox
        $outboxFlagged = DB::table('domain_outbox_events')
            ->where('event_name', 'CustomerOrderFlagged')
            ->where('correlation_id', 'corr_prov_fail_fin_1')
            ->first();
        $this->assertNotNull($outboxFlagged);

        // Verify that Timeline log has the failure event registered
        $timelineFailure = FinancialTimeline::where('order_id', $order->id)
            ->where('event_type', 'supplier_order_failed')
            ->first();
        $this->assertNotNull($timelineFailure);
    }
}
