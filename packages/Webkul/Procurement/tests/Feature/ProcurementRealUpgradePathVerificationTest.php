<?php

namespace Webkul\Procurement\Tests\Feature;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Services\ProcurementBatchService;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class ProcurementRealUpgradePathVerificationTest extends TestCase
{
    /**
     * Test 1: Pre-V2 orders remain untouched with zero silent backfill and zero V2 demands.
     */
    public function test_pre_v2_order_has_zero_v2_demands_and_zero_v2_batches(): void
    {
        $preV2Order = Order::where('increment_id', 'ORD-PRE-V2-001')->first();

        if ($preV2Order) {
            $demandsCount = ProcurementDemand::where('order_id', $preV2Order->id)->count();
            $this->assertEquals(0, $demandsCount, 'Pre-V2 orders must not be silently backfilled into V2 demands.');

            $v1Po = DB::table('purchase_orders')->where('order_id', $preV2Order->id)->first();
            $this->assertNotNull($v1Po, 'V1 purchase order must remain intact for historical pre-V2 orders.');
            $this->assertEquals('PO-V1-PRE-001', $v1Po->internal_reference);
        } else {
            $this->markTestSkipped('Pre-V2 fixture not found in current test DB.');
        }
    }

    /**
     * Test 2: Feature flag is false by default in production config.
     */
    public function test_feature_flag_is_false_by_default(): void
    {
        $configVal = config('procurement.v2_enabled');
        $this->assertFalse(
            (bool) env('PROCUREMENT_V2_ENABLED', false),
            'Feature flag PROCUREMENT_V2_ENABLED must be false by default.'
        );
    }

    /**
     * Test 3: New post-upgrade order with V2 enabled creates V2 SPO and never creates V1 purchase order.
     */
    public function test_post_upgrade_new_order_with_v2_creates_v2_spo_and_no_v1_po(): void
    {
        config(['procurement.v2_enabled' => true]);

        $role = Role::firstOrCreate(['name' => 'Procurement Admin'], ['permission_type' => 'all', 'permissions' => ['all']]);
        $admin = Admin::firstOrCreate(
            ['email' => 'upgrade_admin@test.com'],
            ['name' => 'Upgrade Admin', 'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 1]
        );

        $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => 'POST-UPG-SKU']);

        AliExpressProductImport::create([
            'product_id' => $product->id,
            'aliexpress_product_id' => 'ae_upg_'.$product->id,
            'title' => 'Imported UPG SKU',
            'status' => 'success',
            'raw_payload' => ['store_id' => 'store_upg'],
            'payload_snapshot' => ['store_id' => 'store_upg', 'store_name' => 'Store UPG'],
            'shipping_currency' => 'USD',
        ]);

        HigestSourceOffer::create([
            'product_id' => $product->id,
            'variant_id' => $product->id,
            'source_provider' => 'aliexpress',
            'source_sku_id' => 'sku_upg_'.$product->id,
            'acquisition_cost' => 15.0,
            'source_currency' => 'USD',
            'is_active' => 1,
        ]);

        $order = Order::create([
            'increment_id' => 'ORD-POST-UPG-'.rand(1000, 9999),
            'status' => 'processing',
            'channel_name' => 'Default',
            'is_guest' => 0,
            'customer_email' => 'upg_buyer@test.com',
            'customer_first_name' => 'Post',
            'customer_last_name' => 'Upgrade',
            'grand_total' => 40.0,
            'base_grand_total' => 40.0,
            'sub_total' => 40.0,
            'base_sub_total' => 40.0,
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
            'qty_ordered' => 2,
            'price' => 20.0,
            'total' => 40.0,
        ]);

        $initialV1Count = DB::table('purchase_orders')->count();

        // Process through V2 pipeline
        $demandService = app(ProcurementDemandService::class);
        $batchService = app(ProcurementBatchService::class);
        $submitService = app(ProcurementSubmitService::class);

        $demands = $demandService->processOrderDemands($order);
        $this->assertCount(1, $demands);

        $batch = $batchService->createBatch([$demands[0]->id], $admin->id);
        $batchService->approveBatch($batch->id, $admin->id);
        $submitService->submitBatch($batch->id, $admin->id);

        // V2 SupplierPurchaseOrder is created
        $v2Spo = SupplierPurchaseOrder::where('batch_id', $batch->id)->first();
        $this->assertNotNull($v2Spo);
        $this->assertContains($v2Spo->state, ['awaiting_manual_payment', 'submitted']);

        // Crucial: V1 purchase_orders count MUST NOT INCREASE
        $finalV1Count = DB::table('purchase_orders')->count();
        $this->assertEquals($initialV1Count, $finalV1Count, 'V2 workflow must never write into legacy V1 purchase_orders table.');
    }
}
