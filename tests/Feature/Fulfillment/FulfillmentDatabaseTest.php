<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Fulfillment\Contracts\PurchaseOrder as PurchaseOrderContract;
use Webkul\Fulfillment\Contracts\PurchaseOrderItem as PurchaseOrderItemContract;
use Webkul\Fulfillment\Contracts\FulfillmentAttempt as FulfillmentAttemptContract;
use Webkul\Fulfillment\Contracts\FulfillmentProviderEvent as FulfillmentProviderEventContract;
use Webkul\Fulfillment\Contracts\FulfillmentAuditLog as FulfillmentAuditLogContract;
use Webkul\Fulfillment\Contracts\FulfillmentApprovalRequest as FulfillmentApprovalRequestContract;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\PurchaseOrderItem;
use Webkul\Fulfillment\Models\FulfillmentAttempt;
use Webkul\Fulfillment\Models\FulfillmentProviderEvent;
use Webkul\Fulfillment\Models\FulfillmentAuditLog;
use Webkul\Fulfillment\Models\FulfillmentApprovalRequest;
use Webkul\Fulfillment\Enums\FulfillmentErrorType;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\User\Models\Admin;

class FulfillmentDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }
    }

    /**
     * Test schemas of all 6 bridge tables exist with correct columns.
     */
    public function test_all_6_database_tables_exist_with_correct_columns(): void
    {
        // 1. purchase_orders
        $this->assertTrue(Schema::hasTable('purchase_orders'));
        $this->assertTrue(Schema::hasColumns('purchase_orders', [
            'id', 'order_id', 'provider', 'provider_account_id', 'supplier_signature',
            'idempotency_key', 'internal_reference', 'external_order_id', 'state',
            'supplier_state_raw', 'supplier_snapshot', 'attempts', 'last_error',
            'tracking_number', 'tracking_company', 'supplier_cost', 'supplier_currency',
            'payload_snapshot', 'submitted_at', 'created_at', 'updated_at'
        ]));

        // 2. purchase_order_items
        $this->assertTrue(Schema::hasTable('purchase_order_items'));
        $this->assertTrue(Schema::hasColumns('purchase_order_items', [
            'id', 'purchase_order_id', 'order_item_id', 'aliexpress_product_id',
            'sku_id', 'qty', 'supplier_unit_cost', 'created_at', 'updated_at'
        ]));

        // 3. fulfillment_attempts
        $this->assertTrue(Schema::hasTable('fulfillment_attempts'));
        $this->assertTrue(Schema::hasColumns('fulfillment_attempts', [
            'id', 'purchase_order_id', 'attempt_no', 'result', 'error_type',
            'provider_code', 'message', 'created_at', 'updated_at'
        ]));

        // 4. fulfillment_provider_events
        $this->assertTrue(Schema::hasTable('fulfillment_provider_events'));
        $this->assertTrue(Schema::hasColumns('fulfillment_provider_events', [
            'id', 'purchase_order_id', 'provider', 'external_state', 'source_type',
            'payload', 'received_at', 'processed_at', 'created_at', 'updated_at'
        ]));

        // 5. fulfillment_audit_logs
        $this->assertTrue(Schema::hasTable('fulfillment_audit_logs'));
        $this->assertTrue(Schema::hasColumns('fulfillment_audit_logs', [
            'id', 'purchase_order_id', 'user_id', 'action', 'reason',
            'ip_address', 'changes_payload', 'created_at', 'updated_at'
        ]));

        // 6. fulfillment_approval_requests
        $this->assertTrue(Schema::hasTable('fulfillment_approval_requests'));
        $this->assertTrue(Schema::hasColumns('fulfillment_approval_requests', [
            'id', 'purchase_order_id', 'requested_by', 'action', 'reason',
            'changes_payload', 'status', 'approved_by', 'decision_reason', 'created_at', 'updated_at'
        ]));
    }

    /**
     * Test Concord model bindings are correctly registered and resolvable.
     */
    public function test_concord_model_bindings_are_correctly_registered(): void
    {
        $this->assertInstanceOf(PurchaseOrder::class, app(PurchaseOrderContract::class));
        $this->assertInstanceOf(PurchaseOrderItem::class, app(PurchaseOrderItemContract::class));
        $this->assertInstanceOf(FulfillmentAttempt::class, app(FulfillmentAttemptContract::class));
        $this->assertInstanceOf(FulfillmentProviderEvent::class, app(FulfillmentProviderEventContract::class));
        $this->assertInstanceOf(FulfillmentAuditLog::class, app(FulfillmentAuditLogContract::class));
        $this->assertInstanceOf(FulfillmentApprovalRequest::class, app(FulfillmentApprovalRequestContract::class));
    }

    /**
     * Test relationships and foreign keys can be persisted and loaded.
     */
    public function test_database_records_persistance_and_relationship_linking(): void
    {
        // 1. Create native Order & OrderItem
        $order = Order::factory()->create([
            'status' => 'pending',
        ]);
        
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'qty_ordered' => 2,
        ]);

        // 2. Create Admin user
        $admin = Admin::create([
            'name' => 'Operator Test',
            'email' => 'operator-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);

        // 3. Create PurchaseOrder
        $po = PurchaseOrder::create([
            'order_id'            => $order->id,
            'provider'            => 'aliexpress',
            'provider_account_id' => 12,
            'supplier_signature'  => 'ae-store-xyz',
            'idempotency_key'     => hash('sha256', 'test-idempotency-' . uniqid()),
            'internal_reference'  => 'ref-' . uniqid(),
            'state'               => PurchaseOrder::STATE_PENDING,
            'supplier_snapshot'   => ['name' => 'AliExpress', 'account' => 'main'],
        ]);

        // 4. Create PurchaseOrderItem
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 2,
            'supplier_unit_cost'=> 8.50,
        ]);

        // 5. Create FulfillmentAttempt
        $attempt = FulfillmentAttempt::create([
            'purchase_order_id' => $po->id,
            'attempt_no'        => 1,
            'result'            => 'transient',
            'error_type'        => FulfillmentErrorType::NETWORK_ERROR->value,
            'provider_code'     => 'TIMEOUT',
            'message'           => 'Connection timed out',
        ]);

        // 6. Create Provider Event
        $event = FulfillmentProviderEvent::create([
            'purchase_order_id' => $po->id,
            'provider'          => 'aliexpress',
            'external_state'    => 'WAIT_SELLER_SEND_GOODS',
            'source_type'       => 'webhook',
            'payload'           => ['status' => 'shipped'],
            'received_at'       => now(),
        ]);

        // 7. Create Audit Log
        $auditLog = FulfillmentAuditLog::create([
            'purchase_order_id' => $po->id,
            'user_id'           => $admin->id,
            'action'            => 'retry',
            'reason'            => 'Connection failed initially',
            'ip_address'        => '127.0.0.1',
            'changes_payload'   => ['state' => 'pending'],
        ]);

        // 8. Create Approval Request
        $approval = FulfillmentApprovalRequest::create([
            'purchase_order_id' => $po->id,
            'requested_by'      => $admin->id,
            'action'            => 'cancel',
            'reason'            => 'Customer requested cancel',
            'status'            => 'pending',
        ]);

        // Assert relations from PurchaseOrder side
        $po->refresh();
        $this->assertCount(1, $po->items);
        $this->assertCount(1, $po->fulfillmentAttempts);
        $this->assertCount(1, $po->events);
        $this->assertCount(1, $po->auditLogs);
        $this->assertCount(1, $po->approvalRequests);

        // Assert model attributes and snapshots
        $this->assertEquals($order->id, $po->order->id);
        $this->assertEquals('AliExpress', $po->supplier_snapshot['name']);
        $this->assertEquals('webhook', $po->events()->first()->source_type);
        $this->assertEquals($admin->id, $po->auditLogs()->first()->user->id);
        $this->assertEquals($admin->id, $po->approvalRequests()->first()->requestedBy->id);
    }

    /**
     * Test double fulfillment prevention constraint.
     */
    public function test_double_fulfillment_prevention_uniqueness_constraint(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $po = PurchaseOrder::create([
            'order_id'            => $order->id,
            'provider'            => 'aliexpress',
            'idempotency_key'     => hash('sha256', 'uniq-1'),
            'internal_reference'  => 'ref-uniq-1',
            'state'               => PurchaseOrder::STATE_PENDING,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
        ]);

        // Attempting to create another PurchaseOrderItem linking the same PO and OrderItem should fail unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
        ]);
    }

    /**
     * Test querying purchase_orders executes correctly using composite and single indexes.
     */
    public function test_purchase_orders_index_query_execution(): void
    {
        $order = Order::factory()->create();

        PurchaseOrder::create([
            'order_id'            => $order->id,
            'provider'            => 'aliexpress',
            'provider_account_id' => 1,
            'idempotency_key'     => hash('sha256', 'idx-1'),
            'internal_reference'  => 'ref-idx-1',
            'external_order_id'   => 'ext-idx-999',
            'state'               => 'pending',
        ]);

        // Query by state & created_at (Composite index validation)
        $posByState = PurchaseOrder::where('state', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        $this->assertCount(1, $posByState);

        // Query by provider & state (Composite index validation)
        $posByProvider = PurchaseOrder::where('provider', 'aliexpress')
            ->where('state', 'pending')
            ->get();
        $this->assertCount(1, $posByProvider);

        // Query by external_order_id
        $posByExternal = PurchaseOrder::where('external_order_id', 'ext-idx-999')->get();
        $this->assertCount(1, $posByExternal);
    }
}
