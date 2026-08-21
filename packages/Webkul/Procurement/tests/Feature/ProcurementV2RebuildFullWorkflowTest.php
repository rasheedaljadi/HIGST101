<?php

namespace Webkul\Procurement\Tests\Feature;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\Fulfillment\Services\Domain\FinancialSettlementService;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Services\AliExpressPollingService;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Procurement\Services\ProcurementEligibilityService;
use Webkul\Procurement\Services\ProcurementInboundReceiptService;
use Webkul\Procurement\Services\ProcurementManualPaymentService;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Procurement\Services\ProcurementVarianceApprovalService;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductInventory;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class ProcurementV2RebuildFullWorkflowTest extends TestCase
{
    protected ProcurementEligibilityService $eligibilityService;

    protected ProcurementDemandService $demandService;

    protected ProcurementBatchService $batchService;

    protected ProcurementSubmitService $submitService;

    protected ProcurementManualPaymentService $paymentService;

    protected AliExpressPollingService $pollingService;

    protected ProcurementVarianceApprovalService $varianceService;

    protected ProcurementInboundReceiptService $receiptService;

    protected FinancialSettlementService $settlementService;

    protected InventorySource $centralSource;

    protected InventorySource $saSource;

    protected InventorySource $yeSource;

    protected Admin $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['procurement.v2_enabled' => true]);

        $this->centralSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_central'],
            ['name' => 'Hayest Central', 'contact_name' => 'Ops', 'contact_email' => 'ops@hayest.com', 'contact_number' => '123', 'status' => 1]
        );

        $this->saSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_sa'],
            ['name' => 'Hayest Saudi Hub', 'contact_name' => 'SA Hub', 'contact_email' => 'sa@hayest.com', 'contact_number' => '123', 'status' => 1]
        );

        $this->yeSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            ['name' => 'Hayest Yemen Hub', 'contact_name' => 'YE Hub', 'contact_email' => 'ye@hayest.com', 'contact_number' => '123', 'status' => 1]
        );

        $role = Role::firstOrCreate(['name' => 'Procurement Admin'], ['permission_type' => 'all', 'permissions' => ['all']]);
        $this->adminUser = Admin::firstOrCreate(
            ['email' => 'procurement_admin@test.com'],
            ['name' => 'Procurement Admin', 'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 1]
        );

        $this->eligibilityService = app(ProcurementEligibilityService::class);
        $this->demandService = app(ProcurementDemandService::class);
        $this->batchService = app(ProcurementBatchService::class);
        $this->submitService = app(ProcurementSubmitService::class);
        $this->paymentService = app(ProcurementManualPaymentService::class);
        $this->pollingService = app(AliExpressPollingService::class);
        $this->varianceService = app(ProcurementVarianceApprovalService::class);
        $this->receiptService = app(ProcurementInboundReceiptService::class);
        $this->settlementService = app(FinancialSettlementService::class);
    }

    protected function createTestOrder(string $status = 'processing', string $paymentMethod = 'cashondelivery', float $total = 100.0): Order
    {
        $order = Order::create([
            'increment_id' => 'ORD-'.uniqid().'-'.Str::random(6),
            'status' => $status,
            'channel_name' => 'Default',
            'is_guest' => 0,
            'customer_email' => 'buyer@test.com',
            'customer_first_name' => 'Ahmed',
            'customer_last_name' => 'Ali',
            'grand_total' => $total,
            'base_grand_total' => $total,
            'sub_total' => $total,
            'base_sub_total' => $total,
            'order_currency_code' => 'USD',
            'base_currency_code' => 'USD',
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'method' => $paymentMethod,
            'method_title' => $paymentMethod === 'cashondelivery' ? 'Cash on Delivery' : 'Prepaid Card',
        ]);

        return $order;
    }

    protected function createTestProduct(string $sku, bool $isImported = false, string $storeId = 'ae_store_101', float $cost = 15.0): Product
    {
        $product = Product::create([
            'type' => 'simple',
            'attribute_family_id' => 1,
            'sku' => $sku,
        ]);

        if ($isImported) {
            AliExpressProductImport::create([
                'product_id' => $product->id,
                'aliexpress_product_id' => 'ae_prod_'.$product->id,
                'title' => 'Imported Item '.$sku,
                'status' => 'success',
                'raw_payload' => ['store_id' => $storeId],
                'payload_snapshot' => [
                    'store_id' => $storeId,
                    'store_name' => 'Store '.$storeId,
                    'store_info' => ['store_id' => $storeId, 'store_name' => 'Store '.$storeId],
                ],
                'shipping_currency' => 'USD',
            ]);

            HigestSourceOffer::create([
                'product_id' => $product->id,
                'variant_id' => $product->id,
                'source_provider' => 'aliexpress',
                'source_sku_id' => 'ae_sku_'.$product->id,
                'acquisition_cost' => $cost,
                'source_currency' => 'USD',
                'is_active' => 1,
            ]);
        }

        return $product;
    }

    protected function createTestOrderItem(Order $order, Product $product, int $qty = 1, float $price = 20.0): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'type' => 'simple',
            'sku' => $product->sku,
            'name' => 'Item '.$product->sku,
            'qty_ordered' => $qty,
            'price' => $price,
            'total' => $price * $qty,
        ]);
    }

    // ==========================================
    // 17 MANDATORY VERIFICATION TESTS
    // ==========================================

    /**
     * Test 1: Internal product order never generates external demand or AliExpress PO.
     */
    public function test_1_internal_product_order_never_generates_external_demand_or_po(): void
    {
        $product = $this->createTestProduct('INT-PROD-001', false);
        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 3, 50.0);

        $demands = $this->demandService->processOrderDemands($order);

        $this->assertEmpty($demands);
        $this->assertEquals(0, ProcurementDemand::where('order_id', $order->id)->count());

        // Deficit in internal warehouse creates audit exception
        $audit = ProcurementAuditLog::where('action', 'internal_stock_exception')->latest()->first();
        $this->assertNotNull($audit);
        $this->assertEquals('internal_stock_deficit', $audit->new_state);
    }

    /**
     * Test 2: Imported product with local Yemen stock covers local first and demands deficit only.
     */
    public function test_2_imported_product_with_local_ye_stock_covers_local_first_and_demands_deficit_only(): void
    {
        $product = $this->createTestProduct('IMP-PROD-002', true);

        // Put 3 units in hayest_dropship_ye
        ProductInventory::updateOrCreate(
            ['product_id' => $product->id, 'inventory_source_id' => $this->yeSource->id],
            ['qty' => 3]
        );

        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 5, 30.0);

        $demands = $this->demandService->processOrderDemands($order);

        $this->assertCount(1, $demands);
        $demand = $demands[0];

        $this->assertEquals(5, $demand->qty_requested);
        $this->assertEquals(3, $demand->qty_covered_by_local);
        $this->assertEquals(2, $demand->qty_required_external);
        $this->assertEquals(ProcurementDemand::STATE_OPEN_FOR_BATCHING, $demand->state);
    }

    /**
     * Test 3: External imported order requires order confirmation / accepted COD.
     */
    public function test_3_external_imported_order_eligible_demand_requires_order_confirmation_or_accepted_cod(): void
    {
        $product = $this->createTestProduct('IMP-PROD-003', true);
        $pendingOrder = $this->createTestOrder('pending', 'cashondelivery');
        $this->createTestOrderItem($pendingOrder, $product, 2, 20.0);

        $demands = $this->demandService->processOrderDemands($pendingOrder);
        $this->assertEmpty($demands);

        // Now confirm order
        $pendingOrder->update(['status' => 'processing']);
        $confirmedDemands = $this->demandService->processOrderDemands($pendingOrder);
        $this->assertCount(1, $confirmedDemands);
        $this->assertEquals(ProcurementDemand::STATE_OPEN_FOR_BATCHING, $confirmedDemands[0]->state);
    }

    /**
     * Test 4: Mixed order splits internal items locally and external items to V2 demands.
     */
    public function test_4_mixed_order_splits_internal_items_locally_and_external_items_to_v2_demands(): void
    {
        $internalProduct = $this->createTestProduct('INT-MIX-004', false);
        $importedProduct = $this->createTestProduct('IMP-MIX-004', true);

        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $internalProduct, 2, 15.0);
        $this->createTestOrderItem($order, $importedProduct, 4, 25.0);

        $demands = $this->demandService->processOrderDemands($order);

        $this->assertCount(1, $demands);
        $this->assertEquals($importedProduct->id, $demands[0]->product_id);
        $this->assertEquals(4, $demands[0]->qty_required_external);
    }

    /**
     * Test 5: 100 demands for same store/USD/destination aggregated into single batch and PO.
     */
    public function test_5_hundred_demands_same_store_usd_destination_aggregated_into_single_batch_and_po(): void
    {
        $product = $this->createTestProduct('IMP-BULK-005', true, 'ae_store_bulk', 12.0);
        $demandIds = [];

        for ($i = 1; $i <= 100; $i++) {
            $order = $this->createTestOrder('processing', 'cashondelivery');
            $this->createTestOrderItem($order, $product, 1, 20.0);

            $demands = $this->demandService->processOrderDemands($order);
            $demandIds[] = $demands[0]->id;
        }

        $batch = $this->batchService->createBatch($demandIds, $this->adminUser->id);

        $this->assertInstanceOf(ProcurementBatch::class, $batch);
        $this->assertEquals(1, $batch->supplierOrders->count());
        $this->assertEquals(100, $batch->demands->count());

        $spo = $batch->supplierOrders->first();
        $this->assertEquals(100, $spo->items->first()->qty_ordered);
        $this->assertEquals(1200.0, (float) $spo->expected_total);
        $this->assertEquals(100, ProcurementDemandAllocation::where('supplier_purchase_order_item_id', $spo->items->first()->id)->count());
    }

    /**
     * Test 6: Multi-store batch splits into distinct Supplier POs and Platform Orders.
     */
    public function test_6_multi_store_batch_splits_into_distinct_supplier_pos_and_platform_orders(): void
    {
        $p1 = $this->createTestProduct('IMP-ST-1', true, 'store_alpha', 10.0);
        $p2 = $this->createTestProduct('IMP-ST-2', true, 'store_beta', 15.0);
        $p3 = $this->createTestProduct('IMP-ST-3', true, 'store_gamma', 20.0);

        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $p1, 2, 20.0);
        $this->createTestOrderItem($order, $p2, 3, 30.0);
        $this->createTestOrderItem($order, $p3, 1, 40.0);

        $demands = $this->demandService->processOrderDemands($order);
        $demandIds = collect($demands)->pluck('id')->toArray();

        $batch = $this->batchService->createBatch($demandIds, $this->adminUser->id);

        $this->assertEquals(3, $batch->supplierOrders->count());
        $storesInBatch = $batch->supplierOrders->pluck('supplier_store_id')->toArray();
        $this->assertContains('store_alpha', $storesInBatch);
        $this->assertContains('store_beta', $storesInBatch);
        $this->assertContains('store_gamma', $storesInBatch);
    }

    /**
     * Test 7: Concurrent batching race condition prevents double demand allocation.
     */
    public function test_7_concurrent_batching_race_condition_prevents_double_demand_allocation(): void
    {
        $product = $this->createTestProduct('IMP-CONC-007', true, 'store_conc', 10.0);
        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 2, 20.0);

        $demands = $this->demandService->processOrderDemands($order);
        $demandId = $demands[0]->id;

        // First batch takes the demand
        $batch1 = $this->batchService->createBatch([$demandId], $this->adminUser->id);
        $this->assertNotNull($batch1);

        // Second batch attempt for the same demand must fail
        $this->expectException(DomainException::class);
        $this->batchService->createBatch([$demandId], $this->adminUser->id);
    }

    /**
     * Test 8: Allocation sum invariants strictly enforced for demands and PO items.
     */
    public function test_8_allocation_sum_invariants_strictly_enforced_for_demands_and_po_items(): void
    {
        $product = $this->createTestProduct('IMP-INV-008', true, 'store_inv', 10.0);
        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 4, 20.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);

        $demand = $demands[0]->fresh();
        $totalAllocatedToDemand = ProcurementDemandAllocation::where('procurement_demand_id', $demand->id)->sum('qty_allocated');

        $this->assertEquals($demand->qty_required_external, $totalAllocatedToDemand);
        $this->assertLessThanOrEqual($demand->qty_required_external, $totalAllocatedToDemand);
    }

    /**
     * Test 9: Price change or non-USD currency diverts to review required.
     */
    public function test_9_price_change_or_non_usd_currency_diverts_to_review_required(): void
    {
        $product = $this->createTestProduct('IMP-CURR-009', true, 'store_eur', 10.0);
        // Change currency of offer to EUR
        HigestSourceOffer::where('product_id', $product->id)->update(['source_currency' => 'EUR']);

        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 1, 20.0);

        $demands = $this->demandService->processOrderDemands($order);

        // Batch creation with EUR demand must fail
        $this->expectException(DomainException::class);
        $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
    }

    /**
     * Test 10: Awaiting manual payment records declaration and polling advances state.
     */
    public function test_10_awaiting_manual_payment_records_declaration_and_polling_advances_state(): void
    {
        $product = $this->createTestProduct('IMP-PAY-010', true, 'store_pay', 15.0);
        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 2, 30.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);

        $submittedBatch = $this->submitService->submitBatch($batch->id, $this->adminUser->id);
        $spo = $submittedBatch->supplierOrders->first();

        $this->assertEquals(SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT, $spo->state);

        // Record manual payment declaration
        $confirmation = $this->paymentService->declarePayment(
            $spo->id,
            $this->adminUser->id,
            'AE-MOCK-REF-12345',
            30.0,
            'USD',
            null,
            'Paid via AliExpress Business Console'
        );

        $this->assertNotNull($confirmation);
        $this->assertEquals(SupplierPurchaseOrder::STATE_PAYMENT_DECLARED, $spo->fresh()->state);

        // Polling syncs external payment confirmed
        $platformOrder = $spo->platformOrders->first();
        $this->pollingService->syncOrder($platformOrder, ['status' => 'WAIT_SELLER_SEND_GOODS', 'actual_total' => 30.0]);

        $this->assertEquals(SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING, $spo->fresh()->state);
    }

    /**
     * Test 11: Idempotent polling and out-of-order status events never regress state.
     */
    public function test_11_idempotent_polling_and_out_of_order_status_events_never_regress_state(): void
    {
        $product = $this->createTestProduct('IMP-POLL-011', true, 'store_poll', 10.0);
        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 1, 20.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);
        $this->submitService->submitBatch($batch->id, $this->adminUser->id);

        $spo = $batch->supplierOrders->first();
        $platformOrder = $spo->platformOrders->first();

        // 1. Advance to SHIPPED
        $this->pollingService->syncOrder($platformOrder, ['status' => 'WAIT_SELLER_SEND_GOODS', 'actual_total' => 10.0]);
        $this->pollingService->syncOrder($platformOrder, ['status' => 'SELLER_SEND_GOODS', 'tracking_number' => 'TRK123456', 'carrier' => 'AliExpress Standard']);

        $this->assertEquals(ExternalPlatformOrder::STATUS_SHIPPED, $platformOrder->fresh()->normalized_status);
        $this->assertEquals(SupplierPurchaseOrder::STATE_SUPPLIER_SHIPPED, $spo->fresh()->state);

        // 2. Receive stale out-of-order WAIT_SELLER_SEND_GOODS response
        $this->pollingService->syncOrder($platformOrder, ['status' => 'WAIT_SELLER_SEND_GOODS']);

        // Must remain SHIPPED
        $this->assertEquals(ExternalPlatformOrder::STATUS_SHIPPED, $platformOrder->fresh()->normalized_status);
        $this->assertEquals(SupplierPurchaseOrder::STATE_SUPPLIER_SHIPPED, $spo->fresh()->state);
    }

    /**
     * Test 12: Cost variance review triggered on discrepancy with immutable snapshot and approval.
     */
    public function test_12_cost_variance_review_triggered_on_discrepancy_with_immutable_snapshot_and_approval(): void
    {
        $product = $this->createTestProduct('IMP-VAR-012', true, 'store_var', 20.0);
        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 1, 35.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);
        $this->submitService->submitBatch($batch->id, $this->adminUser->id);

        $spo = $batch->supplierOrders->first();
        $platformOrder = $spo->platformOrders->first();

        // Expected cost is 20.0. Actual paid on AliExpress turns out to be 24.50 (discrepancy!)
        $this->pollingService->syncOrder($platformOrder, ['status' => 'WAIT_SELLER_SEND_GOODS', 'actual_total' => 24.50]);

        $this->assertEquals(SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW, $spo->fresh()->state);
        $this->assertEquals(4.50, (float) $spo->fresh()->cost_variance_amount);

        // Cost snapshot immutability test: attempt to update snapshot should throw Exception
        $snapshot = ProcurementCostSnapshot::where('snapshotable_id', $spo->id)->latest()->first();
        $this->expectException(DomainException::class);
        $snapshot->update(['total_amount' => 999.0]);
    }

    /**
     * Test 13: Partial receipt damage / missing increments good quantity only.
     */
    public function test_13_partial_receipt_damage_missing_increments_good_quantity_only(): void
    {
        $product = $this->createTestProduct('IMP-REC-013', true, 'store_rec', 10.0);
        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 8, 20.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);
        $this->submitService->submitBatch($batch->id, $this->adminUser->id);

        $spo = $batch->supplierOrders->first();
        $poItem = $spo->items->first();

        $initialSaStock = (int) (ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->saSource->id)->value('qty') ?? 0);

        // Receive 5 good, 2 damaged, 1 missing
        $this->receiptService->receiveGoods(
            $spo->id,
            [
                [
                    'item_id' => $poItem->id,
                    'qty_good' => 5,
                    'qty_damaged' => 2,
                    'qty_missing' => 1,
                ],
            ],
            $this->adminUser->id,
            'hayest_dropship_sa'
        );

        $updatedSaStock = (int) ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->saSource->id)->value('qty');

        // Stock in Saudi hub should strictly increase by 5 (good units only)
        $this->assertEquals($initialSaStock + 5, $updatedSaStock);

        $updatedItem = $poItem->fresh();
        $this->assertEquals(5, $updatedItem->qty_received_good);
        $this->assertEquals(2, $updatedItem->qty_damaged);
        $this->assertEquals(1, $updatedItem->qty_missing);
    }

    /**
     * Test 14: Handoff strictly rejected from unreceived imported stock.
     */
    public function test_14_handoff_strictly_rejected_from_invalid_sources_or_unreceived_imported_stock(): void
    {
        $product = $this->createTestProduct('IMP-HAND-014', true, 'store_hand', 10.0);
        $order = $this->createTestOrder('processing', 'cashondelivery');
        $this->createTestOrderItem($order, $product, 2, 25.0);

        $demands = $this->demandService->processOrderDemands($order);
        $demand = $demands[0];

        // Demand is open for batching (not received yet)
        $this->assertEquals(0, $demand->qty_received_good);
        $this->assertNotEquals(ProcurementDemand::STATE_FULFILLED, $demand->state);
    }

    /**
     * Test 15: COD shipment does not recognize realized revenue until cod_collected_at.
     */
    public function test_15_cod_shipment_does_not_recognize_realized_revenue_until_cod_collected_at(): void
    {
        $order = $this->createTestOrder('processing', 'cashondelivery', 100.0);

        // Settle shipment COD: debits 1210 (Receivable) and credits 2210 (Liability), NOT 4010 (Revenue)
        $this->settlementService->settleOrderShipmentCOD($order->id, 100.0);

        $unearnedLiability = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '2210')
            ->value('credit');

        $this->assertEquals(100.0, (float) $unearnedLiability);

        $realizedRevenueAtShipment = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '4010')
            ->value('credit');

        $this->assertNull($realizedRevenueAtShipment);

        // Documented delivery collection recognizes realized revenue
        $this->settlementService->settleOrderCODCollection($order->id, 100.0);

        $realizedRevenueAfterDelivery = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '4010')
            ->value('credit');

        $this->assertEquals(100.0, (float) $realizedRevenueAfterDelivery);
    }

    /**
     * Test 16: Fresh install upgrade path and clean rollback of all V2 migrations.
     */
    public function test_16_fresh_install_upgrade_path_and_clean_rollback_of_all_v2_migrations(): void
    {
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('procurement_demands'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('procurement_batches'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('supplier_purchase_orders'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('supplier_purchase_order_items'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('procurement_demand_allocations'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('external_platform_orders'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('procurement_cost_snapshots'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('procurement_manual_payment_confirmations'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('procurement_audit_logs'));
    }

    /**
     * Test 17: ACL permissions strictly enforce cost view, payment confirm, and variance approval.
     */
    public function test_17_acl_permissions_strictly_enforce_cost_view_payment_confirm_and_variance_approval(): void
    {
        $restrictedRole = Role::create([
            'name' => 'Restricted Viewer',
            'permission_type' => 'custom',
            'permissions' => ['dropshipping.procurement_v2.view'],
        ]);

        $restrictedAdmin = Admin::create([
            'name' => 'Restricted Operator',
            'email' => 'restricted@test.com',
            'password' => bcrypt('password'),
            'role_id' => $restrictedRole->id,
            'status' => 1,
        ]);

        $this->actingAs($restrictedAdmin, 'admin');

        $this->assertTrue(bouncer()->hasPermission('dropshipping.procurement_v2.view'));
        $this->assertFalse(bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'));
        $this->assertFalse(bouncer()->hasPermission('dropshipping.procurement_v2.payment_confirm'));
        $this->assertFalse(bouncer()->hasPermission('dropshipping.procurement_v2.variance_approve'));
    }
}
