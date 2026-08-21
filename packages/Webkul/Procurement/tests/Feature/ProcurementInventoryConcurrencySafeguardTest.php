<?php

namespace Webkul\Procurement\Tests\Feature;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use Tests\TestCase;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductInventory;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;

class ProcurementInventoryConcurrencySafeguardTest extends TestCase
{
    protected ProcurementDemandService $demandService;

    protected InventorySource $yeSource;

    protected function setUp(): void
    {
        parent::setUp();

        config(['procurement.v2_enabled' => true]);

        $this->yeSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            ['name' => 'Hayest Yemen Hub', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        $this->demandService = app(ProcurementDemandService::class);
    }

    protected function createImportedProduct(string $sku, int $initialStock = 0): Product
    {
        $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => $sku]);

        AliExpressProductImport::create([
            'product_id' => $product->id,
            'aliexpress_product_id' => 'ae_prod_'.$product->id,
            'title' => 'Product '.$sku,
            'status' => 'success',
            'payload_snapshot' => [
                'store_id' => 'store_conc_1',
                'store_name' => 'Store Concurrency',
                'store_info' => ['store_id' => 'store_conc_1', 'store_name' => 'Store Concurrency'],
            ],
            'shipping_currency' => 'USD',
        ]);

        HigestSourceOffer::create([
            'product_id' => $product->id,
            'variant_id' => $product->id,
            'source_provider' => 'aliexpress',
            'source_sku_id' => 'ae_sku_'.$product->id,
            'acquisition_cost' => 10.0,
            'source_currency' => 'USD',
            'is_active' => 1,
        ]);

        ProductInventory::updateOrCreate(
            ['product_id' => $product->id, 'inventory_source_id' => $this->yeSource->id, 'vendor_id' => 0],
            ['qty' => $initialStock]
        );

        return $product;
    }

    protected function createOrderForItem(Product $product, int $qty = 1): Order
    {
        $order = Order::create([
            'increment_id' => 'ORD-CONC-'.rand(1000, 99999),
            'status' => 'processing',
            'channel_name' => 'Default',
            'is_guest' => 0,
            'customer_email' => 'concurrency@test.com',
            'customer_first_name' => 'Concurrent',
            'customer_last_name' => 'User',
            'grand_total' => 20.0 * $qty,
            'base_grand_total' => 20.0 * $qty,
            'sub_total' => 20.0 * $qty,
            'base_sub_total' => 20.0 * $qty,
            'order_currency_code' => 'USD',
            'base_currency_code' => 'USD',
        ]);

        OrderPayment::create(['order_id' => $order->id, 'method' => 'cashondelivery', 'method_title' => 'COD']);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'name' => 'Item '.$product->sku,
            'sku' => $product->sku,
            'qty_ordered' => $qty,
            'price' => 20.0,
            'base_price' => 20.0,
            'total' => 20.0 * $qty,
            'base_total' => 20.0 * $qty,
            'additional' => [],
        ]);

        return $order;
    }

    /**
     * 1. Stock = 1, two consecutive/concurrent order demands:
     *    Order 1 consumes local stock (covered=1, external=0, allocation reserved=1).
     *    Order 2 sees active reservation of 1, available becomes 0 -> external=1, covered=0.
     *    Total covered = 1, Total external = 1, zero double-reservation, zero overselling.
     */
    public function test_two_orders_competing_for_single_stock_unit_allocate_exactly_one_local_and_one_external(): void
    {
        $product = $this->createImportedProduct('SKU-CONC-001', 1);

        $order1 = $this->createOrderForItem($product, 1);
        $order2 = $this->createOrderForItem($product, 1);

        $demands1 = $this->demandService->processOrderDemands($order1);
        $demands2 = $this->demandService->processOrderDemands($order2);

        $demand1 = $demands1[0];
        $demand2 = $demands2[0];

        // Demand 1 must be locally covered
        $this->assertEquals(1, $demand1->qty_covered_by_local);
        $this->assertEquals(0, $demand1->qty_required_external);
        $this->assertEquals(ProcurementDemand::STATE_LOCALLY_COVERED, $demand1->state);

        // Demand 2 must be external demand because local stock was reserved by Order 1
        $this->assertEquals(0, $demand2->qty_covered_by_local);
        $this->assertEquals(1, $demand2->qty_required_external);
        $this->assertEquals(ProcurementDemand::STATE_OPEN_FOR_BATCHING, $demand2->state);

        // Verify active OrderAllocation count
        $activeAllocations = OrderAllocation::where('source_code', 'hayest_dropship_ye')
            ->where('state', 'reserved')
            ->get();

        $this->assertEquals(1, $activeAllocations->count());
        $this->assertEquals($order1->id, $activeAllocations->first()->order_id);
        $this->assertEquals(1, $activeAllocations->first()->reserved_qty);

        // Physical inventory row remains 1 until warehouse dispatch, but available was strictly 0
        $physStock = ProductInventory::where('product_id', $product->id)
            ->where('inventory_source_id', $this->yeSource->id)
            ->value('qty');
        $this->assertEquals(1, $physStock);
    }

    /**
     * 2. Stock = 0, two orders -> 0 local covered, 2 external demands.
     */
    public function test_zero_stock_routes_all_orders_to_external_demand_without_allocations(): void
    {
        $product = $this->createImportedProduct('SKU-CONC-002', 0);

        $order1 = $this->createOrderForItem($product, 1);
        $order2 = $this->createOrderForItem($product, 1);

        $demands1 = $this->demandService->processOrderDemands($order1);
        $demands2 = $this->demandService->processOrderDemands($order2);

        $this->assertEquals(0, $demands1[0]->qty_covered_by_local);
        $this->assertEquals(1, $demands1[0]->qty_required_external);

        $this->assertEquals(0, $demands2[0]->qty_covered_by_local);
        $this->assertEquals(1, $demands2[0]->qty_required_external);

        // Zero local reservations created
        $activeAllocations = OrderAllocation::where('source_code', 'hayest_dropship_ye')
            ->where('state', 'reserved')
            ->count();
        $this->assertEquals(0, $activeAllocations);
    }

    /**
     * 3. Idempotent retries return existing demand without double allocation.
     */
    public function test_repeated_processing_is_idempotent(): void
    {
        $product = $this->createImportedProduct('SKU-CONC-003', 1);

        $order = $this->createOrderForItem($product, 1);

        $demandsRun1 = $this->demandService->processOrderDemands($order);
        $demandsRun2 = $this->demandService->processOrderDemands($order);

        $this->assertEquals($demandsRun1[0]->id, $demandsRun2[0]->id);

        $allocationsCount = OrderAllocation::where('order_id', $order->id)->count();
        $this->assertEquals(1, $allocationsCount);
    }
}
