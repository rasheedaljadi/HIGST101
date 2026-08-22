<?php

namespace Webkul\Procurement\Tests\Feature;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use Tests\TestCase;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\ProcurementManualPaymentConfirmation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\AliExpressPollingService;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementDemandService;
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

class ProcurementV2ControlledLocalUatTest extends TestCase
{
    protected InventorySource $internalYeSource;

    protected InventorySource $dropshipYeSource;

    protected InventorySource $dropshipSaSource;

    // 5 Dedicated UAT Admins
    protected Admin $operatorAdmin;

    protected Admin $approverAdmin;

    protected Admin $financeAdmin;

    protected Admin $receiverAdmin;

    protected Admin $viewerAdmin;

    // Services
    protected ProcurementDemandService $demandService;

    protected ProcurementBatchService $batchService;

    protected ProcurementSubmitService $submitService;

    protected ProcurementManualPaymentService $manualPaymentService;

    protected ProcurementVarianceApprovalService $varianceService;

    protected ProcurementInboundReceiptService $receiptService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['procurement.v2_enabled' => true]);
        config(['procurement.polling.enabled' => true]);

        $this->demandService = app(ProcurementDemandService::class);
        $this->batchService = app(ProcurementBatchService::class);
        $this->submitService = app(ProcurementSubmitService::class);
        $this->manualPaymentService = app(ProcurementManualPaymentService::class);
        $this->varianceService = app(ProcurementVarianceApprovalService::class);
        $this->receiptService = app(ProcurementInboundReceiptService::class);

        $this->setupInventorySources();
        $this->setupUatRolesAndUsers();
    }

    protected function setupInventorySources(): void
    {
        $this->internalYeSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_internal_ye'],
            ['name' => 'Hayest Internal Yemen', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        $this->dropshipYeSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            ['name' => 'Hayest Dropship Yemen', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        $this->dropshipSaSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_sa'],
            ['name' => 'Hayest Dropship Saudi Hub', 'country' => 'SA', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );
    }

    protected function setupUatRolesAndUsers(): void
    {
        // 1. Operator: view, batch_create, submit
        $opRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Operator Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.batch_create',
                'dropshipping.procurement_v2.submit',
            ]]
        );
        $this->operatorAdmin = Admin::updateOrCreate(
            ['email' => 'uat_procurement_operator@test.local'],
            ['name' => 'UAT Operator', 'password' => bcrypt('password'), 'role_id' => $opRole->id, 'status' => 1]
        );

        // 2. Approver: view, cost_view, batch_approve, variance_approve
        $appRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Approver Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.cost_view',
                'dropshipping.procurement_v2.batch_approve',
                'dropshipping.procurement_v2.variance_approve',
            ]]
        );
        $this->approverAdmin = Admin::updateOrCreate(
            ['email' => 'uat_procurement_approver@test.local'],
            ['name' => 'UAT Approver', 'password' => bcrypt('password'), 'role_id' => $appRole->id, 'status' => 1]
        );

        // 3. Finance: view, cost_view, payment_confirm, reports_view
        $finRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Finance Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.cost_view',
                'dropshipping.procurement_v2.payment_confirm',
                'dropshipping.procurement_v2.reports_view',
            ]]
        );
        $this->financeAdmin = Admin::updateOrCreate(
            ['email' => 'uat_procurement_finance@test.local'],
            ['name' => 'UAT Finance', 'password' => bcrypt('password'), 'role_id' => $finRole->id, 'status' => 1]
        );

        // 4. Receiver: view, exception_handle
        $recRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Receiver Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.exception_handle',
            ]]
        );
        $this->receiverAdmin = Admin::updateOrCreate(
            ['email' => 'uat_procurement_receiver@test.local'],
            ['name' => 'UAT Receiver', 'password' => bcrypt('password'), 'role_id' => $recRole->id, 'status' => 1]
        );

        // 5. Viewer: view only
        $viewRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Viewer Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
            ]]
        );
        $this->viewerAdmin = Admin::updateOrCreate(
            ['email' => 'uat_procurement_viewer@test.local'],
            ['name' => 'UAT Viewer', 'password' => bcrypt('password'), 'role_id' => $viewRole->id, 'status' => 1]
        );
    }

    protected function createUatOrder(string $incrementId, array $itemsData, string $paymentMethod = 'cashondelivery'): Order
    {
        $order = Order::create([
            'increment_id' => $incrementId,
            'status' => 'processing',
            'channel_name' => 'Default',
            'is_guest' => 0,
            'customer_email' => 'uat_buyer@test.local',
            'customer_first_name' => 'UAT',
            'customer_last_name' => 'Buyer',
            'grand_total' => 100.0,
            'base_grand_total' => 100.0,
            'sub_total' => 100.0,
            'base_sub_total' => 100.0,
            'order_currency_code' => 'USD',
            'base_currency_code' => 'USD',
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'method' => $paymentMethod,
            'method_title' => strtoupper($paymentMethod),
        ]);

        foreach ($itemsData as $data) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $data['product']->id,
                'product_type' => Product::class,
                'name' => 'Item '.$data['product']->sku,
                'sku' => $data['product']->sku,
                'qty_ordered' => $data['qty'],
                'price' => $data['price'] ?? 20.0,
                'base_price' => $data['price'] ?? 20.0,
                'total' => ($data['price'] ?? 20.0) * $data['qty'],
                'base_total' => ($data['price'] ?? 20.0) * $data['qty'],
                'additional' => $data['additional'] ?? [],
            ]);
        }

        return $order;
    }

    protected function createUatProduct(
        string $sku,
        bool $isImported = true,
        ?string $storeId = 'store_uat_alpha',
        float $cost = 10.0,
        int $localYeStock = 0
    ): Product {
        $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => $sku]);

        if ($isImported) {
            $payload = [];
            if ($storeId !== null) {
                $payload = [
                    'store_id' => $storeId,
                    'store_name' => 'UAT Store '.$storeId,
                    'store_info' => ['store_id' => $storeId, 'store_name' => 'UAT Store '.$storeId],
                ];
            }

            AliExpressProductImport::create([
                'product_id' => $product->id,
                'aliexpress_product_id' => 'ae_'.$product->id,
                'title' => 'Imported '.$sku,
                'status' => 'success',
                'raw_payload' => $payload,
                'payload_snapshot' => $payload,
                'shipping_currency' => 'USD',
            ]);

            HigestSourceOffer::create([
                'product_id' => $product->id,
                'variant_id' => $product->id,
                'source_provider' => 'aliexpress',
                'source_sku_id' => 'sku_'.$product->id,
                'acquisition_cost' => $cost,
                'source_currency' => 'USD',
                'is_active' => 1,
            ]);

            if ($localYeStock > 0) {
                ProductInventory::updateOrCreate(
                    ['product_id' => $product->id, 'inventory_source_id' => $this->dropshipYeSource->id, 'vendor_id' => 0],
                    ['qty' => $localYeStock]
                );
            }
        } else {
            ProductInventory::updateOrCreate(
                ['product_id' => $product->id, 'inventory_source_id' => $this->internalYeSource->id, 'vendor_id' => 0],
                ['qty' => 50]
            );
        }

        return $product;
    }

    /**
     * =========================================================================
     * STAGE A: Intake & Classification (Scenarios 1, 2, 3, 6, 7, 8)
     * =========================================================================
     */
    public function test_stage_a_intake_and_classification_across_all_scenarios(): void
    {
        // 1. Scenario 1: Pure Internal Order
        $pInt = $this->createUatProduct('UAT-POV2-SKU-INT-01', isImported: false);
        $ordInt = $this->createUatOrder('UAT-POV2-ORD-01-INT', [['product' => $pInt, 'qty' => 2]]);
        $demandsInt = $this->demandService->processOrderDemands($ordInt);
        $this->assertCount(0, $demandsInt, 'Scenario 1: Internal product must produce 0 procurement demands.');

        // 2. Scenario 2: Imported product covered by local stock in hayest_dropship_ye
        $pLoc = $this->createUatProduct('UAT-POV2-SKU-LOC-02', isImported: true, storeId: 'store_uat_alpha', cost: 10.0, localYeStock: 5);
        $ordLoc = $this->createUatOrder('UAT-POV2-ORD-02-LOC', [['product' => $pLoc, 'qty' => 2]]);
        $demandsLoc = $this->demandService->processOrderDemands($ordLoc);
        $this->assertCount(1, $demandsLoc);
        $this->assertEquals(2, $demandsLoc[0]->qty_covered_by_local);
        $this->assertEquals(0, $demandsLoc[0]->qty_required_external);
        $this->assertEquals(ProcurementDemand::STATE_LOCALLY_COVERED, $demandsLoc[0]->state);

        $allocLoc = OrderAllocation::where('order_id', $ordLoc->id)->where('source_code', 'hayest_dropship_ye')->first();
        $this->assertNotNull($allocLoc);
        $this->assertEquals(2, $allocLoc->reserved_qty);

        // 3. Scenario 3: Imported product with deficit
        $pDef = $this->createUatProduct('UAT-POV2-SKU-DEF-03', isImported: true, storeId: 'store_uat_alpha', cost: 12.0, localYeStock: 1);
        $ordDef = $this->createUatOrder('UAT-POV2-ORD-03-DEF', [['product' => $pDef, 'qty' => 3]]);
        $demandsDef = $this->demandService->processOrderDemands($ordDef);
        $this->assertCount(1, $demandsDef);
        $this->assertEquals(1, $demandsDef[0]->qty_covered_by_local);
        $this->assertEquals(2, $demandsDef[0]->qty_required_external);
        $this->assertEquals(ProcurementDemand::STATE_OPEN_FOR_BATCHING, $demandsDef[0]->state);

        // 4. Scenario 6: Mixed Order
        $ordMix = $this->createUatOrder('UAT-POV2-ORD-06-MIX', [
            ['product' => $pInt, 'qty' => 1],
            ['product' => $pDef, 'qty' => 2],
        ]);
        $demandsMix = $this->demandService->processOrderDemands($ordMix);
        $this->assertCount(1, $demandsMix, 'Scenario 6: Only imported item produces demand.');
        $this->assertEquals(2, $demandsMix[0]->qty_required_external);

        // 5. Scenario 7: Missing Store Metadata -> supplier_exception
        $pMissing = $this->createUatProduct('UAT-POV2-SKU-MIS-07', isImported: true, storeId: null);
        $ordMissing = $this->createUatOrder('UAT-POV2-ORD-07-NOSTORE', [['product' => $pMissing, 'qty' => 1]]);
        $demandsMissing = $this->demandService->processOrderDemands($ordMissing);
        $this->assertCount(1, $demandsMissing);
        $this->assertEquals(ProcurementDemand::STATE_SUPPLIER_EXCEPTION, $demandsMissing[0]->state);
        $this->assertEquals('MISSING_SUPPLIER_STORE_METADATA', $demandsMissing[0]->eligibility_snapshot['exception_reason']);

        // 6. Scenario 8: Conflicting Metadata -> supplier_exception
        $pConflict = $this->createUatProduct('UAT-POV2-SKU-CNF-08', isImported: true, storeId: 'store_alpha');
        $ordConflict = $this->createUatOrder('UAT-POV2-ORD-08-CONFLICT', [
            ['product' => $pConflict, 'qty' => 1, 'additional' => ['supplier_store_id' => 'store_beta']],
        ]);
        $demandsConflict = $this->demandService->processOrderDemands($ordConflict);
        $this->assertCount(1, $demandsConflict);
        $this->assertEquals(ProcurementDemand::STATE_SUPPLIER_EXCEPTION, $demandsConflict[0]->state);
        $this->assertEquals('CONFLICTING_SUPPLIER_METADATA', $demandsConflict[0]->eligibility_snapshot['exception_reason']);
    }

    /**
     * =========================================================================
     * STAGE B: Batching & Supplier PO Lifecycle (Scenarios 4, 5, ACL checks)
     * =========================================================================
     */
    public function test_stage_b_batching_store_isolation_and_approval_workflow(): void
    {
        $pAlpha1 = $this->createUatProduct('UAT-POV2-SKU-ALP-01', isImported: true, storeId: 'store_uat_alpha', cost: 10.0);
        $pAlpha2 = $this->createUatProduct('UAT-POV2-SKU-ALP-02', isImported: true, storeId: 'store_uat_alpha', cost: 15.0);
        $pBeta = $this->createUatProduct('UAT-POV2-SKU-BET-01', isImported: true, storeId: 'store_uat_beta', cost: 20.0);

        $ordA = $this->createUatOrder('UAT-POV2-ORD-04A', [['product' => $pAlpha1, 'qty' => 2]]);
        $ordB = $this->createUatOrder('UAT-POV2-ORD-04B', [['product' => $pAlpha2, 'qty' => 1]]);
        $ordC = $this->createUatOrder('UAT-POV2-ORD-05-DIFF', [['product' => $pBeta, 'qty' => 3]]);

        $demandsA = $this->demandService->processOrderDemands($ordA);
        $demandsB = $this->demandService->processOrderDemands($ordB);
        $demandsC = $this->demandService->processOrderDemands($ordC);

        $demandIds = [$demandsA[0]->id, $demandsB[0]->id, $demandsC[0]->id];

        // 1. Viewer attempts to create batch -> 403 Forbidden
        $this->actingAs($this->viewerAdmin, 'admin')
            ->postJson(route('admin.procurement.batches.store'), ['demand_ids' => $demandIds])
            ->assertStatus(403);
        $this->assertEquals(0, ProcurementBatch::count(), 'Viewer must not create batch.');

        // 2. Operator creates batch containing all 3 demands
        $this->actingAs($this->operatorAdmin, 'admin')
            ->postJson(route('admin.procurement.batches.store'), ['demand_ids' => $demandIds])
            ->assertStatus(302);

        $batch = ProcurementBatch::first();
        $this->assertNotNull($batch);
        $this->assertEquals(ProcurementBatch::STATE_READY_FOR_REVIEW, $batch->state);

        // Verify that batch split into 2 distinct Supplier POs for the 2 distinct stores
        $spos = SupplierPurchaseOrder::where('batch_id', $batch->id)->get();
        $this->assertCount(2, $spos, 'Batch must split into 2 SPOs for the 2 stores.');

        $spoAlpha = $spos->firstWhere('supplier_store_id', 'store_uat_alpha');
        $spoBeta = $spos->firstWhere('supplier_store_id', 'store_uat_beta');
        $this->assertNotNull($spoAlpha);
        $this->assertNotNull($spoBeta);

        // Verify Reverse Links and Allocations
        $spoAlphaItems = SupplierPurchaseOrderItem::where('supplier_purchase_order_id', $spoAlpha->id)->get();
        $this->assertCount(2, $spoAlphaItems);
        $totalAllocatedAlpha = ProcurementDemandAllocation::whereIn('procurement_demand_id', [$demandsA[0]->id, $demandsB[0]->id])->sum('qty_allocated');
        $this->assertEquals(3, $totalAllocatedAlpha);

        // 3. Operator tries to approve batch -> 403 Forbidden
        $this->actingAs($this->operatorAdmin, 'admin')
            ->postJson(route('admin.procurement.batches.approve', $batch->id))
            ->assertStatus(403);
        $this->assertEquals(ProcurementBatch::STATE_READY_FOR_REVIEW, $batch->fresh()->state);

        // 4. Approver approves batch -> 302 OK
        $this->actingAs($this->approverAdmin, 'admin')
            ->postJson(route('admin.procurement.batches.approve', $batch->id))
            ->assertStatus(302);
        $this->assertEquals(ProcurementBatch::STATE_APPROVED, $batch->fresh()->state);

        // 5. Operator submits batch -> advances to awaiting_manual_payment
        $this->actingAs($this->operatorAdmin, 'admin')
            ->postJson(route('admin.procurement.batches.submit', $batch->id))
            ->assertStatus(302);
        $this->assertEquals(ProcurementBatch::STATE_AWAITING_MANUAL_PAYMENT, $batch->fresh()->state);

        // Verify Platform Orders generated with state wait_buyer_pay
        $platformOrders = ExternalPlatformOrder::whereIn('supplier_purchase_order_id', $spos->pluck('id'))->get();
        $this->assertCount(2, $platformOrders);
        foreach ($platformOrders as $po) {
            $this->assertEquals('WAIT_BUYER_PAY', $po->raw_status);
        }
    }

    /**
     * =========================================================================
     * STAGE C: Manual Payment, Polling Mock & Cost Variance (Scenario 9)
     * =========================================================================
     */
    public function test_stage_c_manual_payment_polling_and_cost_variance(): void
    {
        $p = $this->createUatProduct('UAT-POV2-SKU-VAR-09', isImported: true, storeId: 'store_uat_var', cost: 20.0);
        $order = $this->createUatOrder('UAT-POV2-ORD-09-VAR', [['product' => $p, 'qty' => 1]]);
        $demands = $this->demandService->processOrderDemands($order);

        $batch = $this->batchService->createBatch([$demands[0]->id], $this->operatorAdmin->id);
        $this->batchService->approveBatch($batch->id, $this->approverAdmin->id);
        $this->submitService->submitBatch($batch->id, $this->operatorAdmin->id);

        $spo = SupplierPurchaseOrder::where('batch_id', $batch->id)->first();
        $platformOrder = ExternalPlatformOrder::where('supplier_purchase_order_id', $spo->id)->first();
        $this->assertNotNull($platformOrder);
        $this->assertEquals(SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT, $spo->state);

        // 1. Operator attempts to declare manual payment -> 403 Forbidden
        $this->actingAs($this->operatorAdmin, 'admin')
            ->postJson(route('admin.procurement.manual_payments.store'), [
                'supplier_purchase_order_id' => $spo->id,
                'external_reference' => 'UAT-PAY-REF-001',
                'declared_total' => 20.0,
                'currency' => 'USD',
                'notes' => 'UAT Test Note',
            ])
            ->assertStatus(403);
        $this->assertEquals(0, ProcurementManualPaymentConfirmation::count());

        // 2. Finance declares manual payment -> Success
        $this->actingAs($this->financeAdmin, 'admin')
            ->postJson(route('admin.procurement.manual_payments.store'), [
                'supplier_purchase_order_id' => $spo->id,
                'external_reference' => 'UAT-PAY-REF-001',
                'declared_total' => 20.0,
                'currency' => 'USD',
                'notes' => 'UAT Test Note',
            ])
            ->assertStatus(302);

        $this->assertEquals(1, ProcurementManualPaymentConfirmation::count());
        $this->assertEquals(SupplierPurchaseOrder::STATE_PAYMENT_DECLARED, $spo->fresh()->state);

        // 3. Polling Mock advances status to confirmed / shipped
        $fakePollingService = app(AliExpressPollingService::class);
        $fakePollingService->syncOrder($platformOrder, [
            'status' => 'WAIT_SELLER_SEND_GOODS',
            'actual_total' => 25.0, // Higher cost triggering Cost Variance
        ]);

        $snapshot = ProcurementCostSnapshot::where('snapshotable_type', SupplierPurchaseOrder::class)
            ->where('snapshotable_id', $spo->id)
            ->first();
        $this->assertNotNull($snapshot);
        $this->assertEquals(20.0, (float) $snapshot->total_amount, 'Expected cost snapshot must remain immutable.');
    }

    /**
     * =========================================================================
     * STAGE D: Receipt, Transit & Security Invariants (Scenario 10)
     * =========================================================================
     */
    public function test_stage_d_receipt_transit_and_quarantine_invariants(): void
    {
        $p = $this->createUatProduct('UAT-POV2-SKU-REC-10', isImported: true, storeId: 'store_uat_rec', cost: 10.0);
        $order = $this->createUatOrder('UAT-POV2-ORD-10-REC', [['product' => $p, 'qty' => 10]]);
        $demands = $this->demandService->processOrderDemands($order);

        $batch = $this->batchService->createBatch([$demands[0]->id], $this->operatorAdmin->id);
        $this->batchService->approveBatch($batch->id, $this->approverAdmin->id);
        $this->submitService->submitBatch($batch->id, $this->operatorAdmin->id);

        $spo = SupplierPurchaseOrder::where('batch_id', $batch->id)->first();
        $spoItem = SupplierPurchaseOrderItem::where('supplier_purchase_order_id', $spo->id)->first();

        $this->manualPaymentService->declarePayment($spo->id, $this->financeAdmin->id, 'REF-REC', 100.0, 'USD', 'proof.png');

        // 1. Inbound receipt at Saudi Hub: 8 Good, 1 Damaged, 1 Missing
        $this->receiptService->receiveInSaudiHub($spo->id, [
            [
                'item_id' => $spoItem->id,
                'qty_good' => 8,
                'qty_damaged' => 1,
                'qty_missing' => 1,
            ],
        ], $this->receiverAdmin->id);

        $saStock = ProductInventory::where('product_id', $p->id)->where('inventory_source_id', $this->dropshipSaSource->id)->value('qty');
        $yeStock = ProductInventory::where('product_id', $p->id)->where('inventory_source_id', $this->dropshipYeSource->id)->value('qty');
        $this->assertEquals(8, $saStock, 'Saudi stock receives good quantity only.');
        $this->assertEquals(0, $yeStock, 'Yemen stock must remain 0 before Yemen receipt.');

        // 2. Dispatch SA -> YE
        $this->receiptService->dispatchToYemenTransfer($spo->id, [$spoItem->id => 8], $this->receiverAdmin->id);
        $saStockAfterDispatch = ProductInventory::where('product_id', $p->id)->where('inventory_source_id', $this->dropshipSaSource->id)->value('qty');
        $this->assertEquals(0, $saStockAfterDispatch);

        // 3. Receive in Yemen Hub
        $this->receiptService->receiveInYemenHub($spo->id, [
            [
                'item_id' => $spoItem->id,
                'qty_good' => 8,
                'qty_damaged' => 0,
            ],
        ], $this->receiverAdmin->id);

        $yeStockAfterReceipt = ProductInventory::where('product_id', $p->id)->where('inventory_source_id', $this->dropshipYeSource->id)->value('qty');
        $this->assertEquals(8, $yeStockAfterReceipt, 'Yemen stock successfully updated with received good units.');
    }

    /**
     * =========================================================================
     * STAGE D2: COD Revenue Recognition & Accounting Transitions (Scenario 11)
     * =========================================================================
     */
    public function test_stage_d2_cod_revenue_recognition_and_liability_transitions(): void
    {
        $p = $this->createUatProduct('UAT-POV2-SKU-COD-11', isImported: true, storeId: 'store_uat_cod', cost: 10.0);
        $order = $this->createUatOrder('UAT-POV2-ORD-11-COD', [['product' => $p, 'qty' => 2]]);

        // Before collection: Unearned revenue / liability (Account 2210)
        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertEquals('cashondelivery', $payment->method);
        $this->assertEquals(0, (int) ($payment->additional['revenue_recognized'] ?? 0));
        $this->assertEquals('2210', $payment->additional['liability_account'] ?? '2210');

        // After delivery & collection: Recognized Sales Revenue (Account 4010)
        $payment->additional = array_merge($payment->additional ?? [], [
            'collected_at' => now()->toIso8601String(),
            'collected_amount' => 40.0,
            'revenue_recognized' => 1,
            'revenue_account' => '4010',
        ]);
        $payment->save();

        $this->assertEquals(1, $payment->fresh()->additional['revenue_recognized']);
        $this->assertEquals('4010', $payment->fresh()->additional['revenue_account']);
    }

    /**
     * =========================================================================
     * STAGE E: Visual Inspection & Admin Navigation (HTTP Views across 5 Roles)
     * =========================================================================
     */
    public function test_stage_e_visual_inspection_and_screen_rendering_across_roles(): void
    {
        // 1. Demands Index (All roles have view)
        foreach ([$this->operatorAdmin, $this->approverAdmin, $this->financeAdmin, $this->receiverAdmin, $this->viewerAdmin] as $adm) {
            $this->actingAs($adm, 'admin')
                ->get(route('admin.procurement.demands.index'))
                ->assertStatus(200)
                ->assertSeeText(trans('procurement::app.demands.title'));
        }

        // 2. Batches Index
        $this->actingAs($this->operatorAdmin, 'admin')
            ->get(route('admin.procurement.batches.index'))
            ->assertStatus(200);

        // 3. Batches Create Form: Operator -> 200, Viewer -> Unauthorized (401/403)
        $this->actingAs($this->operatorAdmin, 'admin')
            ->get(route('admin.procurement.batches.create'))
            ->assertStatus(200)
            ->assertSeeText(trans('procurement::app.batches.create-batch'));

        $viewerCreateRes = $this->actingAs($this->viewerAdmin, 'admin')
            ->get(route('admin.procurement.batches.create'));
        $this->assertContains($viewerCreateRes->status(), [401, 403]);

        // 4. Supplier Orders Index
        $this->actingAs($this->operatorAdmin, 'admin')
            ->get(route('admin.procurement.supplier_orders.index'))
            ->assertStatus(200);

        // 5. Cost Variances Index: Approver -> 200, Viewer -> 401/403
        $this->actingAs($this->approverAdmin, 'admin')
            ->get(route('admin.procurement.cost_variances.index'))
            ->assertStatus(200);

        $viewerCostRes = $this->actingAs($this->viewerAdmin, 'admin')
            ->get(route('admin.procurement.cost_variances.index'));
        $this->assertContains($viewerCostRes->status(), [401, 403]);

        // 6. Exceptions Index: Receiver -> 200, Viewer -> 401/403
        $this->actingAs($this->receiverAdmin, 'admin')
            ->get(route('admin.procurement.exceptions.index'))
            ->assertStatus(200);

        $viewerExcRes = $this->actingAs($this->viewerAdmin, 'admin')
            ->get(route('admin.procurement.exceptions.index'));
        $this->assertContains($viewerExcRes->status(), [401, 403]);

        // 7. Reports: Finance -> 200 (Numeric values)
        $finReport = $this->actingAs($this->financeAdmin, 'admin')
            ->get(route('admin.procurement.reports.index'))
            ->assertStatus(200);
        $this->assertTrue($finReport->viewData('metrics')['cost_view_permitted']);

        // User with reports_view ONLY (without cost_view): metrics are masked
        $reportsOnlyRole = Role::updateOrCreate(
            ['name' => 'UAT Procurement Reports Only Role'],
            ['permission_type' => 'custom', 'permissions' => [
                'dropshipping.procurement_v2.view',
                'dropshipping.procurement_v2.reports_view',
            ]]
        );
        $reportsOnlyAdmin = Admin::updateOrCreate(
            ['email' => 'uat_reports_only@test.local'],
            ['name' => 'UAT Reports Only', 'password' => bcrypt('password'), 'role_id' => $reportsOnlyRole->id, 'status' => 1]
        );

        $maskedReport = $this->actingAs($reportsOnlyAdmin, 'admin')
            ->get(route('admin.procurement.reports.index'))
            ->assertStatus(200);
        $this->assertFalse($maskedReport->viewData('metrics')['cost_view_permitted']);
        $this->assertNull($maskedReport->viewData('metrics')['total_expected_cost']);
        $this->assertNull($maskedReport->viewData('metrics')['total_actual_cost']);
        $this->assertNull($maskedReport->viewData('metrics')['uncollected_cod_total']);

        // Viewer (without reports_view) -> Unauthorized (401/403)
        $viewerReportRes = $this->actingAs($this->viewerAdmin, 'admin')
            ->get(route('admin.procurement.reports.index'));
        $this->assertContains($viewerReportRes->status(), [401, 403]);
    }
}
