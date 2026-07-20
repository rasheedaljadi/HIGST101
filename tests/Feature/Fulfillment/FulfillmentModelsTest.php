<?php

namespace Tests\Feature\Fulfillment;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Fulfillment\Contracts\OrderAllocation as OrderAllocationContract;
use Webkul\Fulfillment\Contracts\AllocationLog as AllocationLogContract;
use Webkul\Fulfillment\Contracts\ProcessedEvent as ProcessedEventContract;
use Webkul\Fulfillment\Contracts\FinancialTimeline as FinancialTimelineContract;
use Webkul\Fulfillment\Contracts\LedgerEntry as LedgerEntryContract;
use Webkul\Fulfillment\Exceptions\ConcurrentUpdateException;
use Webkul\Fulfillment\Exceptions\DuplicateEventException;
use Webkul\Fulfillment\Exceptions\ImmutableTimelineException;
use Webkul\Fulfillment\Exceptions\InvalidStateTransitionException;
use Webkul\Fulfillment\Exceptions\StaleEventException;
use Webkul\Fulfillment\Exceptions\UnbalancedLedgerException;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\AllocationLog;
use Webkul\Fulfillment\Models\ProcessedEvent;
use Webkul\Fulfillment\Models\FinancialTimeline;
use Webkul\Fulfillment\Models\LedgerEntry;
use Webkul\Fulfillment\Repositories\OrderAllocationRepository;
use Webkul\Fulfillment\Repositories\AllocationLogRepository;
use Webkul\Fulfillment\Repositories\ProcessedEventRepository;
use Webkul\Fulfillment\Repositories\FinancialTimelineRepository;
use Webkul\Fulfillment\Repositories\LedgerEntryRepository;
use Webkul\Fulfillment\Services\Domain\EventDeduplicationService;
use Webkul\Fulfillment\Services\Domain\LedgerDomainService;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class FulfillmentModelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }
    }

    /**
     * Test schemas of the 5 tables exist with correct columns.
     */
    public function test_all_5_new_database_tables_exist_with_correct_columns(): void
    {
        // 1. order_allocations
        $this->assertTrue(Schema::hasTable('order_allocations'));
        $this->assertTrue(Schema::hasColumns('order_allocations', [
            'id', 'order_id', 'order_item_id', 'allocation_type', 'source_code',
            'supplier_signature', 'reserved_qty', 'fulfilled_qty', 'canceled_qty',
            'state', 'version', 'created_at', 'updated_at'
        ]));

        // 2. allocation_logs
        $this->assertTrue(Schema::hasTable('allocation_logs'));
        $this->assertTrue(Schema::hasColumns('allocation_logs', [
            'id', 'order_allocation_id', 'action', 'old_qty', 'new_qty',
            'reason', 'created_at', 'updated_at'
        ]));

        // 3. processed_events
        $this->assertTrue(Schema::hasTable('processed_events'));
        $this->assertTrue(Schema::hasColumns('processed_events', [
            'id', 'provider', 'event_id', 'event_name', 'processed_at', 'created_at', 'updated_at'
        ]));

        // 4. financial_timeline
        $this->assertTrue(Schema::hasTable('financial_timeline'));
        $this->assertTrue(Schema::hasColumns('financial_timeline', [
            'id', 'event_id', 'order_id', 'event_type', 'amount', 'currency',
            'metadata', 'recorded_at', 'created_at', 'updated_at'
        ]));

        // 5. ledger_entries
        $this->assertTrue(Schema::hasTable('ledger_entries'));
        $this->assertTrue(Schema::hasColumns('ledger_entries', [
            'id', 'order_id', 'account_code', 'debit', 'credit', 'reference',
            'created_at', 'updated_at'
        ]));
    }

    /**
     * Test Concord model bindings are correctly registered and resolvable.
     */
    public function test_concord_model_bindings_are_correctly_registered(): void
    {
        $this->assertInstanceOf(OrderAllocation::class, app(OrderAllocationContract::class));
        $this->assertInstanceOf(AllocationLog::class, app(AllocationLogContract::class));
        $this->assertInstanceOf(ProcessedEvent::class, app(ProcessedEventContract::class));
        $this->assertInstanceOf(FinancialTimeline::class, app(FinancialTimelineContract::class));
        $this->assertInstanceOf(LedgerEntry::class, app(LedgerEntryContract::class));
    }

    /**
     * Test optimistic locking trait prevents concurrent updates.
     */
    public function test_optimistic_locking_prevents_multi_party_concurrent_updates(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $allocation = OrderAllocation::create([
            'order_id'        => $order->id,
            'order_item_id'   => $orderItem->id,
            'allocation_type' => 'supplier',
            'source_code'     => 'aliexpress',
            'reserved_qty'    => 2,
            'state'           => 'reserved',
            'version'         => 1,
        ]);

        // Simulating 3 concurrent instances loaded from database
        $a1 = OrderAllocation::find($allocation->id);
        $a2 = OrderAllocation::find($allocation->id);
        $a3 = OrderAllocation::find($allocation->id);

        $this->assertEquals(1, $a1->version);
        $this->assertEquals(1, $a2->version);
        $this->assertEquals(1, $a3->version);

        // Instance 1 updates first
        $a1->reserved_qty = 3;
        $this->assertTrue($a1->save());
        $this->assertEquals(2, $a1->version);

        // Instance 2 attempts update (should fail due to version mismatch)
        $a2->reserved_qty = 4;
        try {
            $a2->save();
            $this->fail("Expected ConcurrentUpdateException was not thrown.");
        } catch (ConcurrentUpdateException $e) {
            $this->assertStringContainsString("Version conflict", $e->getMessage());
        }

        // Instance 3 attempts update (should also fail)
        $a3->reserved_qty = 5;
        try {
            $a3->save();
            $this->fail("Expected ConcurrentUpdateException was not thrown.");
        } catch (ConcurrentUpdateException $e) {
            $this->assertStringContainsString("Version conflict", $e->getMessage());
        }

        // Fresh retrieve & update retry should succeed
        $retryInstance = OrderAllocation::find($allocation->id);
        $this->assertEquals(2, $retryInstance->version);
        $retryInstance->reserved_qty = 10;
        $this->assertTrue($retryInstance->save());
        $this->assertEquals(3, $retryInstance->version);
    }

    /**
     * Test Double Entry rules in LedgerDomainService.
     */
    public function test_double_entry_enforces_balanced_accounts(): void
    {
        $ledgerDomainService = app(LedgerDomainService::class);
        $order = Order::factory()->create();

        // 1. Balanced case
        $entries = $ledgerDomainService->buildDoubleEntry($order->id, '1010', '4010', 150.00, 'ref1');
        $this->assertCount(2, $entries);
        $this->assertEquals(150.00, $entries[0]->debit);
        $this->assertEquals(0.00, $entries[0]->credit);
        $this->assertEquals(0.00, $entries[1]->debit);
        $this->assertEquals(150.00, $entries[1]->credit);

        // 2. Amount <= 0 Exception
        try {
            $ledgerDomainService->buildDoubleEntry($order->id, '1010', '4010', -10.00);
            $this->fail("Expected UnbalancedLedgerException not thrown for negative amount.");
        } catch (UnbalancedLedgerException $e) {
            $this->assertStringContainsString("greater than zero", $e->getMessage());
        }

        // 3. Same debit/credit account Exception
        try {
            $ledgerDomainService->buildDoubleEntry($order->id, '1010', '1010', 100.00);
            $this->fail("Expected UnbalancedLedgerException not thrown for identical accounts.");
        } catch (UnbalancedLedgerException $e) {
            $this->assertStringContainsString("cannot be the same", $e->getMessage());
        }
    }

    /**
     * Test processed events event deduplication service.
     */
    public function test_processed_events_prevents_duplicate_and_stale_events(): void
    {
        $deduplicationService = app(EventDeduplicationService::class);

        $now = now();
        $staleTime = $now->copy()->subMinutes(10);
        $futureTime = $now->copy()->addMinutes(10);

        // 1. Process AliExpress event1 - Accept
        $result = $deduplicationService->processEvent('aliexpress', 'event_1', 'OrderPlaced', $now);
        $this->assertTrue($result->isAccepted());

        // 2. Process CJ event1 - Accept (same ID, different provider)
        $result = $deduplicationService->processEvent('cj', 'event_1', 'OrderPlaced', $now);
        $this->assertTrue($result->isAccepted());

        // 3. Process AliExpress event1 again - Duplicate
        $result = $deduplicationService->processEvent('aliexpress', 'event_1', 'OrderPlaced', $now);
        $this->assertTrue($result->isDuplicate());

        // 4. Process AliExpress event2 with a stale timestamp - Stale
        $result = $deduplicationService->processEvent('aliexpress', 'event_2', 'OrderPlaced', $staleTime);
        $this->assertTrue($result->isStale());

        // 5. Process AliExpress event3 with a future/newer timestamp - Accept
        $result = $deduplicationService->processEvent('aliexpress', 'event_3', 'OrderPlaced', $futureTime);
        $this->assertTrue($result->isAccepted());
    }

    /**
     * Test FinancialTimeline is immutable and append-only.
     */
    public function test_financial_timeline_is_strictly_append_only(): void
    {
        $order = Order::factory()->create();

        // 1. Try to create with missing fields - fail
        try {
            FinancialTimeline::create([
                'order_id' => $order->id,
            ]);
            $this->fail("Expected InvalidArgumentException for missing fields.");
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("required for FinancialTimeline", $e->getMessage());
        }

        // 2. Append event via factory helper
        $timeline = FinancialTimeline::appendEvent($order->id, 'customer_paid', 100.00, 'USD', ['gateway' => 'stripe']);
        $timeline->save();
        $this->assertNotNull($timeline->id);
        $this->assertNotNull($timeline->event_id); // Generated UUID

        // 3. Prevent updates
        try {
            $timeline->amount = 150.00;
            $timeline->save();
            $this->fail("Expected ImmutableTimelineException on update.");
        } catch (ImmutableTimelineException $e) {
            $this->assertStringContainsString("updates are blocked", strtolower($e->getMessage()));
        }

        // 4. Prevent deletes
        try {
            $timeline->delete();
            $this->fail("Expected ImmutableTimelineException on delete.");
        } catch (ImmutableTimelineException $e) {
            $this->assertStringContainsString("deletions are blocked", strtolower($e->getMessage()));
        }
    }

    /**
     * Test OrderAllocation transition invariants and split/merge behavior.
     */
    public function test_order_allocation_state_transition_invariants(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $alloc = OrderAllocation::create([
            'order_id'        => $order->id,
            'order_item_id'   => $orderItem->id,
            'allocation_type' => 'supplier',
            'source_code'     => 'aliexpress',
            'reserved_qty'    => 5,
            'state'           => 'reserved',
        ]);

        // Fulfill allocation
        $alloc->fulfill(5);
        $this->assertEquals('fulfilled', $alloc->state);
        $this->assertEquals(0, $alloc->reserved_qty);
        $this->assertEquals(5, $alloc->fulfilled_qty);

        // Attempt another action on fulfilled allocation - fails
        try {
            $alloc->cancel(5, "Should fail");
            $this->fail("Expected InvalidStateTransitionException on fulfilled cancel.");
        } catch (InvalidStateTransitionException $e) {
            $this->assertStringContainsString("Cannot cancel allocation in state", $e->getMessage());
        }
    }

    /**
     * Test split and merge allocation behavior and source checks.
     */
    public function test_order_allocation_split_and_merge(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $alloc1 = OrderAllocation::create([
            'order_id'        => $order->id,
            'order_item_id'   => $orderItem->id,
            'allocation_type' => 'supplier',
            'source_code'     => 'aliexpress',
            'reserved_qty'    => 10,
            'state'           => 'reserved',
        ]);

        // Split 4 from 10
        $alloc2 = $alloc1->split(4);
        
        $alloc1->refresh();
        $this->assertEquals(6, $alloc1->reserved_qty);
        $this->assertEquals(4, $alloc2->reserved_qty);
        $this->assertEquals('reserved', $alloc2->state);

        // Merge alloc2 (4) back into alloc1 (6)
        $alloc1->merge($alloc2);
        
        $alloc1->refresh();
        $alloc2->refresh();
        $this->assertEquals(10, $alloc1->reserved_qty);
        $this->assertEquals('canceled', $alloc2->state);
        $this->assertEquals(0, $alloc2->reserved_qty);

        // Incompatible merge check (supplier vs local warehouse)
        $localAlloc = OrderAllocation::create([
            'order_id'        => $order->id,
            'order_item_id'   => $orderItem->id,
            'allocation_type' => 'warehouse',
            'source_code'     => 'warehouse_riyadh',
            'reserved_qty'    => 2,
            'state'           => 'reserved',
        ]);

        try {
            $alloc1->merge($localAlloc);
            $this->fail("Expected InvalidStateTransitionException when merging incompatible sources.");
        } catch (InvalidStateTransitionException $e) {
            $this->assertStringContainsString("Cannot merge allocations from different sources", $e->getMessage());
        }
    }

    /**
     * Test transaction atomicity across multiple aggregates.
     */
    public function test_all_aggregates_rollback_on_transaction_failure(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $ledgerRepository = app(LedgerEntryRepository::class);
        $timelineRepository = app(FinancialTimelineRepository::class);
        $allocationRepository = app(OrderAllocationRepository::class);
        $processedEventRepository = app(ProcessedEventRepository::class);

        try {
            DB::transaction(function () use ($order, $orderItem, $ledgerRepository, $timelineRepository, $allocationRepository, $processedEventRepository) {
                // 1. Create Allocation
                $allocationRepository->create([
                    'order_id'        => $order->id,
                    'order_item_id'   => $orderItem->id,
                    'allocation_type' => 'supplier',
                    'source_code'     => 'aliexpress',
                    'reserved_qty'    => 2,
                    'state'           => 'reserved',
                ]);

                // 2. Create ProcessedEvent
                $processedEventRepository->create([
                    'provider'   => 'aliexpress',
                    'event_id'   => 'evt_tx_failure_test',
                    'event_name' => 'OrderPlaced',
                ]);

                // 3. Append Financial Timeline
                $timeline = FinancialTimeline::appendEvent($order->id, 'customer_paid', 100.00, 'USD');
                $timelineRepository->create($timeline->toArray());

                // 4. Ledger Entry
                $ledgerRepository->create([
                    'order_id'     => $order->id,
                    'account_code' => '1010',
                    'debit'        => 100.00,
                    'credit'       => 0.00,
                ]);

                // Force a query exception or custom exception to trigger rollback
                throw new \RuntimeException("Forced coordinator transaction failure");
            });
        } catch (\RuntimeException $e) {
            $this->assertEquals("Forced coordinator transaction failure", $e->getMessage());
        }

        // Verify that absolutely no database records were saved
        $this->assertEquals(0, OrderAllocation::where('order_id', $order->id)->count());
        $this->assertEquals(0, ProcessedEvent::where('event_id', 'evt_tx_failure_test')->count());
        $this->assertEquals(0, FinancialTimeline::where('order_id', $order->id)->count());
        $this->assertEquals(0, LedgerEntry::where('order_id', $order->id)->count());
    }
}
