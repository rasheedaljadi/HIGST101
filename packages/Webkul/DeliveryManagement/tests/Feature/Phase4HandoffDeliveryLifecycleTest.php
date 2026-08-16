<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\DeliveryManagement\Database\Seeders\DeliveryGovernorateRulesSeeder;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAttemptLog;
use Webkul\DeliveryManagement\Models\DeliveryCashCollection;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\DeliveryManagement\Services\DeliveryLifecycleService;
use Webkul\DeliveryManagement\Services\HandoffExecutionService;
use Webkul\DeliveryManagement\Services\ShippingMethodAdapter;
use Webkul\Inventory\Database\Seeders\HayestCentralInventorySourceSeeder;
use Webkul\Inventory\Services\InventoryMovementService;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class Phase4HandoffDeliveryLifecycleTest extends TestCase
{
    protected HandoffExecutionService $handoffExecutionService;

    protected DeliveryLifecycleService $deliveryLifecycleService;

    protected InventoryMovementService $inventoryMovementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HayestCentralInventorySourceSeeder::class);
        $this->seed(DeliveryGovernorateRulesSeeder::class);

        $this->handoffExecutionService = app(HandoffExecutionService::class);
        $this->deliveryLifecycleService = app(DeliveryLifecycleService::class);
        $this->inventoryMovementService = app(InventoryMovementService::class);
    }

    protected function createTestAdmin(string $roleName = 'Courier'): Admin
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['permission_type' => 'custom', 'permissions' => []]
        );

        return Admin::create([
            'name' => 'Agent '.uniqid(),
            'email' => 'agent_'.uniqid().'@hayest.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
            'status' => 1,
        ]);
    }

    protected function setupOrderWithStock(
        int $stockQty = 10,
        int $orderQty = 2,
        string $paymentMethod = 'cashondelivery',
        string $shippingMethod = 'homedelivery_standard',
        string $stateCode = 'SAN'
    ): array {
        $hayestSource = $this->inventoryMovementService->getSourceByCode('hayest_central');

        $attributeFamilyId = DB::table('attribute_families')->value('id') ?? 1;

        // Create product
        $product = Product::create([
            'type' => 'simple',
            'attribute_family_id' => $attributeFamilyId,
            'sku' => 'PROD-P4-'.uniqid(),
        ]);

        // Insert inventory in hayest_central
        DB::table('product_inventories')->insert([
            'product_id' => $product->id,
            'inventory_source_id' => $hayestSource->id,
            'qty' => $stockQty,
        ]);

        // Channel, Locale, Currency
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

        // Create Order
        $order = Order::create([
            'increment_id' => 'ORD-P4-'.uniqid(),
            'status' => 'pending',
            'is_guest' => 1,
            'channel_id' => $channelId,
            'channel_type' => Channel::class,
            'channel_name' => 'Default',
            'customer_email' => 'customer@hayest.test',
            'customer_first_name' => 'Ali',
            'customer_last_name' => 'Yemen',
            'shipping_method' => $shippingMethod,
            'shipping_title' => 'Hayest Express',
            'grand_total' => 20000,
            'base_grand_total' => 20000,
            'order_currency_code' => 'YER',
            'base_currency_code' => 'YER',
            'total_item_count' => 1,
            'total_qty_ordered' => $orderQty,
        ]);

        // Create Order Item
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'type' => 'simple',
            'sku' => $product->sku,
            'name' => 'Test Item',
            'qty_ordered' => $orderQty,
            'qty_to_ship' => $orderQty,
            'qty_to_invoice' => $orderQty,
            'price' => 10000,
            'base_price' => 10000,
            'total' => 10000 * $orderQty,
            'base_total' => 10000 * $orderQty,
        ]);

        // Order Address
        $shippingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_shipping',
            'first_name' => 'Ali',
            'last_name' => 'Yemen',
            'email' => 'ali@hayest.test',
            'phone' => '777123456',
            'address' => 'Hadda St',
            'city' => 'Sanaa',
            'state' => $stateCode,
            'country' => 'YE',
        ]);

        $billingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_billing',
            'first_name' => 'Ali',
            'last_name' => 'Yemen',
            'email' => 'ali@hayest.test',
            'phone' => '777123456',
            'address' => 'Hadda St',
            'city' => 'Sanaa',
            'state' => $stateCode,
            'country' => 'YE',
        ]);

        $order->setRelation('shipping_address', $shippingAddress);
        $order->setRelation('billing_address', $billingAddress);

        // Order Payment
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'method' => $paymentMethod,
            'method_title' => $paymentMethod === 'cashondelivery' ? 'Cash On Delivery' : 'Money Transfer',
        ]);

        $order->setRelation('payment', $payment);

        // Create DeliveryAssignment
        $assignment = DeliveryAssignment::create([
            'order_id' => $order->id,
            'delivery_type' => app(ShippingMethodAdapter::class)->canonicalize($shippingMethod) ?: 'home_delivery',
            'status' => DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT,
            'customer_address_snapshot' => [
                'first_name' => 'Ali',
                'last_name' => 'Yemen',
                'phone' => '777123456',
                'address' => 'Hadda St',
                'city' => 'Sanaa',
                'state' => $stateCode,
                'country' => 'YE',
            ],
            'attempt_count' => 0,
            'max_attempts' => 3,
        ]);

        return compact('product', 'order', 'orderItem', 'assignment', 'hayestSource');
    }

    /**
     * 1. Handoff success from hayest_central creates Shipment and deducts stock once.
     */
    public function test_handoff_success_from_hayest_central_creates_shipment_and_deducts_once(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 2);
        $order = $setup['order'];
        $product = $setup['product'];
        $hayestSource = $setup['hayestSource'];
        $supervisor = $this->createTestAdmin('Supervisor');

        $assignment = $this->handoffExecutionService->executeHandoff(
            orderId: $order->id,
            actorId: $supervisor->id,
            actorType: 'supervisor'
        );

        $this->assertEquals(DeliveryAssignment::STATUS_PICKED_UP, $assignment->status);
        $this->assertNotNull($assignment->shipment_id);
        $this->assertNotNull($assignment->picked_up_at);

        // 1. Verify physical stock deducted exactly once (10 - 2 = 8)
        $stockAfter = DB::table('product_inventories')
            ->where('product_id', $product->id)
            ->where('inventory_source_id', $hayestSource->id)
            ->value('qty');
        $this->assertEquals(8, $stockAfter);

        // 2. Verify audit inventory movement recorded
        $movement = DB::table('inventory_movements')
            ->where('product_id', $product->id)
            ->where('source_inventory_source_id', $hayestSource->id)
            ->where('movement_type', 'handoff_to_delivery_party')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(2, $movement->quantity);
        $this->assertEquals($supervisor->id, $movement->actor_id);
    }

    /**
     * 2. Handoff is rejected if stock is insufficient.
     */
    public function test_handoff_fails_when_stock_is_insufficient(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 1, orderQty: 5); // Stock 1 < Order 5
        $order = $setup['order'];
        $supervisor = $this->createTestAdmin('Supervisor');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->handoffExecutionService->executeHandoff(
            orderId: $order->id,
            actorId: $supervisor->id,
            actorType: 'supervisor'
        );
    }

    /**
     * 3. Handoff is idempotent: calling again does not create a second shipment or double deduct stock.
     */
    public function test_replaying_handoff_is_idempotent_and_does_not_double_deduct(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 3);
        $order = $setup['order'];
        $product = $setup['product'];
        $hayestSource = $setup['hayestSource'];
        $supervisor = $this->createTestAdmin('Supervisor');

        // First call
        $firstResult = $this->handoffExecutionService->executeHandoff(
            orderId: $order->id,
            actorId: $supervisor->id,
            idempotencyKey: 'IDEMP-HANDOFF-101'
        );

        $this->assertEquals(7, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));
        $firstShipmentId = $firstResult->shipment_id;

        // Second call (replay)
        $secondResult = $this->handoffExecutionService->executeHandoff(
            orderId: $order->id,
            actorId: $supervisor->id,
            idempotencyKey: 'IDEMP-HANDOFF-101'
        );

        // Stock MUST stay at 7 (no second deduction)
        $this->assertEquals(7, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));
        $this->assertEquals($firstShipmentId, $secondResult->shipment_id);

        // Shipments count for order must be exactly 1
        $this->assertEquals(1, DB::table('shipments')->where('order_id', $order->id)->count());
    }

    /**
     * 4. Courier lifecycle: assignment, starting delivery, and policy isolation.
     */
    public function test_courier_assignment_and_start_delivery(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 1);
        $order = $setup['order'];
        $assignment = $setup['assignment'];
        $courierA = $this->createTestAdmin('Courier');
        $courierB = $this->createTestAdmin('Courier');
        $supervisor = $this->createTestAdmin('Supervisor');

        // 1. Assign to Courier A
        $assignment = $this->deliveryLifecycleService->assignToCourier($assignment, $courierA->id, $supervisor->id);
        $this->assertEquals($courierA->id, $assignment->delivery_boy_id);
        $this->assertEquals(DeliveryAssignment::STATUS_ASSIGNED, $assignment->status);

        // 2. Perform handoff
        $assignment = $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);
        $this->assertEquals(DeliveryAssignment::STATUS_PICKED_UP, $assignment->status);

        // 3. Courier B tries to start delivery -> must fail
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized');
        $this->deliveryLifecycleService->startDelivery($assignment, $courierB->id);
    }

    /**
     * 4b. Assigned courier successfully starts delivery journey.
     */
    public function test_assigned_courier_starts_delivery_journey(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 1);
        $order = $setup['order'];
        $assignment = $setup['assignment'];
        $courier = $this->createTestAdmin('Courier');
        $supervisor = $this->createTestAdmin('Supervisor');

        $assignment = $this->deliveryLifecycleService->assignToCourier($assignment, $courier->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);

        $assignment = $this->deliveryLifecycleService->startDelivery($assignment->fresh(), $courier->id);
        $this->assertEquals(DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, $assignment->status);
        $this->assertNotNull($assignment->out_for_delivery_at);
    }

    /**
     * 5. Delivery point arrival and customer confirmation.
     */
    public function test_delivery_point_arrival_and_confirmation(): void
    {
        $point = DeliveryPoint::create([
            'code' => 'SAN-HUB-P4',
            'name' => 'Sanaa Main Point',
            'state_code' => 'SAN',
            'city' => 'Sanaa',
            'address' => 'Baghdad St',
            'is_active' => true,
        ]);

        $setup = $this->setupOrderWithStock(
            stockQty: 10,
            orderQty: 1,
            paymentMethod: 'moneytransfer',
            shippingMethod: 'deliverypoint_pickup'
        );

        $order = $setup['order'];
        $assignment = $setup['assignment'];
        $pointAgent = $this->createTestAdmin('PointAgent');
        $supervisor = $this->createTestAdmin('Supervisor');

        // Assign to delivery point
        $assignment = $this->deliveryLifecycleService->assignToDeliveryPoint($assignment, $point->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);

        // Confirm arrival at point
        $assignment = $this->deliveryLifecycleService->confirmArrivalAtPoint($assignment->fresh(), $pointAgent->id, $point->id);
        $this->assertEquals(DeliveryAssignment::STATUS_ARRIVED_AT_POINT, $assignment->status);
    }

    /**
     * 6. Final delivery confirmation for COD records collection in YER and generates paid invoice without double stock deduction.
     */
    public function test_final_delivery_for_cod_records_collection_and_paid_invoice_without_stock_deduction(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 2, paymentMethod: 'cashondelivery');
        $order = $setup['order'];
        $product = $setup['product'];
        $hayestSource = $setup['hayestSource'];
        $courier = $this->createTestAdmin('Courier');
        $supervisor = $this->createTestAdmin('Supervisor');

        // 1. Assign and Handoff
        $assignment = $this->deliveryLifecycleService->assignToCourier($setup['assignment'], $courier->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);

        // 2. Start delivery
        $assignment = $this->deliveryLifecycleService->startDelivery($assignment->fresh(), $courier->id);

        $stockBeforeDelivery = DB::table('product_inventories')
            ->where('product_id', $product->id)
            ->where('inventory_source_id', $hayestSource->id)
            ->value('qty');
        $this->assertEquals(8, $stockBeforeDelivery);

        // 3. Confirm delivery and collect COD
        $deliveredAssignment = $this->deliveryLifecycleService->confirmCustomerDelivery(
            assignment: $assignment,
            actorId: $courier->id,
            actorType: 'courier',
            collectedAmount: 20000,
            currency: 'YER'
        );

        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERED, $deliveredAssignment->status);
        $this->assertNotNull($deliveredAssignment->delivered_at);

        // 4. Verify stock is NOT deducted again (still 8)
        $stockAfterDelivery = DB::table('product_inventories')
            ->where('product_id', $product->id)
            ->where('inventory_source_id', $hayestSource->id)
            ->value('qty');
        $this->assertEquals(8, $stockAfterDelivery);

        // 5. Verify COD cash collection record
        $collection = DeliveryCashCollection::where('delivery_assignment_id', $deliveredAssignment->id)->first();
        $this->assertNotNull($collection);
        $this->assertEquals(20000, (float) $collection->amount);
        $this->assertEquals('YER', $collection->currency);

        // 6. Verify paid invoice was generated for COD order
        $invoice = DB::table('invoices')->where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('paid', $invoice->state);
    }

    /**
     * 7. Final delivery for prepaid orders does NOT record COD cash collection.
     */
    public function test_final_delivery_for_prepaid_does_not_create_cod_collection(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 1, paymentMethod: 'moneytransfer');
        $order = $setup['order'];
        $courier = $this->createTestAdmin('Courier');
        $supervisor = $this->createTestAdmin('Supervisor');

        $assignment = $this->deliveryLifecycleService->assignToCourier($setup['assignment'], $courier->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);
        $assignment = $this->deliveryLifecycleService->startDelivery($assignment->fresh(), $courier->id);

        $deliveredAssignment = $this->deliveryLifecycleService->confirmCustomerDelivery(
            assignment: $assignment,
            actorId: $courier->id,
            actorType: 'courier'
        );

        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERED, $deliveredAssignment->status);

        // Verify NO cash collection is created
        $this->assertEquals(0, DeliveryCashCollection::where('delivery_assignment_id', $deliveredAssignment->id)->count());
    }

    /**
     * 8. Delivery failure logs attempt and schedules retry or marks failed when max attempts reached.
     */
    public function test_delivery_failure_logs_attempt_and_manages_retries(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 1);
        $courier = $this->createTestAdmin('Courier');
        $supervisor = $this->createTestAdmin('Supervisor');

        $assignment = $this->deliveryLifecycleService->assignToCourier($setup['assignment'], $courier->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($setup['order']->id, $supervisor->id);
        $assignment = $this->deliveryLifecycleService->startDelivery($assignment->fresh(), $courier->id);

        // Attempt 1: Failed -> retry scheduled
        $assignment = $this->deliveryLifecycleService->recordDeliveryFailure(
            assignment: $assignment,
            reason: 'Customer phone unreachable',
            actorId: $courier->id,
            scheduleRetry: true
        );

        $this->assertEquals(1, $assignment->attempt_count);
        $this->assertEquals(DeliveryAssignment::STATUS_RETRY_SCHEDULED, $assignment->status);
        $this->assertEquals(1, DeliveryAttemptLog::where('delivery_assignment_id', $assignment->id)->count());

        // Attempt 2: Failed -> retry scheduled
        $assignment = $this->deliveryLifecycleService->recordDeliveryFailure(
            assignment: $assignment,
            reason: 'Customer requested next day',
            actorId: $courier->id,
            scheduleRetry: true
        );
        $this->assertEquals(2, $assignment->attempt_count);
        $this->assertEquals(DeliveryAssignment::STATUS_RETRY_SCHEDULED, $assignment->status);

        // Attempt 3: Failed (reaches max_attempts 3) -> delivery_failed
        $assignment = $this->deliveryLifecycleService->recordDeliveryFailure(
            assignment: $assignment,
            reason: 'Wrong address provided',
            actorId: $courier->id,
            scheduleRetry: true
        );
        $this->assertEquals(3, $assignment->attempt_count);
        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERY_FAILED, $assignment->status);
        $this->assertNotNull($assignment->failed_at);
        $this->assertEquals(3, DeliveryAttemptLog::where('delivery_assignment_id', $assignment->id)->count());
    }

    /**
     * 9. Return to Hayest Central requires supervisor permission and restores stock to hayest_central once.
     */
    public function test_return_to_hayest_restores_physical_stock_once(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 2);
        $order = $setup['order'];
        $product = $setup['product'];
        $hayestSource = $setup['hayestSource'];
        $courier = $this->createTestAdmin('Courier');
        $supervisor = $this->createTestAdmin('Supervisor');

        // Handoff: Stock goes from 10 -> 8
        $assignment = $this->deliveryLifecycleService->assignToCourier($setup['assignment'], $courier->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);

        $this->assertEquals(8, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));

        // Mark failed
        $assignment = $this->deliveryLifecycleService->recordDeliveryFailure($assignment->fresh(), 'Customer canceled order', $courier->id, false);

        // Supervisor approves return to Hayest Central
        $returnedAssignment = $this->deliveryLifecycleService->returnToHayest(
            assignment: $assignment,
            supervisorId: $supervisor->id,
            reason: 'Customer refused delivery, returned to central warehouse'
        );

        $this->assertEquals(DeliveryAssignment::STATUS_RETURNED_TO_HAYEST, $returnedAssignment->status);
        $this->assertNotNull($returnedAssignment->returned_at);

        // 1. Stock restored back to 10 in hayest_central
        $stockRestored = DB::table('product_inventories')
            ->where('product_id', $product->id)
            ->where('inventory_source_id', $hayestSource->id)
            ->value('qty');
        $this->assertEquals(10, $stockRestored);

        // 2. Inventory movement return_from_delivery recorded
        $movement = DB::table('inventory_movements')
            ->where('product_id', $product->id)
            ->where('target_inventory_source_id', $hayestSource->id)
            ->where('movement_type', 'return_from_delivery')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(2, $movement->quantity);

        // 3. Replay protection: Calling return again does not double-restore stock
        $this->deliveryLifecycleService->returnToHayest(
            assignment: $returnedAssignment,
            supervisorId: $supervisor->id,
            reason: 'Duplicate call'
        );

        $this->assertEquals(10, DB::table('product_inventories')->where('product_id', $product->id)->where('inventory_source_id', $hayestSource->id)->value('qty'));
    }

    /**
     * 10. Handoff is rejected if order allocation is still with supplier (inbound receipt and rebind required).
     */
    public function test_handoff_rejected_if_allocation_still_with_supplier(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 1);
        $order = $setup['order'];
        $supervisor = $this->createTestAdmin('Supervisor');

        // Create an active allocation pointing to supplier aliexpress
        DB::table('order_allocations')->insert([
            'order_id' => $order->id,
            'order_item_id' => $setup['orderItem']->id,
            'product_id' => $setup['product']->id,
            'allocation_type' => 'supplier',
            'source_code' => 'aliexpress',
            'reserved_qty' => 1,
            'state' => 'reserved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Handoff Rejected: Order #'.$order->id.' allocation is still with supplier');

        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);
    }

    /**
     * 11. Replaying customer delivery confirmation is strictly idempotent.
     */
    public function test_replaying_customer_delivery_is_idempotent(): void
    {
        $setup = $this->setupOrderWithStock(stockQty: 10, orderQty: 1, paymentMethod: 'cashondelivery');
        $order = $setup['order'];
        $courier = $this->createTestAdmin('Courier');
        $supervisor = $this->createTestAdmin('Supervisor');

        $assignment = $this->deliveryLifecycleService->assignToCourier($setup['assignment'], $courier->id, $supervisor->id);
        $this->handoffExecutionService->executeHandoff($order->id, $supervisor->id);
        $assignment = $this->deliveryLifecycleService->startDelivery($assignment->fresh(), $courier->id);

        // First confirmation
        $delivered1 = $this->deliveryLifecycleService->confirmCustomerDelivery(
            assignment: $assignment,
            actorId: $courier->id,
            collectedAmount: 20000,
            idempotencyKey: 'IDEMP-DELIVERY-1'
        );

        // Replay confirmation
        $delivered2 = $this->deliveryLifecycleService->confirmCustomerDelivery(
            assignment: $delivered1,
            actorId: $courier->id,
            collectedAmount: 20000,
            idempotencyKey: 'IDEMP-DELIVERY-1'
        );

        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERED, $delivered2->status);
        $this->assertEquals(1, DeliveryCashCollection::where('delivery_assignment_id', $assignment->id)->count());
        $this->assertEquals(1, DB::table('invoices')->where('order_id', $order->id)->count());
    }
}
