<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Exception;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryCashCollection;
use Webkul\DeliveryManagement\Models\DeliverySettlement;
use Webkul\DeliveryManagement\Services\DeliveryLifecycleService;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class DynamicCurrencyDeliveryTest extends TestCase
{
    protected DeliveryLifecycleService $lifecycleService;

    protected Admin $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lifecycleService = app(DeliveryLifecycleService::class);

        $role = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['permission_type' => 'all']
        );

        // Ensure Admin user
        $this->adminUser = Admin::firstOrCreate(
            ['email' => 'currency_tester@higest.test'],
            [
                'name' => 'Currency Admin Tester',
                'password' => bcrypt('secret123'),
                'role_id' => $role->id,
                'status' => 1,
            ]
        );
    }

    /**
     * Helper to create mock order and assignment with custom currency.
     */
    protected function createOrderWithCurrency(string $currencyCode, float $amount, string $paymentMethod = 'cashondelivery'): array
    {
        $channel = Channel::first() ?: Channel::create([
            'code' => 'default',
            'name' => 'Default Channel',
            'hostname' => 'localhost',
            'default_locale_id' => 1,
            'base_currency_id' => 1,
        ]);

        $order = Order::create([
            'increment_id' => 'CURR-'.uniqid(),
            'status' => 'processing',
            'channel_id' => $channel->id,
            'channel_type' => Channel::class,
            'channel_name' => 'Default',
            'customer_email' => 'customer@currency.test',
            'customer_first_name' => 'Dynamic',
            'customer_last_name' => 'CurrencyUser',
            'shipping_method' => 'delivery_home_delivery',
            'shipping_title' => 'Express Delivery',
            'grand_total' => $amount,
            'base_grand_total' => $amount,
            'order_currency_code' => $currencyCode,
            'base_currency_code' => $currencyCode,
            'total_item_count' => 1,
            'total_qty_ordered' => 1,
        ]);

        OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_shipping',
            'first_name' => 'Dynamic',
            'last_name' => 'CurrencyUser',
            'phone' => '777000111',
            'address1' => 'Main Street',
            'city' => 'Sanaa',
            'state' => 'Sanaa',
            'country' => 'YE',
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'method' => $paymentMethod,
            'method_title' => $paymentMethod === 'cashondelivery' ? 'Cash On Delivery' : 'Prepaid Card',
        ]);

        $assignment = DeliveryAssignment::create([
            'order_id' => $order->id,
            'delivery_type' => 'home_delivery',
            'delivery_boy_id' => $this->adminUser->id,
            'status' => DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
            'state_code' => 'SAN',
            'payment_method' => $paymentMethod,
            'attempt_count' => 1,
            'max_attempts' => 3,
            'idempotency_key' => 'CURR-ASSIGN-'.$order->id,
        ]);

        return ['order' => $order, 'assignment' => $assignment];
    }

    /**
     * Test 1: Delivery COD cash collection records exact order currency and amounts.
     */
    public function test_cod_cash_collection_records_dynamic_order_currency_and_amounts(): void
    {
        $setup = $this->createOrderWithCurrency(currencyCode: 'USD', amount: 150.00, paymentMethod: 'cashondelivery');
        $assignment = $setup['assignment'];
        $order = $setup['order'];

        $delivered = $this->lifecycleService->confirmCustomerDelivery(
            assignment: $assignment,
            actorId: $this->adminUser->id,
            actorType: 'courier',
            collectedAmount: 150.00,
            currency: 'USD'
        );

        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERED, $delivered->status);

        $collection = DeliveryCashCollection::where('delivery_assignment_id', $assignment->id)->first();
        $this->assertNotNull($collection);
        $this->assertEquals('USD', $collection->order_currency_code);
        $this->assertEquals(150.00, (float) $collection->order_amount);
        $this->assertEquals('USD', $collection->collected_currency_code);
        $this->assertEquals(150.00, (float) $collection->collected_amount);
        $this->assertEquals('USD', $collection->currency);
        $this->assertEquals(150.00, (float) $collection->amount);
    }

    /**
     * Test 2: Phase 1 strictly rejects mismatched collection currency instead of silent conversion.
     */
    public function test_phase1_rejects_collection_in_mismatched_currency(): void
    {
        $setup = $this->createOrderWithCurrency(currencyCode: 'USD', amount: 100.00, paymentMethod: 'cashondelivery');
        $assignment = $setup['assignment'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Collected currency (EUR) must match order currency (USD).');

        $this->lifecycleService->confirmCustomerDelivery(
            assignment: $assignment,
            actorId: $this->adminUser->id,
            actorType: 'courier',
            collectedAmount: 100.00,
            currency: 'EUR'
        );
    }

    /**
     * Test 3: Prepaid orders do not generate COD cash collections upon delivery.
     */
    public function test_prepaid_orders_do_not_generate_cash_collection(): void
    {
        $setup = $this->createOrderWithCurrency(currencyCode: 'USD', amount: 250.00, paymentMethod: 'moneytransfer');
        $assignment = $setup['assignment'];

        $delivered = $this->lifecycleService->confirmCustomerDelivery(
            assignment: $assignment,
            actorId: $this->adminUser->id,
            actorType: 'courier',
            collectedAmount: null
        );

        $this->assertEquals(DeliveryAssignment::STATUS_DELIVERED, $delivered->status);

        $collectionCount = DeliveryCashCollection::where('delivery_assignment_id', $assignment->id)->count();
        $this->assertEquals(0, $collectionCount);
    }

    /**
     * Test 4: Courier settlement records dynamic system currency without hardcoded values.
     */
    public function test_settlement_saves_dynamic_system_currency(): void
    {
        $settlement = DeliverySettlement::create([
            'delivery_boy_id' => $this->adminUser->id,
            'settlement_date' => now()->toDateString(),
            'expected_amount' => 500.00,
            'actual_amount' => 500.00,
            'difference' => 0.00,
            'currency' => 'USD',
            'status' => 'settled',
            'settled_by' => $this->adminUser->id,
            'settled_at' => now(),
            'notes' => 'USD Settlement verification test',
        ]);

        $this->assertNotNull($settlement->id);
        $this->assertEquals('USD', $settlement->currency);
        $this->assertEquals('settled', $settlement->status);
        $this->assertEquals(500.00, (float) $settlement->actual_amount);
    }

    /**
     * Test 5: Http endpoint rejects mismatched currency via Courier Delivery API.
     */
    public function test_delivery_agent_api_rejects_currency_mismatch(): void
    {
        $setup = $this->createOrderWithCurrency(currencyCode: 'USD', amount: 75.00, paymentMethod: 'cashondelivery');
        $assignment = $setup['assignment'];

        $response = $this->actingAs($this->adminUser, 'admin')->postJson("/delivery/assignments/{$assignment->id}/delivered", [
            'collected_amount' => 75.00,
            'collected_currency' => 'SAR',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('SAR', $response->json('message'));
        $this->assertStringContainsString('USD', $response->json('message'));
    }
}
