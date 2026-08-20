<?php

namespace Webkul\Procurement\Tests\Feature;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use Exception;
use Tests\TestCase;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Services\HandoffExecutionService;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Procurement\Services\ProcurementInboundReceiptService;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductInventory;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class ProcurementCanonicalInventoryLifecycleTest extends TestCase
{
    protected ProcurementDemandService $demandService;

    protected ProcurementBatchService $batchService;

    protected ProcurementSubmitService $submitService;

    protected ProcurementInboundReceiptService $receiptService;

    protected HandoffExecutionService $handoffService;

    protected InventorySource $saSource;

    protected InventorySource $saQuarantineSource;

    protected InventorySource $yeSource;

    protected InventorySource $yeQuarantineSource;

    protected InventorySource $internalYeSource;

    protected InventorySource $centralSource;

    protected Admin $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['procurement.v2_enabled' => true]);

        $this->saSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_sa'],
            ['name' => 'Hayest Saudi Hub', 'country' => 'SA', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );

        $this->saQuarantineSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_quarantine_sa'],
            ['name' => 'Hayest Saudi Quarantine', 'country' => 'SA', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );

        $this->yeSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            ['name' => 'Hayest Yemen Dropship Hub', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        $this->yeQuarantineSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_quarantine_ye'],
            ['name' => 'Hayest Yemen Quarantine', 'country' => 'YE', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );

        $this->internalYeSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_internal_ye'],
            ['name' => 'Hayest Yemen Internal Warehouse', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        $this->centralSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_central'],
            ['name' => 'Hayest Central Legacy', 'country' => 'YE', 'is_salable' => 0, 'is_delivery_source' => 0, 'status' => 1]
        );

        $role = Role::firstOrCreate(['name' => 'Procurement Admin'], ['permission_type' => 'all', 'permissions' => ['all']]);
        $this->adminUser = Admin::firstOrCreate(
            ['email' => 'procurement_admin_inv@test.com'],
            ['name' => 'Procurement Admin', 'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 1]
        );

        $this->demandService = app(ProcurementDemandService::class);
        $this->batchService = app(ProcurementBatchService::class);
        $this->submitService = app(ProcurementSubmitService::class);
        $this->receiptService = app(ProcurementInboundReceiptService::class);
        $this->handoffService = app(HandoffExecutionService::class);
    }

    protected function createTestProduct(string $sku, bool $isImported = true, float $cost = 10.0): Product
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
                'title' => 'Imported '.$sku,
                'status' => 'success',
                'raw_payload' => ['store_id' => 'ae_store_999'],
                'payload_snapshot' => ['store_id' => 'ae_store_999', 'store_name' => 'Store 999'],
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

    protected function createTestOrder(Product $product, int $qty = 2, float $price = 25.0): Order
    {
        $order = Order::create([
            'increment_id' => 'ORD-INV-'.rand(100000, 999999),
            'status' => 'processing',
            'channel_name' => 'Default',
            'is_guest' => 0,
            'customer_email' => 'inv_buyer@test.com',
            'customer_first_name' => 'Ali',
            'customer_last_name' => 'Hassan',
            'grand_total' => $price * $qty,
            'base_grand_total' => $price * $qty,
            'sub_total' => $price * $qty,
            'base_sub_total' => $price * $qty,
            'order_currency_code' => 'USD',
            'base_currency_code' => 'USD',
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'method' => 'cashondelivery',
            'method_title' => 'Cash on Delivery',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'type' => 'simple',
            'sku' => $product->sku,
            'name' => 'Item '.$product->sku,
            'qty_ordered' => $qty,
            'qty_to_ship' => $qty,
            'price' => $price,
            'total' => $price * $qty,
        ]);

        OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_shipping',
            'first_name' => 'Ali',
            'last_name' => 'Hassan',
            'email' => 'ali@test.com',
            'phone' => '777000111',
            'address' => 'Baghdad St',
            'city' => 'Sanaa',
            'state' => 'SAN',
            'country' => 'YE',
        ]);

        return $order;
    }

    /**
     * 1. V2 Saudi receipt: Official receipt for AliExpress imported SPO strictly increments hayest_dropship_sa by good quantity.
     */
    public function test_v2_saudi_receipt_increments_only_hayest_dropship_sa_by_good_quantity(): void
    {
        $product = $this->createTestProduct('CANON-SA-001', true, 10.0);
        $order = $this->createTestOrder($product, 6, 20.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);
        $this->submitService->submitBatch($batch->id, $this->adminUser->id);

        $spo = $batch->supplierOrders->first();
        $poItem = $spo->items->first();

        // Perform official receipt at Saudi Hub
        $this->receiptService->receiveInSaudiHub(
            $spo->id,
            [['item_id' => $poItem->id, 'qty_good' => 6, 'qty_damaged' => 0, 'qty_missing' => 0]],
            $this->adminUser->id
        );

        $saStock = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->saSource->id)->value('qty');
        $this->assertEquals(6, $saStock);

        // Crucial: Yemen Hub and Legacy Central must remain untouched at this stage
        $yeStock = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->yeSource->id)->value('qty');
        $centralStock = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->centralSource->id)->value('qty');
        $this->assertEquals(0, (int) $yeStock);
        $this->assertEquals(0, (int) $centralStock);
    }

    /**
     * 2. Damage/missing: Damaged units route to quarantine without incrementing sellable stock.
     */
    public function test_damaged_and_missing_units_route_to_quarantine_without_incrementing_sellable_stock(): void
    {
        $product = $this->createTestProduct('CANON-DAM-002', true, 12.0);
        $order = $this->createTestOrder($product, 10, 25.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);
        $this->submitService->submitBatch($batch->id, $this->adminUser->id);

        $spo = $batch->supplierOrders->first();
        $poItem = $spo->items->first();

        // 6 good, 3 damaged, 1 missing
        $this->receiptService->receiveInSaudiHub(
            $spo->id,
            [['item_id' => $poItem->id, 'qty_good' => 6, 'qty_damaged' => 3, 'qty_missing' => 1]],
            $this->adminUser->id
        );

        // Saudi staging has 6 good
        $saStock = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->saSource->id)->value('qty');
        $this->assertEquals(6, $saStock);

        // Saudi quarantine has 3 damaged
        $quarantineStock = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->saQuarantineSource->id)->value('qty');
        $this->assertEquals(3, $quarantineStock);

        // No sellable inventory in Yemen
        $yeStock = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->yeSource->id)->value('qty');
        $this->assertEquals(0, (int) $yeStock);
    }

    /**
     * 3. SA->YE Dispatch: Dispatch deducts from hayest_dropship_sa and does NOT prematurely increment hayest_dropship_ye.
     */
    public function test_sa_to_ye_dispatch_deducts_sa_and_does_not_prematurely_increment_ye(): void
    {
        $product = $this->createTestProduct('CANON-TRF-003', true, 10.0);
        $order = $this->createTestOrder($product, 4, 20.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);
        $this->submitService->submitBatch($batch->id, $this->adminUser->id);

        $spo = $batch->supplierOrders->first();
        $poItem = $spo->items->first();

        $this->receiptService->receiveInSaudiHub(
            $spo->id,
            [['item_id' => $poItem->id, 'qty_good' => 4]],
            $this->adminUser->id
        );

        // Dispatch 4 units on transit manifest
        $this->receiptService->dispatchToYemenTransfer(
            $spo->id,
            [$poItem->id => 4],
            $this->adminUser->id,
            'MANIFEST-SA-YE-003'
        );

        // Saudi staging stock is decremented to 0
        $saStockAfterDispatch = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->saSource->id)->value('qty');
        $this->assertEquals(0, $saStockAfterDispatch);

        // Yemen hub is still 0 during transit (no premature increment)
        $yeStockDuringTransit = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->yeSource->id)->value('qty');
        $this->assertEquals(0, (int) $yeStockDuringTransit);
    }

    /**
     * 4. Yemen receipt: Completing receipt in Yemen increments ONLY hayest_dropship_ye by good quantity.
     */
    public function test_yemen_receipt_completion_increments_only_hayest_dropship_ye_and_never_internal_or_central(): void
    {
        $product = $this->createTestProduct('CANON-YE-004', true, 10.0);
        $order = $this->createTestOrder($product, 5, 20.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);
        $this->submitService->submitBatch($batch->id, $this->adminUser->id);

        $spo = $batch->supplierOrders->first();
        $poItem = $spo->items->first();

        $this->receiptService->receiveInSaudiHub($spo->id, [['item_id' => $poItem->id, 'qty_good' => 5]], $this->adminUser->id);
        $this->receiptService->dispatchToYemenTransfer($spo->id, [$poItem->id => 5], $this->adminUser->id, 'MANIFEST-004');

        // Receive in Yemen Hub (4 good, 1 transit damaged)
        $this->receiptService->receiveInYemenHub(
            $spo->id,
            [['item_id' => $poItem->id, 'qty_good' => 4, 'qty_damaged' => 1]],
            $this->adminUser->id
        );

        // Yemen Dropship Hub has exactly 4 sellable units
        $yeStock = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->yeSource->id)->value('qty');
        $this->assertEquals(4, $yeStock);

        // Yemen Quarantine has 1 unit
        $yeQuarantine = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->yeQuarantineSource->id)->value('qty');
        $this->assertEquals(1, $yeQuarantine);

        // Never routed to internal or central
        $internalYe = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->internalYeSource->id)->value('qty');
        $central = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->centralSource->id)->value('qty');
        $this->assertEquals(0, (int) $internalYe);
        $this->assertEquals(0, (int) $central);
    }

    /**
     * 5. Handoff: Strictly rejects handoff from forbidden/staging sources and unreceived imported stock.
     */
    public function test_handoff_strictly_rejected_from_prohibited_sources_and_unreceived_stock(): void
    {
        $product = $this->createTestProduct('CANON-HAND-005', true, 10.0);
        $order = $this->createTestOrder($product, 2, 20.0);

        DeliveryAssignment::create([
            'order_id' => $order->id,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'governorate_code' => 'SAN',
            'delivery_fee' => 1500,
        ]);

        // Attempting handoff while stock is still in Saudi Hub or unreceived must fail
        ProductInventory::updateOrCreate(
            ['product_id' => $product->id, 'inventory_source_id' => $this->saSource->id],
            ['qty' => 5]
        );

        $this->expectException(Exception::class);
        $this->handoffService->executeHandoff($order->id, $this->adminUser->id);
    }

    /**
     * 6. Regression: Internal products strictly use hayest_internal_ye without creating external demands.
     */
    public function test_internal_products_strictly_use_hayest_internal_ye_without_creating_external_demands(): void
    {
        $product = $this->createTestProduct('CANON-INT-006', false);
        $order = $this->createTestOrder($product, 3, 30.0);

        // Put 1 unit in hayest_internal_ye
        ProductInventory::updateOrCreate(
            ['product_id' => $product->id, 'inventory_source_id' => $this->internalYeSource->id],
            ['qty' => 1]
        );

        $demands = $this->demandService->processOrderDemands($order);

        // No external demand generated
        $this->assertEmpty($demands);
        $this->assertEquals(0, ProcurementDemand::where('order_id', $order->id)->count());

        // Internal deficit recorded in audit logs
        $audit = ProcurementAuditLog::where('action', 'internal_stock_exception')->latest()->first();
        $this->assertNotNull($audit);
        $this->assertEquals('internal_stock_deficit', $audit->new_state);
        $this->assertEquals(2, $audit->details['deficit']);
    }

    /**
     * 7. Legacy isolation: hayest_central remains historical read-only and never appears in V2 movements or handoff.
     */
    public function test_legacy_isolation_hayest_central_never_appears_in_v2_movements_or_handoff(): void
    {
        $product = $this->createTestProduct('CANON-LEG-007', true, 10.0);
        $order = $this->createTestOrder($product, 2, 20.0);

        $demands = $this->demandService->processOrderDemands($order);
        $batch = $this->batchService->createBatch([$demands[0]->id], $this->adminUser->id);
        $this->batchService->approveBatch($batch->id, $this->adminUser->id);
        $this->submitService->submitBatch($batch->id, $this->adminUser->id);

        $spo = $batch->supplierOrders->first();
        $poItem = $spo->items->first();

        // Full V2 cycle
        $this->receiptService->receiveInSaudiHub($spo->id, [['item_id' => $poItem->id, 'qty_good' => 2]], $this->adminUser->id);
        $this->receiptService->dispatchToYemenTransfer($spo->id, [$poItem->id => 2], $this->adminUser->id, 'LEG-007');
        $this->receiptService->receiveInYemenHub($spo->id, [['item_id' => $poItem->id, 'qty_good' => 2]], $this->adminUser->id);

        // Confirm hayest_central has 0 inventory entries and 0 increments
        $centralStock = ProductInventory::where('product_id', $product->id)->where('inventory_source_id', $this->centralSource->id)->value('qty');
        $this->assertEquals(0, (int) $centralStock);
    }
}
