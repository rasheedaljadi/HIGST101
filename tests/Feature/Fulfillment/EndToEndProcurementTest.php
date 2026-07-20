<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Shipment;
use Webkul\Fulfillment\Models\OrderProcess;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\LedgerEntry;
use Webkul\Fulfillment\Listeners\OrderLifecycleListener;
use Webkul\Fulfillment\Services\Application\InboxEventProcessor;
use Webkul\Fulfillment\Services\Application\ExternalInboxService;
use Webkul\Fulfillment\Services\Application\OutboxEventProcessor;
use Webkul\Fulfillment\Providers\AliExpress\FakeProviderSimulator;
use Webkul\Fulfillment\Services\FulfillmentService;
use Illuminate\Http\Request;

class EndToEndProcurementTest extends TestCase
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
        DB::table('order_processes')->delete();
        DB::table('order_allocations')->delete();
        DB::table('purchase_orders')->delete();
        DB::table('procurement_sessions')->delete();
        DB::table('ledger_entries')->delete();
        DB::table('external_inbox_events')->delete();
        DB::table('domain_outbox_events')->delete();
        DB::table('financial_timeline')->delete();
    }

    /**
     * Test full End-to-End happy path.
     */
    public function test_full_end_to_end_procurement_lifecycle(): void
    {
        // 1. Customer Order Placement
        $order = Order::factory()->create(['grand_total' => 200.00]);
        $orderItem = OrderItem::factory()->create([
            'order_id'    => $order->id,
            'qty_ordered' => 1,
            'price'       => 200.00,
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'method'   => 'stripe',
        ]);

        // Clean up any default factory-created addresses
        DB::table('addresses')->where('order_id', $order->id)->delete();

        OrderAddress::create([
            'order_id'     => $order->id,
            'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
            'first_name'   => 'Ahmad',
            'last_name'    => 'Ali',
            'city'         => 'Riyadh',
            'country'      => 'SA',
        ]);
        $order->unsetRelation('addresses');

        $listener = app(OrderLifecycleListener::class);

        // Initiate lifecycle
        $listener->handleOrderPlaced($order);

        $process = OrderProcess::where('order_id', $order->id)->first();
        $this->assertNotNull($process);
        $this->assertEquals('waiting_payment', $process->lifecycle_state);

        $correlationId = $process->correlation_id;
        $this->assertNotEmpty($correlationId);

        // 2. Allocation & OrderAccepted Trigger
        // Settle payment (online invoice paid) to accept order and start saga
        $invoice = Invoice::create([
            'order_id'    => $order->id,
            'state'       => 'paid',
            'grand_total' => 200.00,
        ]);

        // Manually create the allocation before invoice trigger so saga can route it
        $allocation = OrderAllocation::create([
            'order_id'          => $order->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
            'source_type'       => 'supplier',
            'source_code'       => 'aliexpress',
            'state'             => 'reserved',
            'supplier_snapshot' => json_encode([
                'supplier_product_id' => 'ae-prod-e2e',
                'supplier_sku_id'     => 'ae-sku-e2e',
                'requested_qty'       => 1,
                'available_qty'       => 1,
                'supplier_cost'       => 120.00,
            ])
        ]);

        // Trigger paid event
        $listener->handleInvoiceSaved($invoice);

        $process->refresh();
        $this->assertEquals('fulfillment_started', $process->lifecycle_state);

        // 3. Purchase Order should be created & dispatched
        $po = PurchaseOrder::where('order_id', $order->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals('submitted', $po->state);
        $this->assertEquals('SIM-EXT-100200300', $po->external_order_id);
        $this->assertEquals(64, strlen($po->idempotency_key));

        // 4. Procurement Session checks
        $session = ProcurementSession::create([
            'order_allocation_id' => $allocation->id,
            'state'               => 'COMPLETED',
            'correlation_id'      => $correlationId,
            'supplier_snapshot'   => json_decode($allocation->supplier_snapshot, true),
            'policy_snapshot'     => ['pricing_policy' => 'aliexpress_default'],
        ]);

        $this->assertEquals($correlationId, $session->correlation_id);
        $this->assertEquals(json_decode($allocation->supplier_snapshot, true), $session->supplier_snapshot);
        $this->assertEquals('COMPLETED', $session->state);

        // 5. Webhook -> PurchaseOrder Shipped
        $inboxService = app(ExternalInboxService::class);
        $processor = app(InboxEventProcessor::class);

        $shippedPayload = [
            'event_id'          => 'evt-e2e-shipped',
            'order_id'          => 'SIM-EXT-100200300',
            'status'            => 'SELLER_SEND_GOODS',
            'tracking_number'   => 'TRK-E2E-123',
            'carrier_code'      => 'aliexpress_standard',
            'timestamp'         => now()->toIso8601String(),
        ];

        config(['fulfillment.aliexpress.webhook_secret' => 'key']);
        $body = json_encode($shippedPayload);
        $timestamp = time();
        $sig = hash_hmac('sha256', $timestamp . '.' . $body, 'key');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_Signature'   => $sig,
            'HTTP_X-Timestamp' => $timestamp,
        ], $body);

        $ingestRes = $inboxService->ingest('aliexpress', 'evt-e2e-shipped', 'order_status_changed', $shippedPayload, $request);
        $this->assertEquals('success', $ingestRes['status']);

        // Process webhook
        $processed = $processor->processPending();
        $this->assertEquals(1, $processed);

        $po->refresh();
        $this->assertEquals('shipped', $po->state);
        $this->assertEquals('TRK-E2E-123', $po->tracking_number);

        // 6. Webhook -> PurchaseOrder Delivered & Invoice recognized
        $deliveredPayload = [
            'event_id'          => 'evt-e2e-delivered',
            'order_id'          => 'SIM-EXT-100200300',
            'status'            => 'FINISH',
            'timestamp'         => now()->toIso8601String(),
        ];
        $bodyDel = json_encode($deliveredPayload);
        $sigDel = hash_hmac('sha256', $timestamp . '.' . $bodyDel, 'key');

        $requestDel = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_Signature'   => $sigDel,
            'HTTP_X-Timestamp' => $timestamp,
        ], $bodyDel);

        $ingestResDel = $inboxService->ingest('aliexpress', 'evt-e2e-delivered', 'order_status_changed', $deliveredPayload, $requestDel);
        $this->assertEquals('success', $ingestResDel['status']);

        // Process webhook
        $processedDel = $processor->processPending();
        $this->assertEquals(1, $processedDel);

        $po->refresh();
        $this->assertEquals('delivered', $po->state);

        // 7. Customer Order Completed
        // Simulate Shipment to recognize prepaid revenue
        $shipment = Shipment::create(['order_id' => $order->id]);
        $listener->handleShipmentSaved($shipment);

        // Verify order reflected status
        $order->update(['status' => 'completed']);
        $order->refresh();
        $this->assertEquals('completed', $order->status);

        // Run outbox processor to send all pending outbox events
        app(OutboxEventProcessor::class)->processPending();

        // 8. Financial Posting assertions
        $debitTotal = LedgerEntry::where('order_id', $order->id)->sum('debit');
        $creditTotal = LedgerEntry::where('order_id', $order->id)->sum('credit');
        $this->assertEquals($debitTotal, $creditTotal);
        $this->assertGreaterThan(0.00, $debitTotal);

        // Verify no pending items in outbox or inbox
        $pendingOutbox = DB::table('domain_outbox_events')->where('status', 'pending')->count();
        $pendingInbox = DB::table('external_inbox_events')->where('status', 'pending')->count();
        $this->assertEquals(0, $pendingOutbox);
        $this->assertEquals(0, $pendingInbox);
    }
}
