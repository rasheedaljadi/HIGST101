<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\ProviderAccount;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\ExternalOrder;
use Webkul\Fulfillment\Commands\CreateProcurementSessionCommand;
use Webkul\Fulfillment\Commands\ValidateSupplierAvailabilityCommand;
use Webkul\Fulfillment\Commands\SubmitSupplierOrderCommand;
use Webkul\Fulfillment\Commands\SyncSupplierOrderStatusCommand;
use Webkul\Fulfillment\Handlers\Procurement\CreateProcurementSessionHandler;
use Webkul\Fulfillment\Handlers\Procurement\ValidateSupplierAvailabilityHandler;
use Webkul\Fulfillment\Handlers\Procurement\SubmitSupplierOrderHandler;
use Webkul\Fulfillment\Handlers\Procurement\SyncSupplierOrderStatusHandler;
use Webkul\Fulfillment\Providers\AliExpress\FakeProviderSimulator;
use Webkul\Fulfillment\Services\Application\ReconciliationEngine;
use Webkul\Fulfillment\Services\Application\ProcurementInboxService;
use Webkul\Fulfillment\Services\Domain\SupplierFailureCompensationService;
use Webkul\Fulfillment\Services\Domain\ProviderHealthService;

class ProcurementPlatformTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        DB::table('provider_accounts')->delete();
        DB::table('procurement_sagas')->delete();
        DB::table('procurement_aggregates')->delete();
        DB::table('procurement_sessions')->delete();
        DB::table('procurement_commands')->delete();
        DB::table('procurement_inbox_events')->delete();
        DB::table('procurement_dead_letters')->delete();
        DB::table('outgoing_requests')->delete();
        DB::table('external_payload_archives')->delete();
        DB::table('external_orders')->delete();
        DB::table('external_order_projections')->delete();
        DB::table('procurement_dashboard_projections')->delete();
        DB::table('external_api_logs')->delete();
        DB::table('procurement_timelines')->delete();
        DB::table('procurement_metrics')->delete();
        DB::table('order_allocations')->delete();
        DB::table('domain_outbox_events')->delete();
        DB::table('ledger_entries')->delete();

        config(['fulfillment.providers.aliexpress_simulator' => FakeProviderSimulator::class]);
    }

    public function test_procurement_session_full_success_flow(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $allocation = OrderAllocation::create([
            'order_id'          => $order->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
            'source_type'       => 'supplier',
            'source_code'       => 'aliexpress',
            'state'             => 'reserved',
            'supplier_snapshot' => json_encode([
                'supplier_product_id' => '100200300',
                'supplier_sku_id'     => 'sku-abc',
                'requested_qty'       => 1,
                'available_qty'       => 1,
                'supplier_cost'       => 10.00
            ])
        ]);

        $corrId = 'corr-id-123';
        $causId = 'caus-id-123';

        $createCommand = new CreateProcurementSessionCommand(
            purchaseOrderId: 999,
            orderAllocationId: $allocation->id,
            correlationId: $corrId,
            causationId: $causId
        );
        $createHandler = app(CreateProcurementSessionHandler::class);
        $session = $createHandler->handle($createCommand);

        $this->assertEquals('CREATED', $session->state);
        $this->assertNotNull($session->procurement_aggregate_id);

        $validateCommand = new ValidateSupplierAvailabilityCommand(
            procurementSessionId: $session->id,
            correlationId: $corrId,
            causationId: $causId
        );
        $validateHandler = app(ValidateSupplierAvailabilityHandler::class);
        $session = $validateHandler->handle($validateCommand);

        $this->assertEquals('VALIDATED', $session->state);

        $session->state = 'READY_TO_SUBMIT';
        $session->save();

        $submitCommand = new SubmitSupplierOrderCommand(
            procurementSessionId: $session->id,
            correlationId: $corrId,
            causationId: $causId
        );
        $submitHandler = app(SubmitSupplierOrderHandler::class);
        $session = $submitHandler->handle($submitCommand);

        $this->assertEquals('SUBMITTED', $session->state);

        $extOrder = ExternalOrder::where('procurement_session_id', $session->id)->first();
        $this->assertNotNull($extOrder);
        $this->assertEquals('SIM-EXT-100200300', $extOrder->external_order_id);

        $outboxEvents = DB::table('domain_outbox_events')->get();
        $eventNames = $outboxEvents->pluck('event_name')->toArray();
        $this->assertContains('ProcurementStarted', $eventNames);
        $this->assertContains('ProcurementValidated', $eventNames);
        $this->assertContains('ProcurementSubmitted', $eventNames);
        $this->assertContains('SupplierOrderSubmitted', $eventNames);

        $syncCommand = new SyncSupplierOrderStatusCommand(
            procurementSessionId: $session->id,
            correlationId: $corrId,
            causationId: $causId
        );
        $syncHandler = app(SyncSupplierOrderStatusHandler::class);
        $session = $syncHandler->handle($syncCommand);

        $this->assertEquals('COMPLETED', $session->state);
    }

    public function test_supplier_price_change_blocks_submission(): void
    {
        $simulator = app(FakeProviderSimulator::class);
        $simulator->setFailureMode('price_changed');
        app()->instance(FakeProviderSimulator::class, $simulator);

        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $allocation = OrderAllocation::create([
            'order_id'          => $order->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
            'source_type'       => 'supplier',
            'source_code'       => 'aliexpress',
            'state'             => 'reserved',
            'supplier_snapshot' => json_encode([
                'supplier_product_id' => '100200300',
                'supplier_sku_id'     => 'sku-abc',
                'requested_qty'       => 1,
                'available_qty'       => 1,
                'supplier_cost'       => 10.00
            ])
        ]);

        $corrId = 'corr-id-123';
        $causId = 'caus-id-123';

        $createCommand = new CreateProcurementSessionCommand(
            purchaseOrderId: 999,
            orderAllocationId: $allocation->id,
            correlationId: $corrId,
            causationId: $causId
        );
        $session = app(CreateProcurementSessionHandler::class)->handle($createCommand);

        $priceSnap = $session->price_snapshot;
        $priceSnap['current_cost'] = 15.00;
        $session->price_snapshot = $priceSnap;
        $session->save();

        $validateCommand = new ValidateSupplierAvailabilityCommand(
            procurementSessionId: $session->id,
            correlationId: $corrId,
            causationId: $causId
        );
        $session = app(ValidateSupplierAvailabilityHandler::class)->handle($validateCommand);

        $this->assertEquals('FAILED', $session->state);
    }

    public function test_supplier_stock_failure_runs_compensation(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $allocation = OrderAllocation::create([
            'order_id'          => $order->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
            'source_type'       => 'supplier',
            'source_code'       => 'aliexpress',
            'state'             => 'reserved',
            'supplier_snapshot' => json_encode([
                'supplier_product_id' => '100200300',
                'supplier_sku_id'     => 'sku-abc',
                'requested_qty'       => 1,
                'available_qty'       => 0,
                'supplier_cost'       => 10.00
            ])
        ]);

        $corrId = 'corr-id-123';
        $causId = 'caus-id-123';

        $createCommand = new CreateProcurementSessionCommand(
            purchaseOrderId: 999,
            orderAllocationId: $allocation->id,
            correlationId: $corrId,
            causationId: $causId
        );
        $session = app(CreateProcurementSessionHandler::class)->handle($createCommand);

        $validateCommand = new ValidateSupplierAvailabilityCommand(
            procurementSessionId: $session->id,
            correlationId: $corrId,
            causationId: $causId
        );
        $session = app(ValidateSupplierAvailabilityHandler::class)->handle($validateCommand);

        $this->assertEquals('FAILED', $session->state);

        $compensationService = app(SupplierFailureCompensationService::class);
        $compensationService->compensate($session, 'Stockout');

        $allocation->refresh();
        $this->assertEquals('canceled', $allocation->state);
    }

    public function test_duplicate_supplier_webhook_is_ignored(): void
    {
        $inboxService = app(ProcurementInboxService::class);
        $eventId = 'evt-uniq-999';

        $payload = [
            'aliexpress_order_id' => 'ae-ext-9921',
            'status'              => 'shipped',
        ];

        $processorCalled = 0;
        $processor = function ($payload) use (&$processorCalled) {
            $processorCalled++;
        };

        $res1 = $inboxService->receive('aliexpress', $eventId, 'order_shipped', $payload, $processor);
        $this->assertTrue($res1);
        $this->assertEquals(1, $processorCalled);

        $res2 = $inboxService->receive('aliexpress', $eventId, 'order_shipped', $payload, $processor);
        $this->assertFalse($res2);
        $this->assertEquals(1, $processorCalled);
    }

    public function test_invalid_procurement_transition_is_rejected(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $allocation = OrderAllocation::create([
            'order_id'          => $order->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
            'source_type'       => 'supplier',
            'source_code'       => 'aliexpress',
            'state'             => 'reserved',
        ]);

        $session = ProcurementSession::create([
            'order_allocation_id' => $allocation->id,
            'state'               => 'CREATED',
            'correlation_id'      => 'corr',
            'causation_id'        => 'caus',
        ]);

        $this->expectException(\Webkul\Fulfillment\Exceptions\InvalidProcurementTransitionException::class);
        $session->transitionTo('COMPLETED');
    }

    public function test_provider_health_mttr_mtbf(): void
    {
        $healthService = app(ProviderHealthService::class);
        $res = $healthService->getHealthStatus('aliexpress');

        $this->assertEquals('HEALTHY', $res['status']);
        $this->assertArrayHasKey('mttr_hours', $res);
        $this->assertArrayHasKey('mtbf_hours', $res);
    }
}
