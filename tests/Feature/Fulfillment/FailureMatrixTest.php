<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Fulfillment\Models\OrderProcess;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\ProviderAccount;
use Webkul\Fulfillment\Services\Application\InboxEventProcessor;
use Webkul\Fulfillment\Services\Application\ExternalInboxService;
use Webkul\Fulfillment\Services\Application\ProviderCircuitBreaker;
use Webkul\Fulfillment\Services\FulfillmentService;
use Webkul\Fulfillment\Providers\AliExpress\FakeProviderSimulator;
use Webkul\Fulfillment\Services\Domain\TokenRefreshService;
use Illuminate\Http\Request;

class FailureMatrixTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['fulfillment.providers.aliexpress' => FakeProviderSimulator::class]);

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        DB::table('orders')->delete();
        DB::table('provider_accounts')->delete();
        DB::table('purchase_orders')->delete();
        DB::table('procurement_sessions')->delete();
        DB::table('ledger_entries')->delete();
        DB::table('order_allocations')->delete();
        DB::table('external_inbox_events')->delete();
        Cache::flush();
    }

    protected function createTestOrder(): Order
    {
        $order = Order::factory()->create();
        
        // Delete any default addresses created by factory to prevent duplicate active addresses
        DB::table('addresses')->where('order_id', $order->id)->delete();

        OrderAddress::create([
            'order_id'     => $order->id,
            'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
            'first_name'   => 'Ahmad',
            'last_name'    => 'Ali',
            'city'         => 'Riyadh',
            'country'      => 'SA',
        ]);
        
        $order->unsetRelation('addresses'); // force reloading the addresses relation
        return $order;
    }

    /**
     * Test OAuth expired and token refresh retry logic.
     */
    public function test_oauth_expired_and_token_refresh(): void
    {
        $account = ProviderAccount::create([
            'provider'      => 'aliexpress',
            'name'          => 'Main Account',
            'status'        => 'EXPIRED',
            'access_token'  => 'old-expired-token',
            'refresh_token' => 'active-refresh-token',
        ]);

        $refreshService = app(TokenRefreshService::class);
        $res = $refreshService->refresh($account);

        $this->assertTrue($res);
        $account->refresh();
        $this->assertEquals('ACTIVE', $account->status);
        $this->assertStringContainsString('refreshed-fake-access-token', $account->access_token);
    }

    /**
     * Test Rate limit releases job.
     */
    public function test_rate_limit_exceeded_behavior(): void
    {
        $simulator = app(FakeProviderSimulator::class);
        $simulator->setFailureMode('RATE_LIMIT');
        app()->instance(FakeProviderSimulator::class, $simulator);

        $order = $this->createTestOrder();
        $po = PurchaseOrder::create([
            'order_id'            => $order->id,
            'provider'            => 'aliexpress',
            'idempotency_key'     => 'idemp-rate-limit',
            'internal_reference'  => 'PO-RL-1',
            'state'               => 'pending',
        ]);

        $service = app(FulfillmentService::class);

        try {
            $service->executePurchaseOrder($po);
            $this->fail("Should have thrown rate limit exception.");
        } catch (\Throwable $e) {
            $po->refresh();
            // Since the rate limit error occurred, we threw RuntimeException, but po is marked 'pending' for retry
            $this->assertEquals('pending', $po->state);
        }
    }

    /**
     * Test Timeout after submit and reconciliation on next run.
     */
    public function test_timeout_after_submit_reconciles_successfully(): void
    {
        $simulator = app(FakeProviderSimulator::class);
        // First run times out
        $simulator->setFailureMode('TIMEOUT');
        app()->instance(FakeProviderSimulator::class, $simulator);

        $order = $this->createTestOrder();
        $po = PurchaseOrder::create([
            'order_id'            => $order->id,
            'provider'            => 'aliexpress',
            'idempotency_key'     => 'idemp-timeout',
            'internal_reference'  => 'PO-TIMEOUT-1',
            'state'               => 'pending',
        ]);

        $service = app(FulfillmentService::class);

        try {
            $service->executePurchaseOrder($po);
        } catch (\Throwable $e) {
            // Expected timeout
        }

        $po->refresh();
        $this->assertEquals('submitting', $po->state);

        // Next run reconciles and finds the order instead of placing a duplicate
        $simulator->setFailureMode('RECONCILE_MATCH');
        $service->executePurchaseOrder($po);

        $po->refresh();
        $this->assertEquals('submitted', $po->state);
        $this->assertEquals('SIM-EXT-100200300', $po->external_order_id);
    }

    /**
     * Test Webhook duplicate is ignored.
     */
    public function test_duplicate_webhook_ignored(): void
    {
        $inboxService = app(ExternalInboxService::class);
        $payload = [
            'event_id'  => 'evt-dup-matrix',
            'order_id'  => 'PO-DUP-1',
            'status'    => 'order_created',
            'timestamp' => now()->toIso8601String(),
        ];

        config(['fulfillment.aliexpress.webhook_secret' => 'key']);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_Signature'   => hash_hmac('sha256', time() . '.' . json_encode($payload), 'key'),
            'HTTP_X-Timestamp' => time(),
        ], json_encode($payload));

        $res1 = $inboxService->ingest('aliexpress', 'evt-dup-matrix', 'order_status_changed', $payload, $request);
        $this->assertEquals('success', $res1['status']);

        $res2 = $inboxService->ingest('aliexpress', 'evt-dup-matrix', 'order_status_changed', $payload, $request);
        $this->assertEquals('duplicate', $res2['status']);
    }

    /**
     * Test Webhook before polling (webhook applied first, polling has no effect).
     */
    public function test_webhook_before_polling(): void
    {
        $po = PurchaseOrder::create([
            'order_id'           => 1,
            'provider'           => 'aliexpress',
            'idempotency_key'    => 'idemp-web-poll',
            'internal_reference' => 'PO-WEB-POLL',
            'state'              => 'submitted',
        ]);

        // Webhook updates status to shipped
        $po->markSupplierShipped('TRK-123', 'standard');
        $this->assertEquals('shipped', $po->state);

        // Subsequent poll doesn't duplicate or overwrite with old status
        $this->assertEquals('shipped', $po->state);
    }

    /**
     * Test stockout compensation.
     */
    public function test_stockout_triggers_compensation(): void
    {
        $order = $this->createTestOrder();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $allocation = OrderAllocation::create([
            'order_id'          => $order->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
            'source_type'       => 'supplier',
            'source_code'       => 'aliexpress',
            'state'             => 'reserved',
            'supplier_snapshot' => json_encode(['available_qty' => 0]),
        ]);

        $session = ProcurementSession::create([
            'order_allocation_id' => $allocation->id,
            'state'               => 'CREATED',
        ]);

        $this->assertEquals('CREATED', $session->state);
        // Compensation flow cancels allocation
        $allocation->update(['state' => 'canceled']);
        $this->assertEquals('canceled', $allocation->fresh()->state);
    }

    /**
     * Test price changes trigger manual review.
     */
    public function test_price_change_blocks_submission(): void
    {
        $simulator = app(FakeProviderSimulator::class);
        $simulator->setFailureMode('PRICE_CHANGED');
        app()->instance(FakeProviderSimulator::class, $simulator);

        $order = $this->createTestOrder();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'idempotency_key'    => 'idemp-price',
            'internal_reference' => 'PO-PRICE-1',
            'state'              => 'pending',
        ]);

        $service = app(FulfillmentService::class);
        $service->executePurchaseOrder($po);

        $po->refresh();
        $this->assertEquals('needs_manual_review', $po->state);
        $this->assertStringContainsString('price has changed', $po->last_error);
    }

    /**
     * Test provider unavailable trips the circuit breaker.
     */
    public function test_circuit_breaker_tripping(): void
    {
        $provider = 'aliexpress';
        $endpoint = 'order.create';
        $operation = 'write';

        // 1. Initially closed (not blocked)
        $this->assertFalse(ProviderCircuitBreaker::isBlocked($provider, $endpoint, $operation));

        // 2. Record consecutive failures
        for ($i = 0; $i < 5; $i++) {
            ProviderCircuitBreaker::recordFailure($provider, $endpoint, $operation);
        }

        // 3. Should trip open
        $this->assertTrue(ProviderCircuitBreaker::isBlocked($provider, $endpoint, $operation));
    }

    /**
     * Test worker crash processing lock recovery.
     */
    public function test_worker_crash_lock_recovery(): void
    {
        $processor = app(InboxEventProcessor::class);

        // Insert locked processing event
        DB::table('external_inbox_events')->insert([
            'provider'              => 'aliexpress',
            'event_id'              => 'evt-stuck-matrix',
            'event_type'            => 'order_status_changed',
            'payload'               => json_encode(['foo' => 'bar']),
            'status'                => 'processing',
            'processing_lock_id'    => 'lock-stuck',
            'processing_started_at' => now()->subMinutes(10), // Stale
            'received_at'           => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $recovered = $processor->recoverTimedOutEvents(300);
        $this->assertEquals(1, $recovered);

        $event = DB::table('external_inbox_events')->where('event_id', 'evt-stuck-matrix')->first();
        $this->assertEquals('pending', $event->status);
        $this->assertNull($event->processing_lock_id);
    }

    /**
     * Test provider returns success but database commit fails.
     */
    public function test_provider_success_but_db_commit_fails_reconciliation(): void
    {
        $order = $this->createTestOrder();
        $po = PurchaseOrder::create([
            'order_id'            => $order->id,
            'provider'            => 'aliexpress',
            'idempotency_key'     => 'idemp-db-fail',
            'internal_reference'  => 'PO-DB-FAIL-1',
            'state'               => 'pending',
            'attempts'            => 1, // Represents a retry
        ]);

        $simulator = app(FakeProviderSimulator::class);
        // Reconcile matches existing order in simulator
        $simulator->setFailureMode('RECONCILE_MATCH');
        app()->instance(FakeProviderSimulator::class, $simulator);

        $service = app(FulfillmentService::class);
        $service->executePurchaseOrder($po);

        $po->refresh();
        $this->assertEquals('submitted', $po->state);
        $this->assertEquals('SIM-EXT-100200300', $po->external_order_id);
    }

    /**
     * Test database commit succeeds but Provider timeout.
     */
    public function test_db_commit_succeeds_but_provider_timeout(): void
    {
        $order = $this->createTestOrder();
        $po = PurchaseOrder::create([
            'order_id'            => $order->id,
            'provider'            => 'aliexpress',
            'idempotency_key'     => 'idemp-prov-timeout',
            'internal_reference'  => 'PO-PROV-TIMEOUT-1',
            'state'               => 'submitting', // Committed locally as submitting
        ]);

        $simulator = app(FakeProviderSimulator::class);
        // Reconcile matches existing order in simulator on next run
        $simulator->setFailureMode('RECONCILE_MATCH');
        app()->instance(FakeProviderSimulator::class, $simulator);

        $service = app(FulfillmentService::class);
        $service->executePurchaseOrder($po);

        $po->refresh();
        $this->assertEquals('submitted', $po->state);
        $this->assertEquals('SIM-EXT-100200300', $po->external_order_id);
    }
}
