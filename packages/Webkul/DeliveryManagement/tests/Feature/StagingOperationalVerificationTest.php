<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\DeliveryManagement\Database\Seeders\DeliveryGovernorateRulesSeeder;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAttemptLog;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\DeliveryManagement\Services\DeliveryLifecycleService;
use Webkul\DeliveryManagement\Services\HandoffExecutionService;
use Webkul\DeliveryManagement\Services\PaymentEligibilityChecker;
use Webkul\DeliveryManagement\Services\ShippingMethodAdapter;
use Webkul\Fulfillment\Services\InboundReceiptService;
use Webkul\Inventory\Database\Seeders\HayestCentralInventorySourceSeeder;
use Webkul\Inventory\Services\InventoryMovementService;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class StagingOperationalVerificationTest extends TestCase
{
    protected HandoffExecutionService $handoffExecutionService;

    protected DeliveryLifecycleService $deliveryLifecycleService;

    protected InventoryMovementService $inventoryMovementService;

    protected InboundReceiptService $inboundReceiptService;

    protected PaymentEligibilityChecker $paymentEligibilityChecker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HayestCentralInventorySourceSeeder::class);
        $this->seed(DeliveryGovernorateRulesSeeder::class);

        $this->handoffExecutionService = app(HandoffExecutionService::class);
        $this->deliveryLifecycleService = app(DeliveryLifecycleService::class);
        $this->inventoryMovementService = app(InventoryMovementService::class);
        $this->inboundReceiptService = app(InboundReceiptService::class);
        $this->paymentEligibilityChecker = app(PaymentEligibilityChecker::class);
    }

    protected function createAdminUser(string $roleName = 'Courier', ?int $deliveryPointId = null): Admin
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['permission_type' => 'custom', 'permissions' => []]
        );

        return Admin::create([
            'name' => "{$roleName} ".uniqid(),
            'email' => strtolower($roleName).'_'.uniqid().'@hayest.test',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'delivery_point_id' => $deliveryPointId,
            'status' => 1,
        ]);
    }

    protected function buildStagingOrder(
        int $stockQty = 10,
        int $orderQty = 2,
        string $paymentMethod = 'cashondelivery',
        string $shippingMethod = 'homedelivery_standard',
        string $stateCode = 'SAN',
        ?int $deliveryPointId = null
    ): array {
        $hayestSource = $this->inventoryMovementService->getSourceByCode('hayest_central');
        $attributeFamilyId = DB::table('attribute_families')->value('id') ?? 1;

        $product = Product::create([
            'type' => 'simple',
            'attribute_family_id' => $attributeFamilyId,
            'sku' => 'STG-PROD-'.uniqid(),
        ]);

        DB::table('product_inventories')->insert([
            'product_id' => $product->id,
            'inventory_source_id' => $hayestSource->id,
            'qty' => $stockQty,
        ]);

        $manageStockAttrId = DB::table('attributes')->where('code', 'manage_stock')->value('id');
        if ($manageStockAttrId) {
            DB::table('product_attribute_values')->insert([
                'product_id' => $product->id,
                'attribute_id' => $manageStockAttrId,
                'channel' => 'default',
                'boolean_value' => 1,
            ]);
        }

        $channelId = DB::table('channels')->where('code', 'default')->value('id')
            ?? DB::table('channels')->insertGetId([
                'code' => 'default',
                'theme' => 'default',
                'hostname' => 'localhost',
                'default_locale_id' => 1,
                'base_currency_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('product_channels')->insertOrIgnore([
            'product_id' => $product->id,
            'channel_id' => $channelId,
        ]);

        $order = Order::create([
            'increment_id' => 'STG-ORD-'.uniqid(),
            'status' => 'pending',
            'is_guest' => 1,
            'channel_id' => $channelId,
            'channel_type' => Channel::class,
            'channel_name' => 'Default',
            'customer_email' => 'customer_stg@hayest.test',
            'customer_first_name' => 'Nasser',
            'customer_last_name' => 'Al-Hemyari',
            'shipping_method' => $shippingMethod,
            'shipping_title' => 'Hayest Standard Delivery',
            'grand_total' => 25000,
            'base_grand_total' => 25000,
            'order_currency_code' => 'YER',
            'base_currency_code' => 'YER',
            'total_item_count' => 1,
            'total_qty_ordered' => $orderQty,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'type' => 'simple',
            'sku' => $product->sku,
            'name' => 'Staging Verification Item',
            'qty_ordered' => $orderQty,
            'qty_to_ship' => $orderQty,
            'qty_to_invoice' => $orderQty,
            'price' => 12500,
            'base_price' => 12500,
            'total' => 12500 * $orderQty,
            'base_total' => 12500 * $orderQty,
        ]);

        $shippingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_shipping',
            'first_name' => 'Nasser',
            'last_name' => 'Al-Hemyari',
            'email' => 'nasser@hayest.test',
            'phone' => '777987654',
            'address' => 'Zubairy St',
            'city' => 'Sanaa',
            'state' => $stateCode,
            'country' => 'YE',
        ]);

        $billingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_billing',
            'first_name' => 'Nasser',
            'last_name' => 'Al-Hemyari',
            'email' => 'nasser@hayest.test',
            'phone' => '777987654',
            'address' => 'Zubairy St',
            'city' => 'Sanaa',
            'state' => $stateCode,
            'country' => 'YE',
        ]);

        $order->setRelation('shipping_address', $shippingAddress);
        $order->setRelation('billing_address', $billingAddress);

        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'method' => $paymentMethod,
            'method_title' => $paymentMethod === 'cashondelivery' ? 'Cash On Delivery' : 'Money Transfer',
        ]);

        $order->setRelation('payment', $payment);

        $assignment = DeliveryAssignment::create([
            'order_id' => $order->id,
            'delivery_type' => app(ShippingMethodAdapter::class)->canonicalize($shippingMethod) ?: 'home_delivery',
            'delivery_point_id' => $deliveryPointId,
            'status' => DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT,
            'customer_address_snapshot' => [
                'first_name' => 'Nasser',
                'last_name' => 'Al-Hemyari',
                'phone' => '777987654',
                'address' => 'Zubairy St',
                'city' => 'Sanaa',
                'state' => $stateCode,
                'country' => 'YE',
            ],
            'attempt_count' => 0,
            'max_attempts' => (int) config('delivery.max_delivery_attempts', 3),
        ]);

        return compact('product', 'order', 'orderItem', 'assignment', 'hayestSource');
    }

    /**
     * Staging Scenario 1: Complete AliExpress procurement, inbound receipt, allocation rebind, handoff, and final delivery.
     */
    public function test_staging_scenario_aliexpress_procurement_to_final_delivery(): void
    {
        $setup = $this->buildStagingOrder(stockQty: 0, orderQty: 2);
        $order = $setup['order'];
        $product = $setup['product'];
        $hayestSource = $setup['hayestSource'];
        $supervisor = $this->createAdminUser('Supervisor');
        $courier = $this->createAdminUser('Courier');

        // Step 1: Initial state - Order item allocated to AliExpress supplier
        DB::table('order_allocations')->insert([
            'order_id' => $order->id,
            'order_item_id' => $setup['orderItem']->id,
            'product_id' => $product->id,
            'allocation_type' => 'supplier',
            'source_code' => 'aliexpress',
            'reserved_qty' => 2,
            'state' => 'reserved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify Handoff is strictly rejected when allocation is still with AliExpress
        try {
            $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);
            $this->fail('Handoff should have been rejected for supplier allocation');
        } catch (Exception $e) {
            $this->assertStringContainsString('Handoff Rejected', $e->getMessage());
        }

        // Step 2: Physical inbound receipt into Hayest Central warehouse
        $poId = DB::table('purchase_orders')->insertGetId([
            'order_id' => $order->id,
            'provider' => 'aliexpress',
            'internal_reference' => 'PO-STG-'.$order->id.'-'.uniqid(),
            'idempotency_key' => 'IDEMP-PO-'.$order->id.'-'.uniqid(),
            'state' => 'submitted',
            'receipt_status' => 'inbound_receipt_pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_order_items')->insert([
            'purchase_order_id' => $poId,
            'order_item_id' => $setup['orderItem']->id,
            'qty' => 2,
            'supplier_unit_cost' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receiptResult = $this->inboundReceiptService->confirmFullReceipt(
            purchaseOrderId: $poId,
            actorId: $supervisor->id,
            notes: 'Physical inbound inspection confirmed by staging QA',
            idempotencyKey: 'INBOUND-STG-PO-'.$poId
        );

        $this->assertEquals(2, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));

        // Step 3: Verify allocation was rebound to warehouse:hayest_central
        $activeAlloc = DB::table('order_allocations')
            ->where('order_id', $order->id)
            ->where('state', '!=', 'canceled')
            ->first();
        $this->assertNotNull($activeAlloc);
        $this->assertEquals('warehouse', $activeAlloc->allocation_type);
        $this->assertEquals('hayest_central', $activeAlloc->source_code);

        // Step 4: Assign to Courier
        $assignment = $this->deliveryLifecycleService->assignToCourier($setup['assignment'], $courier->id, $supervisor->id);
        $this->assertEquals(DeliveryAssignment::STATUS_ASSIGNED, $assignment->status);

        // Step 5: Handoff from hayest_central to Courier
        $handedOver = $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);
        $this->assertEquals(DeliveryAssignment::STATUS_PICKED_UP, $handedOver->status);
        $this->assertNotNull($handedOver->shipment_id);

        // Verify Shipment count = 1
        $this->assertEquals(1, DB::table('shipments')->where('order_id', $order->id)->count());

        // Verify stock deducted once: 2 -> 0
        $this->assertEquals(0, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));

        // Step 6: Courier starts delivery
        $outForDel = $this->deliveryLifecycleService->startDelivery($handedOver, $courier->id);
        $this->assertEquals(DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, $outForDel->status);

        // Step 7: Final delivery and COD collection
        $delivered = $this->deliveryLifecycleService->confirmCustomerDelivery(
            assignment: $outForDel,
            actorId: $courier->id,
            collectedAmount: 25000,
            currency: 'YER'
        );

        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERED, $delivered->status);

        // Step 8: Verify NO second stock deduction
        $this->assertEquals(0, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));

        // Verify 1 cash collection in YER
        $collections = DB::table('delivery_cash_collections')->where('delivery_assignment_id', $delivered->id)->get();
        $this->assertCount(1, $collections);
        $this->assertEquals(25000, (float) $collections->first()->amount);
        $this->assertEquals('YER', $collections->first()->currency);

        // Verify 1 paid invoice
        $invoices = DB::table('invoices')->where('order_id', $order->id)->get();
        $this->assertCount(1, $invoices);
        $this->assertEquals('paid', $invoices->first()->state);
    }

    /**
     * Staging Scenario 2: Delivery Point assignment, arrival confirmation, and strict COD rejection.
     */
    public function test_staging_scenario_delivery_point_pickup(): void
    {
        $point = DeliveryPoint::create([
            'code' => 'SAN-HUB-STG',
            'name' => 'Sanaa Central Hub',
            'state_code' => 'SAN',
            'city' => 'Sanaa',
            'address' => 'Zubairy Crossroads',
            'is_active' => true,
        ]);

        // Verify COD is strictly rejected for delivery points
        $isCodEligible = $this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            deliveryType: 'delivery_point',
            stateCode: 'SAN',
            deliveryPointId: $point->id
        );
        $this->assertFalse($isCodEligible, 'COD must be prohibited for delivery point pickups.');

        $setup = $this->buildStagingOrder(
            stockQty: 10,
            orderQty: 1,
            paymentMethod: 'moneytransfer',
            shippingMethod: 'deliverypoint_pickup',
            deliveryPointId: $point->id
        );

        $order = $setup['order'];
        $supervisor = $this->createAdminUser('Supervisor');
        $pointAgent = $this->createAdminUser('PointAgent', $point->id);

        // Assign to delivery point
        $assignment = $this->deliveryLifecycleService->assignToDeliveryPoint($setup['assignment'], $point->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);

        // Confirm arrival at delivery point by explicit point agent actor
        $arrived = $this->deliveryLifecycleService->confirmArrivalAtPoint($assignment->fresh(), $pointAgent->id, $point->id);
        $this->assertEquals(DeliveryAssignment::STATUS_ARRIVED_AT_POINT, $arrived->status);

        // Final customer pickup from point
        $completed = $this->deliveryLifecycleService->confirmCustomerDelivery($arrived, $pointAgent->id, 'delivery_point_agent');
        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERED, $completed->status);

        // No COD cash collection created for prepaid pickup
        $this->assertEquals(0, DB::table('delivery_cash_collections')->where('delivery_assignment_id', $completed->id)->count());
    }

    /**
     * Staging Scenario 3: Consecutive delivery failures, retry limits, and supervisor return to Hayest Central.
     */
    public function test_staging_scenario_failure_retries_and_supervisor_return(): void
    {
        $setup = $this->buildStagingOrder(stockQty: 5, orderQty: 2);
        $order = $setup['order'];
        $product = $setup['product'];
        $hayestSource = $setup['hayestSource'];
        $courier = $this->createAdminUser('Courier');
        $supervisor = $this->createAdminUser('Supervisor');

        $assignment = $this->deliveryLifecycleService->assignToCourier($setup['assignment'], $courier->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);

        // Initial handoff reduced stock: 5 -> 3
        $this->assertEquals(3, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));

        $assignment = $this->deliveryLifecycleService->startDelivery($assignment->fresh(), $courier->id);

        // Attempt 1: Customer not answering -> retry scheduled
        $assignment = $this->deliveryLifecycleService->recordDeliveryFailure($assignment, 'Customer not answering phone', $courier->id, true);
        $this->assertEquals(1, $assignment->attempt_count);
        $this->assertEquals(DeliveryAssignment::STATUS_RETRY_SCHEDULED, $assignment->status);

        // Attempt 2: Postponed by customer -> retry scheduled
        $assignment = $this->deliveryLifecycleService->recordDeliveryFailure($assignment, 'Customer requested evening delivery', $courier->id, true);
        $this->assertEquals(2, $assignment->attempt_count);
        $this->assertEquals(DeliveryAssignment::STATUS_RETRY_SCHEDULED, $assignment->status);

        // Attempt 3: Out of coverage (reaches max_attempts 3) -> delivery_failed
        $assignment = $this->deliveryLifecycleService->recordDeliveryFailure($assignment, 'Address unreachable and phone disconnected', $courier->id, true);
        $this->assertEquals(3, $assignment->attempt_count);
        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERY_FAILED, $assignment->status);
        $this->assertNotNull($assignment->failed_at);

        // Verify attempt logs
        $logs = DeliveryAttemptLog::where('delivery_assignment_id', $assignment->id)->get();
        $this->assertCount(3, $logs);

        // Supervisor approves return to Hayest Central
        $returned = $this->deliveryLifecycleService->returnToHayest($assignment, $supervisor->id, 'Exceeded max delivery attempts');
        $this->assertEquals(DeliveryAssignment::STATUS_RETURNED_TO_HAYEST, $returned->status);
        $this->assertNotNull($returned->returned_at);

        // Stock restored back to Hayest Central: 3 -> 5
        $this->assertEquals(5, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));

        // Verify movement recorded
        $movement = DB::table('inventory_movements')
            ->where('product_id', $product->id)
            ->where('target_inventory_source_id', $hayestSource->id)
            ->where('movement_type', 'return_from_delivery')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(2, $movement->quantity);
    }
}
