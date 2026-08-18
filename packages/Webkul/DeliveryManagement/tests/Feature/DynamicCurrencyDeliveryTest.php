<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
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
        $locale = Locale::firstOrCreate(['code' => 'en'], ['name' => 'English', 'direction' => 'ltr']);
        $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal' => 2]);

        $channel = Channel::first() ?: Channel::create([
            'code' => 'default',
            'name' => 'Default Channel',
            'hostname' => 'localhost',
            'default_locale_id' => $locale->id,
            'base_currency_id' => $currency->id,
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

    /**
     * Test 6: Database schema verification - no hardcoded 'YER' defaults on currency columns.
     */
    public function test_database_schema_has_no_hardcoded_yer_defaults(): void
    {
        // 1. Check delivery_cash_collections columns
        $collectionCols = collect(DB::select('SHOW FULL COLUMNS FROM delivery_cash_collections'))->keyBy('Field');

        $this->assertTrue($collectionCols->has('order_currency_code'));
        $this->assertTrue($collectionCols->has('order_amount'));
        $this->assertTrue($collectionCols->has('collected_currency_code'));
        $this->assertTrue($collectionCols->has('collected_amount'));

        $this->assertNull($collectionCols['currency']->Default, 'Legacy currency default must be NULL, not YER');
        $this->assertNull($collectionCols['base_currency']->Default, 'Legacy base_currency default must be NULL, not YER');
        $this->assertNull($collectionCols['order_currency_code']->Default);
        $this->assertNull($collectionCols['collected_currency_code']->Default);

        // 2. Check delivery_settlements columns
        $settlementCols = collect(DB::select('SHOW FULL COLUMNS FROM delivery_settlements'))->keyBy('Field');
        $this->assertNull($settlementCols['currency']->Default, 'Settlement currency default must be NULL, not YER');
    }

    /**
     * Test 7: Runtime verification - inserting records without manual currency does not write YER automatically.
     */
    public function test_runtime_insertion_without_explicit_currency_does_not_inject_yer(): void
    {
        $rawId = DB::table('delivery_cash_collections')->insertGetId([
            'delivery_assignment_id' => 9999,
            'order_id' => 8888,
            'delivery_boy_id' => $this->adminUser->id,
            'amount' => 100.0000,
            'amount_in_base_currency' => 100.0000,
            'idempotency_key' => 'TEST-NO-YER-'.uniqid(),
            'collected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('delivery_cash_collections')->where('id', $rawId)->first();
        $this->assertNull($row->currency);
        $this->assertNull($row->base_currency);
        $this->assertNull($row->order_currency_code);
        $this->assertNull($row->collected_currency_code);
    }

    /**
     * Test 8: Rollback verification - running down() removes added columns and leaves defaults as NULL without YER.
     */
    public function test_rollback_down_leaves_defaults_as_null_without_yer(): void
    {
        $migration = require dirname(__DIR__, 2).'/src/Database/Migrations/2026_08_18_000001_normalize_currency_fields_in_delivery_tables.php';

        try {
            // Execute rollback down()
            $migration->down();

            $collectionCols = collect(DB::select('SHOW FULL COLUMNS FROM delivery_cash_collections'))->keyBy('Field');
            $settlementCols = collect(DB::select('SHOW FULL COLUMNS FROM delivery_settlements'))->keyBy('Field');

            // 1. Normalized columns must be dropped
            $this->assertFalse($collectionCols->has('order_currency_code'));
            $this->assertFalse($collectionCols->has('order_amount'));
            $this->assertFalse($collectionCols->has('collected_currency_code'));
            $this->assertFalse($collectionCols->has('collected_amount'));

            // 2. Defaults must remain NULL (no restoration of hardcoded 'YER')
            $this->assertNull($collectionCols['currency']->Default, 'Rollback must keep currency default as NULL, not YER');
            $this->assertNull($collectionCols['base_currency']->Default, 'Rollback must keep base_currency default as NULL, not YER');
            $this->assertNull($settlementCols['currency']->Default, 'Rollback must keep settlement currency default as NULL, not YER');
        } finally {
            // Restore up() state for any subsequent test isolation
            $migration->up();
        }
    }
}
