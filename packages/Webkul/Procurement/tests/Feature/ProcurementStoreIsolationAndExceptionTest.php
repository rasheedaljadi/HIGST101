<?php

namespace Webkul\Procurement\Tests\Feature;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use DomainException;
use Tests\TestCase;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class ProcurementStoreIsolationAndExceptionTest extends TestCase
{
    protected ProcurementDemandService $demandService;

    protected ProcurementBatchService $batchService;

    protected Admin $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['procurement.v2_enabled' => true]);

        InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            ['name' => 'Hayest Yemen Hub', 'country' => 'YE', 'is_salable' => 1, 'is_delivery_source' => 1, 'status' => 1]
        );

        $role = Role::firstOrCreate(['name' => 'Store Isolation Admin'], ['permission_type' => 'all', 'permissions' => ['all']]);
        $this->adminUser = Admin::firstOrCreate(
            ['email' => 'store_isolation_admin@test.com'],
            ['name' => 'Store Admin', 'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 1]
        );

        $this->demandService = app(ProcurementDemandService::class);
        $this->batchService = app(ProcurementBatchService::class);
    }

    protected function createOrderWithItem(string $sku, ?string $storeId, ?string $additionalStoreId = null): array
    {
        $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => $sku]);

        $payload = [];
        if ($storeId !== null) {
            $payload = [
                'store_id' => $storeId,
                'store_name' => 'Store '.$storeId,
                'store_info' => ['store_id' => $storeId, 'store_name' => 'Store '.$storeId],
            ];
        }

        AliExpressProductImport::create([
            'product_id' => $product->id,
            'aliexpress_product_id' => 'ae_prod_'.$product->id,
            'title' => 'Product '.$sku,
            'status' => 'success',
            'payload_snapshot' => $payload,
            'shipping_currency' => 'USD',
        ]);

        HigestSourceOffer::create([
            'product_id' => $product->id,
            'variant_id' => $product->id,
            'source_provider' => 'aliexpress',
            'source_sku_id' => 'ae_sku_'.$product->id,
            'acquisition_cost' => 15.0,
            'source_currency' => 'USD',
            'is_active' => 1,
        ]);

        $order = Order::create([
            'increment_id' => 'ORD-STR-'.rand(1000, 9999),
            'status' => 'processing',
            'channel_name' => 'Default',
            'is_guest' => 0,
            'customer_email' => 'store_test@test.com',
            'customer_first_name' => 'Store',
            'customer_last_name' => 'Tester',
            'grand_total' => 30.0,
            'base_grand_total' => 30.0,
            'sub_total' => 30.0,
            'base_sub_total' => 30.0,
            'order_currency_code' => 'USD',
            'base_currency_code' => 'USD',
        ]);

        OrderPayment::create(['order_id' => $order->id, 'method' => 'cashondelivery', 'method_title' => 'COD']);

        $additional = [];
        if ($additionalStoreId !== null) {
            $additional['supplier_store_id'] = $additionalStoreId;
        }

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'name' => 'Item '.$sku,
            'sku' => $sku,
            'qty_ordered' => 1,
            'price' => 30.0,
            'base_price' => 30.0,
            'total' => 30.0,
            'base_total' => 30.0,
            'additional' => $additional,
        ]);

        $demands = $this->demandService->processOrderDemands($order);

        return [$product, $order, $demands[0]];
    }

    /**
     * 1. Demands with trusted store ID batches strictly per store.
     */
    public function test_demands_with_trusted_store_id_batch_strictly_per_store(): void
    {
        [$prod1, $ord1, $demandStoreA1] = $this->createOrderWithItem('SKU-STORE-A1', 'store_1001');
        [$prod2, $ord2, $demandStoreA2] = $this->createOrderWithItem('SKU-STORE-A2', 'store_1001');
        [$prod3, $ord3, $demandStoreB1] = $this->createOrderWithItem('SKU-STORE-B1', 'store_1002');

        $this->assertEquals(ProcurementDemand::STATE_OPEN_FOR_BATCHING, $demandStoreA1->state);
        $this->assertEquals(ProcurementDemand::STATE_OPEN_FOR_BATCHING, $demandStoreA2->state);
        $this->assertEquals(ProcurementDemand::STATE_OPEN_FOR_BATCHING, $demandStoreB1->state);

        // Batch all 3 demands
        $batch = $this->batchService->createBatch(
            [$demandStoreA1->id, $demandStoreA2->id, $demandStoreB1->id],
            $this->adminUser->id
        );

        // Should split into exactly 2 SupplierPurchaseOrders (one for store_1001, one for store_1002)
        $this->assertEquals(2, $batch->supplierOrders->count());

        $spoStores = $batch->supplierOrders->pluck('supplier_store_id')->toArray();
        $this->assertContains('store_1001', $spoStores);
        $this->assertContains('store_1002', $spoStores);
    }

    /**
     * 2. Demand with missing store metadata routes to supplier_exception and is excluded from batching.
     */
    public function test_demand_with_missing_store_metadata_routes_to_supplier_exception(): void
    {
        [$prod, $ord, $demandMissing] = $this->createOrderWithItem('SKU-MISSING-STORE', null, null);

        $this->assertEquals(ProcurementDemand::STATE_SUPPLIER_EXCEPTION, $demandMissing->state);
        $this->assertNull($demandMissing->supplier_store_id);
        $this->assertEquals('MISSING_SUPPLIER_STORE_METADATA', $demandMissing->eligibility_snapshot['exception_reason']);

        // Excluded from open demands query
        $openDemandIds = $this->batchService->getOpenDemandsQuery()->pluck('id')->toArray();
        $this->assertNotContains($demandMissing->id, $openDemandIds);

        // Direct batch attempt throws exception
        $this->expectException(DomainException::class);
        $this->batchService->createBatch([$demandMissing->id], $this->adminUser->id);
    }

    /**
     * 3. Demand with conflicting store metadata routes to supplier_exception.
     */
    public function test_demand_with_conflicting_store_metadata_routes_to_supplier_exception(): void
    {
        [$prod, $ord, $demandConflict] = $this->createOrderWithItem('SKU-CONFLICT-STORE', 'store_aaa', 'store_bbb');

        $this->assertEquals(ProcurementDemand::STATE_SUPPLIER_EXCEPTION, $demandConflict->state);
        $this->assertNull($demandConflict->supplier_store_id);
        $this->assertEquals('CONFLICTING_SUPPLIER_METADATA', $demandConflict->eligibility_snapshot['exception_reason']);

        $openDemandIds = $this->batchService->getOpenDemandsQuery()->pluck('id')->toArray();
        $this->assertNotContains($demandConflict->id, $openDemandIds);
    }
}
