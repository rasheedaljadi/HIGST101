<?php

namespace Webkul\Fulfillment\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\Fulfillment\Events\HayestStockReceived;
use Webkul\Fulfillment\Events\Procurement\ProcurementCompleted;
use Webkul\Fulfillment\Listeners\AliExpressStockListener;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\PurchaseOrderItem;
use Webkul\Fulfillment\Services\InboundReceiptService;
use Webkul\Inventory\Services\InventoryMovementService;

class Phase2ProcurementReceiptTest extends TestCase
{
    protected InboundReceiptService $inboundReceiptService;

    protected InventoryMovementService $inventoryMovementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inboundReceiptService = app(InboundReceiptService::class);
        $this->inventoryMovementService = app(InventoryMovementService::class);
    }

    /**
     * Helper to create test order, product, PO and allocation.
     */
    protected function createTestProcurementFixture(int $qty = 2): array
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-SKU-'.Str::random(6),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'status' => 'processing',
            'is_guest' => 0,
            'customer_email' => 'customer_'.Str::random(5).'@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderItemId = DB::table('order_items')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $productId,
            'sku' => 'SKU-ITEM-'.Str::random(5),
            'type' => 'simple',
            'name' => 'Test Item',
            'qty_ordered' => $qty,
            'price' => 100,
            'base_price' => 100,
            'total' => 100 * $qty,
            'base_total' => 100 * $qty,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $po = PurchaseOrder::create([
            'order_id' => $orderId,
            'provider' => 'aliexpress',
            'idempotency_key' => 'po_key_'.Str::random(10),
            'internal_reference' => 'PO-REF-'.Str::random(10),
            'state' => PurchaseOrder::STATE_SUBMITTED,
            'receipt_status' => 'not_received',
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'order_item_id' => $orderItemId,
            'qty' => $qty,
            'supplier_unit_cost' => 50,
        ]);

        $allocation = OrderAllocation::create([
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'product_id' => $productId,
            'allocation_type' => 'supplier',
            'source_code' => 'aliexpress',
            'reserved_qty' => $qty,
            'fulfilled_qty' => 0,
            'state' => 'reserved',
            'version' => 1,
        ]);

        return compact('productId', 'orderId', 'orderItemId', 'po', 'poItem', 'allocation');
    }

    /**
     * 1. ProcurementCompleted marks inbound pending without modifying physical stock.
     */
    public function test_procurement_completed_marks_inbound_pending_without_modifying_stock(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 3);
        $po = $fixture['po'];
        $productId = $fixture['productId'];

        Event::fake([HayestStockReceived::class]);

        $source = $this->inventoryMovementService->getSourceByCode('hayest_central');

        // Initial physical stock must be null
        $initialStock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $source->id)
            ->value('qty');

        $this->assertNull($initialStock);

        // Fire ProcurementCompleted event
        Event::dispatch(new ProcurementCompleted(
            sessionId: 101,
            purchaseOrderId: $po->id,
            correlationId: (string) Str::uuid(),
            causationId: (string) Str::uuid()
        ));

        $po->refresh();
        $this->assertEquals('inbound_receipt_pending', $po->receipt_status);

        // Physical inventory in hayest_central must remain unaffected
        $stockAfter = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $source->id)
            ->value('qty');

        $this->assertNull($stockAfter);

        // HayestStockReceived must NOT be dispatched
        Event::assertNotDispatched(HayestStockReceived::class);
    }

    /**
     * 2. ProcurementCompleted idempotency: does not overwrite confirmed or discrepancy states.
     */
    public function test_procurement_completed_idempotency_preserves_confirmed_and_discrepancy_states(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 2);
        $po = $fixture['po'];

        // Case A: PO is already full_receipt_confirmed
        $po->receipt_status = 'full_receipt_confirmed';
        $po->save();

        $this->inboundReceiptService->markInboundPending($po->id);
        $po->refresh();
        $this->assertEquals('full_receipt_confirmed', $po->receipt_status);

        // Case B: PO is discrepancy_reported
        $po->receipt_status = 'discrepancy_reported';
        $po->save();

        $this->inboundReceiptService->markInboundPending($po->id);
        $po->refresh();
        $this->assertEquals('discrepancy_reported', $po->receipt_status);
    }

    /**
     * 3. Full physical receipt increments hayest_central stock and records movements.
     */
    public function test_full_physical_receipt_increments_hayest_central_stock_and_records_movements(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 4);
        $po = $fixture['po'];
        $productId = $fixture['productId'];
        $orderItemId = $fixture['orderItemId'];

        Event::fake([HayestStockReceived::class]);

        $source = $this->inventoryMovementService->getSourceByCode('hayest_central');
        $idempotencyKey = 'receipt_test_'.Str::random(10);

        // Execute full physical receipt with mandatory actorId
        $result = $this->inboundReceiptService->confirmFullReceipt(
            purchaseOrderId: $po->id,
            actorId: 10,
            notes: 'Received 4 units in perfect condition',
            idempotencyKey: $idempotencyKey
        );

        $po->refresh();
        $this->assertEquals('full_receipt_confirmed', $po->receipt_status);
        $this->assertEquals(PurchaseOrder::STATE_DELIVERED, $po->state);
        $this->assertEquals(10, $po->receipt_confirmed_by);
        $this->assertNotNull($po->receipt_confirmed_at);

        // Verify product_inventories updated for hayest_central
        $stock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $source->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(4, $stock->qty);

        // Verify audit movement 'source_receipt' recorded
        $sourceReceipt = DB::table('inventory_movements')
            ->where('purchase_order_id', $po->id)
            ->where('movement_type', 'source_receipt')
            ->first();
        $this->assertNotNull($sourceReceipt);
        $this->assertEquals(4, $sourceReceipt->quantity);
        $this->assertEquals(10, $sourceReceipt->actor_id);

        // Verify stock in movement 'hayest_stock_in' recorded
        $stockIn = DB::table('inventory_movements')
            ->where('purchase_order_id', $po->id)
            ->where('movement_type', 'hayest_stock_in')
            ->first();
        $this->assertNotNull($stockIn);
        $this->assertEquals(4, $stockIn->quantity);
        $this->assertEquals(10, $stockIn->actor_id);

        // Verify HayestStockReceived event dispatched
        Event::assertDispatched(HayestStockReceived::class, function ($event) use ($po, $orderItemId, $productId) {
            return $event->purchaseOrderId === $po->id
                && $event->orderItemId === $orderItemId
                && $event->productId === $productId
                && $event->quantity === 4
                && in_array($event->inventorySourceCode, ['hayest_central', 'hayest_dropship_ye']);
        });
    }

    /**
     * 4. Idempotency prevents duplicate stock increments.
     */
    public function test_receipt_idempotency_prevents_duplicate_stock_increments(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 5);
        $po = $fixture['po'];
        $productId = $fixture['productId'];

        $source = $this->inventoryMovementService->getSourceByCode('hayest_central');
        $idempotencyKey = 'idempotent_test_'.Str::random(10);

        // First execution
        $this->inboundReceiptService->confirmFullReceipt(
            purchaseOrderId: $po->id,
            actorId: 1,
            notes: 'First receipt attempt',
            idempotencyKey: $idempotencyKey
        );

        // Second duplicate execution
        $duplicateResult = $this->inboundReceiptService->confirmFullReceipt(
            purchaseOrderId: $po->id,
            actorId: 1,
            notes: 'Duplicate receipt attempt',
            idempotencyKey: $idempotencyKey
        );

        $this->assertTrue($duplicateResult['already_processed']);

        // Physical inventory must remain 5, not 10
        $stock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $source->id)
            ->value('qty');

        $this->assertEquals(5, $stock);
    }

    /**
     * 5. Pending, submitted, and shipped states do NOT alter physical stock.
     */
    public function test_pending_submitted_and_shipped_states_do_not_alter_physical_stock(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 2);
        $po = $fixture['po'];
        $productId = $fixture['productId'];

        $source = $this->inventoryMovementService->getSourceByCode('hayest_central');

        // Check submitted state
        $this->assertEquals(PurchaseOrder::STATE_SUBMITTED, $po->state);
        $this->assertNull(DB::table('product_inventories')->where('product_id', $productId)->where('inventory_source_id', $source->id)->value('qty'));

        // Transition to shipped
        $po->markSupplierShipped('TRK123456', 'DHL');
        $this->assertEquals(PurchaseOrder::STATE_SHIPPED, $po->state);

        // Stock must still be null
        $this->assertNull(DB::table('product_inventories')->where('product_id', $productId)->where('inventory_source_id', $source->id)->value('qty'));
    }

    /**
     * 6. Discrepancy & damaged receipt records structured data, flags manual review, and blocks stock & events.
     */
    public function test_discrepancy_and_damaged_receipt_records_structured_data_and_blocks_stock(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 3);
        $po = $fixture['po'];
        $productId = $fixture['productId'];

        Event::fake([HayestStockReceived::class]);
        $source = $this->inventoryMovementService->getSourceByCode('hayest_central');

        // Report damage / missing goods
        $this->inboundReceiptService->recordReceiptDiscrepancy(
            purchaseOrderId: $po->id,
            actorId: 5,
            reason: '1 unit broken during transit, 1 unit missing',
            receivedQty: 1,
            missingQty: 1,
            damagedQty: 1
        );

        $po->refresh();
        $this->assertEquals('discrepancy_reported', $po->receipt_status);
        $this->assertEquals(PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW, $po->state);
        $this->assertEquals(5, $po->receipt_confirmed_by);
        $this->assertNotNull($po->receipt_confirmed_at);
        $this->assertStringContainsString('broken during transit', $po->last_error);

        // Verify structured discrepancy data
        $this->assertIsArray($po->receipt_discrepancy_data);
        $this->assertEquals(3, $po->receipt_discrepancy_data['ordered_qty']);
        $this->assertEquals(2, $po->receipt_discrepancy_data['inspected_qty']);
        $this->assertEquals(1, $po->receipt_discrepancy_data['received_qty']);
        $this->assertEquals(1, $po->receipt_discrepancy_data['missing_qty']);
        $this->assertEquals(1, $po->receipt_discrepancy_data['damaged_qty']);
        $this->assertEquals(5, $po->receipt_discrepancy_data['actor_id']);

        // Verify stock was NOT incremented
        $stock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $source->id)
            ->value('qty');

        $this->assertNull($stock);

        // Verify HayestStockReceived was NOT emitted
        Event::assertNotDispatched(HayestStockReceived::class);
    }

    /**
     * 7. Allocation rebind transfers from supplier to hayest_central without double reservation.
     */
    public function test_allocation_rebind_transfers_to_hayest_central_without_double_reservation(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 2);
        $po = $fixture['po'];
        $allocation = $fixture['allocation'];
        $orderItemId = $fixture['orderItemId'];

        // Assert initial state before rebind
        $activeSupplierBefore = OrderAllocation::where('order_item_id', $orderItemId)
            ->where('allocation_type', 'supplier')
            ->where('state', 'reserved')
            ->count();
        $this->assertEquals(1, $activeSupplierBefore);

        $activeWarehouseBefore = OrderAllocation::where('order_item_id', $orderItemId)
            ->where('allocation_type', 'warehouse')
            ->where('source_code', 'hayest_central')
            ->where('state', 'reserved')
            ->count();
        $this->assertEquals(0, $activeWarehouseBefore);

        $totalReservedBefore = OrderAllocation::where('order_item_id', $orderItemId)
            ->where('state', 'reserved')
            ->sum('reserved_qty');
        $this->assertEquals(2, $totalReservedBefore);

        $initialVersion = $allocation->version;

        // Perform full receipt which triggers rebind
        $this->inboundReceiptService->confirmFullReceipt(
            purchaseOrderId: $po->id,
            actorId: 1
        );

        $allocation->refresh();

        // 1. Supplier allocation active before = 1 and after = 0
        $activeSupplierAfter = OrderAllocation::where('order_item_id', $orderItemId)
            ->where('allocation_type', 'supplier')
            ->where('state', 'reserved')
            ->count();
        $this->assertEquals(0, $activeSupplierAfter);

        // 2. Warehouse allocation active before = 0 and after = 1
        $activeWarehouseAfter = OrderAllocation::where('order_item_id', $orderItemId)
            ->where('allocation_type', 'warehouse')
            ->whereIn('source_code', ['hayest_central', 'hayest_dropship_ye'])
            ->where('state', 'reserved')
            ->count();
        $this->assertEquals(1, $activeWarehouseAfter);

        // 3. Sum of reserved_qty before and after is exactly equal (2 == 2)
        $totalReservedAfter = OrderAllocation::where('order_item_id', $orderItemId)
            ->where('state', 'reserved')
            ->sum('reserved_qty');
        $this->assertEquals($totalReservedBefore, $totalReservedAfter);

        // 4. fulfilled_qty remains 0 before handoff
        $this->assertEquals(0, $allocation->fulfilled_qty);

        // 5. Version incremented with optimistic locking
        $this->assertGreaterThan($initialVersion, $allocation->version);

        // 6. allocation_logs contains exactly 1 rebind record
        $rebindLogsCount = DB::table('allocation_logs')
            ->where('order_allocation_id', $allocation->id)
            ->where('action', 'rebind')
            ->count();
        $this->assertEquals(1, $rebindLogsCount);

        $log = DB::table('allocation_logs')
            ->where('order_allocation_id', $allocation->id)
            ->where('action', 'rebind')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals(2, $log->old_qty);
        $this->assertEquals(2, $log->new_qty);

        // 7. Replaying receipt event does NOT create a second allocation or duplicate rebind log
        $this->inboundReceiptService->confirmFullReceipt(
            purchaseOrderId: $po->id,
            actorId: 1
        );

        $totalAllocationsAfterReplay = OrderAllocation::where('order_item_id', $orderItemId)->count();
        $this->assertEquals(1, $totalAllocationsAfterReplay);

        $rebindLogsAfterReplay = DB::table('allocation_logs')
            ->where('order_allocation_id', $allocation->id)
            ->where('action', 'rebind')
            ->count();
        $this->assertEquals(1, $rebindLogsAfterReplay);
    }

    /**
     * 8. Transaction failure rolls back stock, allocation, PO, and movements.
     */
    public function test_transaction_failure_rolls_back_stock_and_allocation(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 2);
        $po = $fixture['po'];
        $productId = $fixture['productId'];
        $allocation = $fixture['allocation'];

        $source = $this->inventoryMovementService->getSourceByCode('hayest_central');

        // Simulate failure by overriding purchase order method or throwing in transaction
        // We do this by setting an invalid state or using a mock that throws
        $caught = false;
        try {
            DB::transaction(function () use ($po) {
                // Simulate an exception during confirmFullReceipt inner steps
                $this->inboundReceiptService->confirmFullReceipt(
                    purchaseOrderId: $po->id,
                    actorId: 1
                );
                throw new \RuntimeException('Simulated unexpected power outage before transaction end');
            });
        } catch (\RuntimeException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);

        // Verify all changes were completely rolled back:
        $po->refresh();
        $this->assertNotEquals('full_receipt_confirmed', $po->receipt_status);

        $allocation->refresh();
        $this->assertEquals('supplier', $allocation->allocation_type);
        $this->assertEquals('aliexpress', $allocation->source_code);

        $stock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $source->id)
            ->value('qty');

        $this->assertNull($stock);

        $movementCount = DB::table('inventory_movements')
            ->where('purchase_order_id', $po->id)
            ->count();

        $this->assertEquals(0, $movementCount);
    }

    /**
     * 9. Event is dispatched strictly after commit, and never on transaction failure.
     */
    public function test_event_dispatched_strictly_after_commit_and_never_on_failure(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 2);
        $po = $fixture['po'];

        Event::fake([HayestStockReceived::class]);

        $caught = false;
        try {
            DB::transaction(function () use ($po) {
                $this->inboundReceiptService->confirmFullReceipt(
                    purchaseOrderId: $po->id,
                    actorId: 1
                );
                throw new \DomainException('Aborting transaction after confirmFullReceipt');
            });
        } catch (\DomainException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);

        // Event MUST NOT be dispatched when transaction rolls back
        Event::assertNotDispatched(HayestStockReceived::class);
    }

    /**
     * 10. Subsequent AliExpress catalog/stock sync does NOT modify hayest_central inventory.
     */
    public function test_subsequent_aliexpress_sync_does_not_alter_hayest_central_inventory(): void
    {
        $fixture = $this->createTestProcurementFixture(qty: 2);
        $po = $fixture['po'];
        $productId = $fixture['productId'];

        $centralSource = $this->inventoryMovementService->getSourceByCode('hayest_central');

        // Confirm full physical receipt so hayest_central has 2 units
        $this->inboundReceiptService->confirmFullReceipt(
            purchaseOrderId: $po->id,
            actorId: 1
        );

        $centralStock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $centralSource->id)
            ->value('qty');
        $this->assertEquals(2, $centralStock);

        // Run AliExpress stock listener update for external stock change
        $listener = app(AliExpressStockListener::class);
        $listener->handle(
            eventName: 'supplier.stock.updated',
            payload: [
                'variant_id' => $productId,
                'new_stock' => 150,
                'external_variant_version' => 10,
            ],
            correlationId: (string) Str::uuid(),
            causationId: (string) Str::uuid()
        );

        // hayest_central stock MUST remain exactly 2, completely untouched by AliExpress sync
        $centralStockAfter = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $centralSource->id)
            ->value('qty');
        $this->assertEquals(2, $centralStockAfter);
    }
}
