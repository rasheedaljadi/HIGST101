<?php

namespace Tests\Feature\Fulfillment;

use App\Models\AliExpressToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\PurchaseOrderItem;
use Webkul\Fulfillment\Models\FulfillmentApprovalRequest;
use Webkul\Fulfillment\Models\FulfillmentAuditLog;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\User\Models\Admin;

class FulfillmentAdminUITest extends TestCase
{
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            $this->artisan('migrate');
            DB::statement('PRAGMA foreign_keys = OFF;');
            
            // Seed core dependencies to satisfy Channel foreign keys
            DB::table('currencies')->insertOrIgnore(['id' => 1, 'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);
            DB::table('locales')->insertOrIgnore(['id' => 1, 'code' => 'en', 'name' => 'English', 'direction' => 'ltr']);
            DB::table('categories')->insertOrIgnore(['id' => 1, 'position' => 1, 'status' => 1, '_lft' => 1, '_rgt' => 2]);
            
            // Seed roles table so Bouncer check does not fail / redirect admin to login
            DB::table('roles')->insertOrIgnore([
                'id'              => 1,
                'name'            => 'Administrator',
                'permission_type' => 'all',
            ]);
        }

        if (! \Webkul\Core\Models\Channel::find(1)) {
            \Webkul\Core\Models\Channel::factory()->create(['id' => 1]);
        }

        if (! \Webkul\Attribute\Models\AttributeFamily::find(1)) {
            \Webkul\Attribute\Models\AttributeFamily::create([
                'id'              => 1,
                'code'            => 'default',
                'name'            => 'Default',
                'status'          => 1,
                'is_user_defined' => 0,
            ]);
        }

        // Seed a valid AliExpress token so client requests don't fail OAuth check
        AliExpressToken::create([
            'account'                 => 'test-account',
            'access_token'            => 'valid-test-access-token',
            'refresh_token'           => 'valid-test-refresh-token',
            'access_token_expires_at' => now()->addDays(7),
            'refresh_token_expires_at'=> now()->addDays(30),
        ]);

        // Create Admin user linked to role_id = 1
        $this->admin = Admin::create([
            'name'     => 'Super Administrator',
            'email'    => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => 1,
            'status'   => 1,
        ]);

        Cache::flush();
    }

    /**
     * Test admin menu visibility based on the feature flag.
     */
    public function test_admin_menu_visibility_config_toggle(): void
    {
        config(['fulfillment.admin_ui_enabled' => false]);
        
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.dropshipping.fulfillment.index'));
        $response->assertStatus(403);

        config(['fulfillment.admin_ui_enabled' => true]);

        $response = $this->get(route('admin.dropshipping.fulfillment.index'));
        $response->assertStatus(200);
    }

    /**
     * Test dashboard widgets (KPIs) loading and caching.
     */
    public function test_dashboard_kpis_rendering_on_dashboard(): void
    {
        config(['fulfillment.admin_ui_enabled' => true]);

        // Create PO in needs review state
        $order = Order::factory()->create(['status' => 'pending']);
        PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-kpis',
            'internal_reference' => 'ref-kpis',
            'state'              => 'needs_manual_review',
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.dropshipping.fulfillment.index'));
        
        $response->assertStatus(200);
        $response->assertSee(trans('fulfillment::app.admin.dashboard.manual-review-orders'));
        $response->assertSee(trans('fulfillment::app.admin.dashboard.success-rate'));
        $response->assertSee(trans('fulfillment::app.admin.dashboard.retry-rate'));

        // Check KPI cache exists
        $this->assertTrue(Cache::has('fulfillment_dashboard_kpis'));
    }

    /**
     * Test PO detail view screen.
     */
    public function test_admin_can_view_single_purchase_order_details(): void
    {
        config(['fulfillment.admin_ui_enabled' => true]);

        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-view',
            'internal_reference' => 'ref-view',
            'state'              => 'needs_manual_review',
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.dropshipping.fulfillment.view', $po->id));
        $response->assertStatus(200);
        $response->assertSee($po->internal_reference);
    }

    /**
     * Test retry action.
     */
    public function test_admin_retry_action_trigger(): void
    {
        config(['fulfillment.admin_ui_enabled' => true]);
        config(['fulfillment.retry_enabled' => true]);

        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-retry',
            'internal_reference' => 'ref-retry',
            'state'              => 'needs_manual_review',
        ]);

        // Mock the FulfillmentService to prevent running actual API execution on supplier
        $this->mock(\Webkul\Fulfillment\Services\FulfillmentService::class, function ($mock) {
            $mock->shouldReceive('executePurchaseOrder')->once()->andReturn(true);
        });

        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.dropshipping.fulfillment.retry', $po->id));
        $response->assertRedirect();
        
        // Assert state was reverted to pending for job queue/retry attempts
        $this->assertEquals(PurchaseOrder::STATE_PENDING, $po->fresh()->state);
        
        // Assert audit log exists
        $this->assertDatabaseHas('fulfillment_audit_logs', [
            'purchase_order_id' => $po->id,
            'action'            => 'retry',
            'user_id'           => $this->admin->id,
        ]);
    }

    /**
     * Test manual cancel action with approval workflow disabled.
     */
    public function test_admin_cancel_action_without_approval_workflow(): void
    {
        config(['fulfillment.admin_ui_enabled' => true]);
        config(['fulfillment.manual_cancel_enabled' => true]);
        config(['fulfillment.approval_workflow.enabled' => false]);

        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-cancel-direct',
            'internal_reference' => 'ref-cancel-direct',
            'state'              => 'needs_manual_review',
        ]);

        // Mock reflection logic
        $this->mock(\Webkul\Fulfillment\Services\FulfillmentService::class, function ($mock) {
            $mock->shouldReceive('reflectOnCustomerOrder')->once()->andReturn(true);
        });

        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.dropshipping.fulfillment.cancel', $po->id), [
            'reason' => 'Customer changed their mind about order', // at least 10 chars
        ]);

        $response->assertRedirect();
        $this->assertEquals(PurchaseOrder::STATE_CANCELED, $po->fresh()->state);

        // Audit log must have executed status in changes_payload
        $log = FulfillmentAuditLog::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('executed', $log->changes_payload['status']);
    }

    /**
     * Test manual cancel action with approval workflow enabled (requires supervisor approval).
     */
    public function test_admin_cancel_action_with_approval_workflow(): void
    {
        config(['fulfillment.admin_ui_enabled' => true]);
        config(['fulfillment.manual_cancel_enabled' => true]);
        config(['fulfillment.approval_workflow.enabled' => true]);

        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-cancel-workflow',
            'internal_reference' => 'ref-cancel-workflow',
            'state'              => 'submitted', // Active paid state
            'external_order_id'  => '1234567890',
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.dropshipping.fulfillment.cancel', $po->id), [
            'reason' => 'Customer requested cancel high risk',
        ]);

        $response->assertRedirect();
        
        // State remains submitted (suspended for approval)
        $this->assertEquals('submitted', $po->fresh()->state);

        // Approval request created
        $this->assertDatabaseHas('fulfillment_approval_requests', [
            'purchase_order_id' => $po->id,
            'action'            => 'cancel',
            'status'            => 'pending',
            'requested_by'      => $this->admin->id,
        ]);

        // Audit log created with pending_approval in changes_payload
        $log = FulfillmentAuditLog::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('pending_approval', $log->changes_payload['status']);
    }

    /**
     * Test approving a pending cancel request.
     */
    public function test_approving_a_pending_cancel_request(): void
    {
        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-approve',
            'internal_reference' => 'ref-approve',
            'state'              => 'submitted',
        ]);

        $request = FulfillmentApprovalRequest::create([
            'purchase_order_id' => $po->id,
            'requested_by'      => $this->admin->id,
            'action'            => 'cancel',
            'reason'            => 'Customer cancelled',
            'status'            => 'pending',
        ]);

        // Mock reflection logic
        $this->mock(\Webkul\Fulfillment\Services\FulfillmentService::class, function ($mock) {
            $mock->shouldReceive('reflectOnCustomerOrder')->once()->andReturn(true);
        });

        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.dropshipping.fulfillment.approve', $request->id));
        $response->assertRedirect();

        // Check request status updated to executed
        $this->assertEquals('executed', $request->fresh()->status);
        
        // Check PO state updated to canceled
        $this->assertEquals(PurchaseOrder::STATE_CANCELED, $po->fresh()->state);
    }

    /**
     * Test rejecting a pending cancel request.
     */
    public function test_rejecting_a_pending_cancel_request(): void
    {
        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-reject',
            'internal_reference' => 'ref-reject',
            'state'              => 'submitted',
        ]);

        $request = FulfillmentApprovalRequest::create([
            'purchase_order_id' => $po->id,
            'requested_by'      => $this->admin->id,
            'action'            => 'cancel',
            'reason'            => 'Customer cancelled',
            'status'            => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.dropshipping.fulfillment.reject', $request->id));
        $response->assertRedirect();

        // Check request status updated to rejected
        $this->assertEquals('rejected', $request->fresh()->status);
        
        // Check PO state remains unchanged
        $this->assertEquals('submitted', $po->fresh()->state);
    }

    /**
     * Test clear persistent alert banners.
     */
    public function test_clear_alert_banner_from_cache(): void
    {
        Cache::put('fulfillment_active_alerts', [
            [
                'id'        => 'alert_test_123',
                'severity'  => 'critical',
                'message'   => 'Token refresh failed',
                'timestamp' => now()->toDateTimeString(),
            ]
        ], 3600);

        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.dropshipping.fulfillment.clear-alert', 'alert_test_123'));
        $response->assertRedirect();

        // Check alert is removed from cache
        $alerts = Cache::get('fulfillment_active_alerts');
        $this->assertEmpty($alerts);
    }

    /**
     * Test ACL boundaries.
     */
    public function test_acl_unauthorized_user_blocked_from_actions(): void
    {
        // Create an unauthorized user role
        $unauthRole = \Webkul\User\Models\Role::create([
            'name'            => 'Unauthorised',
            'permission_type' => 'custom',
            'permissions'     => [], // no permissions
        ]);

        $unauthAdmin = Admin::create([
            'name'     => 'Unauthorised Staff',
            'email'    => 'unauth-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $unauthRole->id,
            'status'   => 1,
        ]);

        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-acl',
            'internal_reference' => 'ref-acl',
            'state'              => 'needs_manual_review',
        ]);

        $this->actingAs($unauthAdmin, 'admin');

        // Direct index page access should return 302 (bouncer logout redirect)
        $response = $this->get(route('admin.dropshipping.fulfillment.index'));
        $response->assertStatus(302);

        // Create a role with minimal permissions but NOT fulfillment
        $minRole = \Webkul\User\Models\Role::create([
            'name'            => 'Minimal Staff',
            'permission_type' => 'custom',
            'permissions'     => ['dashboard'], // dashboard permission only
        ]);

        $minAdmin = Admin::create([
            'name'     => 'Minimal Staff User',
            'email'    => 'min-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id'  => $minRole->id,
            'status'   => 1,
        ]);

        $this->actingAs($minAdmin, 'admin');

        // Direct index page access should abort with 401
        $response = $this->get(route('admin.dropshipping.fulfillment.index'));
        $response->assertStatus(401);

        // View page access should abort with 401
        $response = $this->get(route('admin.dropshipping.fulfillment.view', $po->id));
        $response->assertStatus(401);

        // Post AJAX endpoints should abort with 401
        $response = $this->post(route('admin.dropshipping.fulfillment.retry', $po->id));
        $response->assertStatus(401);
    }

    /**
     * Test O(1) query count invariant.
     */
    public function test_dashboard_query_count_is_constant_o_1(): void
    {
        $this->actingAs($this->admin, 'admin');

        // Warm-up request to compile views/boot services
        $this->get(route('admin.dropshipping.fulfillment.index'))->assertStatus(200);

        DB::enableQueryLog();

        // Case 1: Load dashboard with 1 PO
        $order1 = Order::factory()->create();
        PurchaseOrder::create([
            'order_id'           => $order1->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-q1',
            'internal_reference' => 'ref-q1',
            'state'              => 'needs_manual_review',
        ]);

        // Flush KPI cache to guarantee DB queries run
        Cache::forget('fulfillment_dashboard_kpis');
        DB::flushQueryLog();
        $response1 = $this->get(route('admin.dropshipping.fulfillment.index'));
        $response1->assertStatus(200);
        $queriesCase1 = count(DB::getQueryLog());

        // Case 2: Seed 5 more POs (Total 6 POs)
        for ($i = 2; $i <= 6; $i++) {
            $order = Order::factory()->create();
            PurchaseOrder::create([
                'order_id'           => $order->id,
                'provider'           => 'aliexpress',
                'supplier_signature' => 'ae-store-xyz',
                'idempotency_key'    => "test-key-q{$i}",
                'internal_reference' => "ref-q{$i}",
                'state'              => 'needs_manual_review',
            ]);
        }

        // Flush KPI cache again for second run
        Cache::forget('fulfillment_dashboard_kpis');
        DB::flushQueryLog();
        $response2 = $this->get(route('admin.dropshipping.fulfillment.index'));
        $response2->assertStatus(200);
        $queriesCase2 = count(DB::getQueryLog());

        DB::disableQueryLog();

        // DB queries count must be identical, proving O(1) complexity
        $this->assertEquals($queriesCase1, $queriesCase2);
    }

    /**
     * Test Queue Concurrency locks.
     */
    public function test_concurrent_po_submissions_are_blocked_by_lock(): void
    {
        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-lock',
            'internal_reference' => 'ref-lock',
            'state'              => 'pending',
        ]);

        // Manually lock the PO execution key
        $lockKey = "fulfillment-po-{$po->id}";
        $lock = Cache::lock($lockKey, 60);
        $this->assertTrue($lock->get());

        // Resolve FulfillmentService and run executePurchaseOrder
        $service = resolve(\Webkul\Fulfillment\Services\FulfillmentService::class);
        
        // Execute under lock must return immediately without updating state to submitting/submitted
        $service->executePurchaseOrder($po);
        $this->assertEquals('pending', $po->fresh()->state);

        // Release lock
        $lock->release();
    }

    /**
     * Test Observability and Correlation Trace.
     */
    public function test_observability_and_correlation_id_trace(): void
    {
        $order = Order::factory()->create();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'ae-store-xyz',
            'idempotency_key'    => 'test-key-obs',
            'internal_reference' => 'ref-obs',
            'state'              => 'pending',
        ]);

        // Record provider event
        $po->events()->create([
            'provider'       => 'aliexpress',
            'external_state' => 'placed',
            'source_type'    => 'webhook',
            'payload'        => ['request' => 'data'],
            'received_at'    => now(),
        ]);

        // Record audit log
        $po->auditLogs()->create([
            'user_id' => $this->admin->id,
            'action' => 'test_trace',
            'changes_payload' => ['status' => 'pending'],
        ]);

        // Retrieve and assert correlation links
        $log = FulfillmentAuditLog::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals($po->internal_reference, $log->purchaseOrder->internal_reference);

        $event = $po->events()->first();
        $this->assertNotNull($event);
        $this->assertEquals($po->id, $event->purchase_order_id);
    }
}
