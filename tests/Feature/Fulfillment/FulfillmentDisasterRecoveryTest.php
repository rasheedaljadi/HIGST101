<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Sales\Models\Order;
use Webkul\User\Models\Admin;

class FulfillmentDisasterRecoveryTest extends TestCase
{
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            $this->artisan('migrate');
            DB::statement('PRAGMA foreign_keys = OFF;');
            
            DB::table('currencies')->insertOrIgnore(['id' => 1, 'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);
            DB::table('locales')->insertOrIgnore(['id' => 1, 'code' => 'en', 'name' => 'English', 'direction' => 'ltr']);
            DB::table('categories')->insertOrIgnore(['id' => 1, 'position' => 1, 'status' => 1, '_lft' => 1, '_rgt' => 2]);
            
            DB::table('roles')->insertOrIgnore([
                'id'              => 1,
                'name'            => 'Administrator',
                'permission_type' => 'all',
            ]);
        }

        if (! \Webkul\Core\Models\Channel::find(1)) {
            \Webkul\Core\Models\Channel::factory()->create(['id' => 1]);
        }

        $this->admin = Admin::create([
            'name'     => 'Super Administrator',
            'email'    => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => 1,
            'status'   => 1,
        ]);
    }

    /**
     * Create an order with shipping address to satisfy API requirements.
     */
    protected function createOrderWithAddress()
    {
        $order = Order::factory()->create();
        $order->addresses()->create([
            'address_type' => \Webkul\Sales\Models\OrderAddress::ADDRESS_TYPE_SHIPPING,
            'first_name'   => 'John',
            'last_name'    => 'Doe',
            'email'        => 'john@example.com',
            'address1'     => '123 Test St',
            'address'      => '123 Test St',
            'city'         => 'Test City',
            'state'        => 'NY',
            'postcode'     => '10001',
            'country'      => 'US',
            'phone'        => '1234567890',
        ]);
        return $order;
    }

    /**
     * Test recovery from a worker crash while in 'submitting' state.
     */
    public function test_worker_crash_recovery_via_reconciliation(): void
    {
        $order = $this->createOrderWithAddress();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'crash-key',
            'internal_reference' => 'ref-crash',
            'state'              => 'submitting', // Worker crashed while submitting
            'attempts'           => 1,
        ]);

        // Mock the provider to simulate reconciliation finding a placed order on AliExpress
        $mockProvider = $this->mock(\Webkul\Fulfillment\Contracts\FulfillmentProviderInterface::class);
        $mockProvider->shouldReceive('findByReference')->once()->andReturn('AE-ORDER-CRASH-RECOVERED');

        $registry = $this->mock(\Webkul\Fulfillment\Services\FulfillmentProviderRegistry::class);
        $registry->shouldReceive('resolve')->with('aliexpress')->andReturn($mockProvider);
        $this->app->instance(\Webkul\Fulfillment\Services\FulfillmentProviderRegistry::class, $registry);

        $service = resolve(\Webkul\Fulfillment\Services\FulfillmentService::class);
        
        // Execute PO submission again (re-run crashed job)
        $service->executePurchaseOrder($po);

        // State must recover to submitted and get the correct external order ID to prevent double purchase
        $this->assertEquals(PurchaseOrder::STATE_SUBMITTED, $po->fresh()->state);
        $this->assertEquals('AE-ORDER-CRASH-RECOVERED', $po->fresh()->external_order_id);
    }

    /**
     * Test DB outage transaction rollback safety.
     */
    public function test_db_outage_transaction_rollback_safety(): void
    {
        $order = $this->createOrderWithAddress();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'db-outage-key',
            'internal_reference' => 'ref-db-outage',
            'state'              => 'needs_manual_review',
        ]);

        $this->actingAs($this->admin, 'admin');

        // Mock FulfillmentService reflectOnCustomerOrder to throw DB exception
        $this->mock(\Webkul\Fulfillment\Services\FulfillmentService::class, function ($mock) {
            $mock->shouldReceive('reflectOnCustomerOrder')->andThrow(new \Illuminate\Database\QueryException('sqlite', 'select *', [], new \Exception('DB Outage')));
        });

        // Call cancel action which runs in transaction
        $response = $this->post(route('admin.dropshipping.fulfillment.cancel', $po->id), [
            'reason' => 'Customer cancel but DB fails',
        ]);

        // Assert response redirects back with error flashed in session
        $response->assertRedirect();
        
        // Assert state did not update to canceled and was rolled back completely
        $this->assertEquals('needs_manual_review', $po->fresh()->state);
    }

    /**
     * Test lock timeout recovery.
     */
    public function test_lock_timeout_recovery(): void
    {
        $order = $this->createOrderWithAddress();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'timeout-key',
            'internal_reference' => 'ref-timeout',
            'state'              => 'pending',
        ]);

        $lockKey = "fulfillment-po-{$po->id}";
        $lock = Cache::lock($lockKey, 1); // 1 second TTL
        $this->assertTrue($lock->get());

        // Wait for lock to expire
        sleep(2);

        // Another worker/attempt should be able to acquire lock
        $newLock = Cache::lock($lockKey, 10);
        $this->assertTrue($newLock->get());
        $newLock->release();
    }
}
