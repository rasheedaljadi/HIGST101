<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\DeliveryManagement\Database\Seeders\DeliveryGovernorateRulesSeeder;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Services\DeliveryLifecycleService;
use Webkul\DeliveryManagement\Services\HandoffExecutionService;
use Webkul\Inventory\Database\Seeders\HayestCentralInventorySourceSeeder;
use Webkul\Inventory\Services\InventoryMovementService;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class DeliveryAgentInterfaceHttpTest extends TestCase
{
    protected HandoffExecutionService $handoffExecutionService;

    protected DeliveryLifecycleService $deliveryLifecycleService;

    protected InventoryMovementService $inventoryMovementService;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->seed(HayestCentralInventorySourceSeeder::class);
        $this->seed(DeliveryGovernorateRulesSeeder::class);

        $this->handoffExecutionService = app(HandoffExecutionService::class);
        $this->deliveryLifecycleService = app(DeliveryLifecycleService::class);
        $this->inventoryMovementService = app(InventoryMovementService::class);
    }

    protected function createCourierAdmin(): Admin
    {
        $role = Role::firstOrCreate(
            ['name' => 'Courier'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        return Admin::create([
            'name' => 'Courier '.uniqid(),
            'email' => 'courier_'.uniqid().'@hayest.test',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 1,
        ]);
    }

    protected function createPointAgentAdmin(int $pointId): Admin
    {
        $role = Role::firstOrCreate(
            ['name' => 'PointAgent'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        return Admin::create([
            'name' => 'Point Agent '.uniqid(),
            'email' => 'point_agent_'.uniqid().'@hayest.test',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'delivery_point_id' => $pointId,
            'status' => 1,
        ]);
    }

    protected function createTestOrderAssignment(int $courierId): DeliveryAssignment
    {
        $hayestSource = $this->inventoryMovementService->getSourceByCode('hayest_central');
        $attributeFamilyId = DB::table('attribute_families')->value('id') ?? 1;

        $product = Product::create([
            'type' => 'simple',
            'attribute_family_id' => $attributeFamilyId,
            'sku' => 'HTTP-PROD-'.uniqid(),
        ]);

        DB::table('product_inventories')->insert([
            'product_id' => $product->id,
            'inventory_source_id' => $hayestSource->id,
            'qty' => 10,
        ]);

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

        $manageStockAttrId = DB::table('attributes')->where('code', 'manage_stock')->value('id');
        if ($manageStockAttrId) {
            DB::table('product_attribute_values')->insert([
                'product_id' => $product->id,
                'attribute_id' => $manageStockAttrId,
                'channel' => 'default',
                'boolean_value' => 1,
            ]);
        }

        $order = Order::create([
            'increment_id' => 'HTTP-ORD-'.uniqid(),
            'status' => 'pending',
            'is_guest' => 1,
            'channel_id' => $channelId,
            'channel_type' => Channel::class,
            'channel_name' => 'Default',
            'customer_email' => 'customer_http@hayest.test',
            'customer_first_name' => 'Saeed',
            'customer_last_name' => 'Al-Matari',
            'shipping_method' => 'homedelivery_standard',
            'shipping_title' => 'Hayest Home Delivery',
            'grand_total' => 18000,
            'base_grand_total' => 18000,
            'order_currency_code' => 'YER',
            'base_currency_code' => 'YER',
            'total_item_count' => 1,
            'total_qty_ordered' => 1,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'type' => 'simple',
            'sku' => $product->sku,
            'name' => 'HTTP Test Item',
            'qty_ordered' => 1,
            'qty_to_ship' => 1,
            'qty_to_invoice' => 1,
            'price' => 18000,
            'base_price' => 18000,
            'total' => 18000,
            'base_total' => 18000,
        ]);

        $shippingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_shipping',
            'first_name' => 'Saeed',
            'last_name' => 'Al-Matari',
            'email' => 'saeed@hayest.test',
            'phone' => '777443322',
            'address' => 'Baghdad St',
            'city' => 'Sanaa',
            'state' => 'SAN',
            'country' => 'YE',
        ]);

        $billingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_billing',
            'first_name' => 'Saeed',
            'last_name' => 'Al-Matari',
            'email' => 'saeed@hayest.test',
            'phone' => '777443322',
            'address' => 'Baghdad St',
            'city' => 'Sanaa',
            'state' => 'SAN',
            'country' => 'YE',
        ]);

        $order->setRelation('shipping_address', $shippingAddress);
        $order->setRelation('billing_address', $billingAddress);

        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'method' => 'cashondelivery',
            'method_title' => 'Cash On Delivery',
        ]);

        $order->setRelation('payment', $payment);

        $assignment = DeliveryAssignment::create([
            'order_id' => $order->id,
            'delivery_boy_id' => $courierId,
            'delivery_type' => 'home_delivery',
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'idempotency_key' => 'ASSIGN-TEST-'.$order->id.'-'.uniqid(),
            'customer_address_snapshot' => [
                'first_name' => 'Saeed',
                'last_name' => 'Al-Matari',
                'phone' => '777443322',
                'address' => 'Baghdad St',
                'city' => 'Sanaa',
                'state' => 'SAN',
                'country' => 'YE',
            ],
            'attempt_count' => 0,
            'max_attempts' => 3,
        ]);

        $this->handoffExecutionService->executeHandoff($order->id, 1, 'admin');

        return $assignment->fresh();
    }

    /**
     * Test guest user is redirected to login.
     */
    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/delivery');
        $response->assertRedirect(route('admin.session.create'));
    }

    /**
     * Test courier sees only their assigned tasks.
     */
    public function test_courier_sees_only_assigned_tasks(): void
    {
        $courierA = $this->createCourierAdmin();
        $courierB = $this->createCourierAdmin();

        $assignmentA = $this->createTestOrderAssignment($courierA->id);
        $assignmentB = $this->createTestOrderAssignment($courierB->id);

        $response = $this->actingAs($courierA, 'admin')->getJson('/delivery');
        $response->assertOk();
        $response->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $ids = collect($data)->pluck('id')->all();

        $this->assertContains($assignmentA->id, $ids);
        $this->assertNotContains($assignmentB->id, $ids);
    }

    /**
     * Test courier cannot view or update another courier's task (403 Forbidden).
     */
    public function test_courier_cannot_access_other_courier_assignment(): void
    {
        $courierA = $this->createCourierAdmin();
        $courierB = $this->createCourierAdmin();

        $assignmentA = $this->createTestOrderAssignment($courierA->id);

        $response = $this->actingAs($courierB, 'admin')->get('/delivery/assignments/'.$assignmentA->id);
        $response->assertStatus(403);
    }

    /**
     * Test courier starts delivery journey via HTTP endpoint.
     */
    public function test_courier_starts_delivery_journey_http(): void
    {
        $courier = $this->createCourierAdmin();
        $assignment = $this->createTestOrderAssignment($courier->id);

        $response = $this->actingAs($courier, 'admin')->postJson("/delivery/assignments/{$assignment->id}/start");
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', DeliveryAssignment::STATUS_OUT_FOR_DELIVERY);
    }

    /**
     * Test courier reports delivery failure via HTTP endpoint.
     */
    public function test_courier_reports_delivery_failure_http(): void
    {
        $courier = $this->createCourierAdmin();
        $assignment = $this->createTestOrderAssignment($courier->id);

        $this->actingAs($courier, 'admin')->postJson("/delivery/assignments/{$assignment->id}/start");

        $response = $this->actingAs($courier, 'admin')->postJson("/delivery/assignments/{$assignment->id}/fail", [
            'reason' => 'Customer is currently out of city',
            'schedule_retry' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', DeliveryAssignment::STATUS_RETRY_SCHEDULED);
        $response->assertJsonPath('data.attempt_count', 1);
    }

    /**
     * Test courier confirms final customer delivery without OTP requirement via HTTP endpoint.
     */
    public function test_courier_confirms_final_delivery_http_without_otp(): void
    {
        $courier = $this->createCourierAdmin();
        $assignment = $this->createTestOrderAssignment($courier->id);

        $this->actingAs($courier, 'admin')->postJson("/delivery/assignments/{$assignment->id}/start");

        // Execute delivery without providing any OTP code
        $payloadWithoutOtp = [
            'collected_amount' => 18000,
        ];

        $response = $this->actingAs($courier, 'admin')->postJson("/delivery/assignments/{$assignment->id}/delivered", $payloadWithoutOtp);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', DeliveryAssignment::STATUS_DELIVERED);

        // Verify that OTP was not required or stored
        $this->assertArrayNotHasKey('otp_code', $payloadWithoutOtp);

        // Verify cash collection was persisted
        $this->assertEquals(1, DB::table('delivery_cash_collections')->where('delivery_assignment_id', $assignment->id)->count());
        $this->assertEquals(1, DB::table('invoices')->where('order_id', $assignment->order_id)->count());
    }
}
