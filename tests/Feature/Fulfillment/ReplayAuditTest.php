<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Fulfillment\Models\OrderProcess;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Services\Application\ExternalInboxService;
use Webkul\Fulfillment\Services\Application\InboxEventProcessor;
use Illuminate\Http\Request;

class ReplayAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        DB::table('external_inbox_events')->delete();
        DB::table('purchase_orders')->delete();
        DB::table('procurement_sessions')->delete();
        DB::table('ledger_entries')->delete();
        DB::table('domain_outbox_events')->delete();
        DB::table('financial_timeline')->delete();
    }

    /**
     * Test webhook event replay results in zero duplicate side effects.
     */
    public function test_replay_results_in_zero_new_side_effects(): void
    {
        $inboxService = app(ExternalInboxService::class);
        $processor = app(InboxEventProcessor::class);

        // Setup base PO aggregate
        $po = PurchaseOrder::create([
            'id'                 => 9999,
            'internal_reference' => 'PO-REPLAY-1',
            'order_id'           => 1,
            'provider'           => 'aliexpress',
            'state'              => 'pending',
            'idempotency_key'    => 'replay-idemp-1',
        ]);

        $payload = [
            'event_id'   => 'evt-replay-100',
            'order_id'   => 'PO-REPLAY-1',
            'status'     => 'ORDER_CREATED',
            'timestamp'  => now()->toIso8601String(),
        ];

        // 1. Ingest first webhook (success)
        config(['fulfillment.aliexpress.webhook_secret' => 'super-secret-key-1122']);
        $body = json_encode($payload);
        $timestamp = time();
        $sig = hash_hmac('sha256', $timestamp . '.' . $body, 'super-secret-key-1122');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_Signature'   => $sig,
            'HTTP_X-Timestamp' => $timestamp,
        ], $body);

        $res1 = $inboxService->ingest('aliexpress', 'evt-replay-100', 'order_status_changed', $payload, $request);
        $this->assertEquals('success', $res1['status']);

        // Process event
        $processor->processPending();
        $po->refresh();
        $this->assertEquals('submitted', $po->state);

        // Baseline stats
        $baselinePoCount = PurchaseOrder::count();
        $baselineSessionCount = ProcurementSession::count();
        $baselineLedgerCount = DB::table('ledger_entries')->count();
        $baselineOutboxCount = DB::table('domain_outbox_events')->count();
        $baselineTimelineCount = DB::table('financial_timeline')->count();
        $baselineInboxCount = DB::table('external_inbox_events')->count();

        // 2. Replay webhook ingestion (deduplication check should reject)
        $res2 = $inboxService->ingest('aliexpress', 'evt-replay-100', 'order_status_changed', $payload, $request);
        $this->assertEquals('duplicate', $res2['status']);

        // 3. Manually simulate processing again to confirm zero side effects
        $processor->processPending();

        // Assert zero new entries created in the system
        $this->assertEquals($baselinePoCount, PurchaseOrder::count());
        $this->assertEquals($baselineSessionCount, ProcurementSession::count());
        $this->assertEquals($baselineLedgerCount, DB::table('ledger_entries')->count());
        $this->assertEquals($baselineOutboxCount, DB::table('domain_outbox_events')->count());
        $this->assertEquals($baselineTimelineCount, DB::table('financial_timeline')->count());
        $this->assertEquals($baselineInboxCount, DB::table('external_inbox_events')->count());
    }
}
