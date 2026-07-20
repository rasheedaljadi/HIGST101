<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Fulfillment\DataObjects\NormalizedExternalEvent;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressEventNormalizer;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressWebhookVerifier;
use Webkul\Fulfillment\Providers\CJ\CJEventNormalizer;
use Webkul\Fulfillment\Services\Application\ExternalInboxService;
use Webkul\Fulfillment\Services\Application\InboxEventProcessor;
use Webkul\Fulfillment\Services\Domain\ExternalStateMapper;
use Webkul\Sales\Models\Order;

class ProviderContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        // Clear tables
        DB::table('external_inbox_events')->delete();
        DB::table('external_health_checks')->delete();
        DB::table('financial_timeline')->delete();
        PurchaseOrder::truncate();
    }

    /**
     * Test webhook inbox deduplication and DLQ transitions.
     */
    public function test_webhook_inbox_deduplication_and_dlq_transitions(): void
    {
        $inboxService = app(ExternalInboxService::class);
        $processor = app(InboxEventProcessor::class);

        $payload = [
            'event_id'   => 'evt-uniq-100',
            'order_id'   => 'PO-TEST-1',
            'status'     => 'ORDER_CREATED',
            'timestamp'  => now()->toIso8601String(),
        ];

        // 1. Success on first ingest (Signed request)
        config(['fulfillment.aliexpress.webhook_secret' => 'super-secret-key-1122']);
        $body = json_encode($payload);
        $timestamp = time();
        $sig = hash_hmac('sha256', $timestamp . '.' . $body, 'super-secret-key-1122');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_Signature'   => $sig,
            'HTTP_X-Timestamp' => $timestamp,
        ], $body);

        $res1 = $inboxService->ingest('aliexpress', 'evt-uniq-100', 'order_status_changed', $payload, $request);
        $this->assertEquals('success', $res1['status']);
        $this->assertNotNull($res1['record_id']);

        // 2. Duplicate rejection on second ingest
        $res2 = $inboxService->ingest('aliexpress', 'evt-uniq-100', 'order_status_changed', $payload, $request);
        $this->assertEquals('duplicate', $res2['status']);

        // Create the PO aggregate referenced in the inbox payload to avoid "PO not found" failure
        PurchaseOrder::create([
            'id'                 => 9999,
            'internal_reference' => 'PO-TEST-1',
            'order_id'           => 1,
            'provider'           => 'aliexpress',
            'state'              => 'pending',
        ]);

        // 3. Process inbox event successfully
        $processed = $processor->processPending();
        $this->assertEquals(1, $processed);

        $inboxEvent = DB::table('external_inbox_events')->where('event_id', 'evt-uniq-100')->first();
        $this->assertEquals('processed', $inboxEvent->status);
        $this->assertEquals(1, $inboxEvent->attempts);

        // 4. Test DLQ transition (status: dead_letter after maxAttempts exceeded)
        // Reset to pending, delete PO to force processing error "PO not found"
        PurchaseOrder::truncate();
        DB::table('external_inbox_events')->where('event_id', 'evt-uniq-100')->update([
            'status'   => 'pending',
            'attempts' => 2, // Set to 2, so the next fail hits 3 (maxAttempts)
        ]);

        $processor->processPending();

        $inboxEvent = DB::table('external_inbox_events')->where('event_id', 'evt-uniq-100')->first();
        $this->assertEquals('dead_letter', $inboxEvent->status);
        $this->assertEquals(3, $inboxEvent->attempts);
        $this->assertNotNull($inboxEvent->last_error);
    }

    /**
     * Test signature and timestamp verification.
     */
    public function test_aliexpress_webhook_signature_and_timestamp_validation(): void
    {
        $verifier = new AliExpressWebhookVerifier();
        $body = json_encode(['foo' => 'bar']);
        $timestamp = time();

        config(['fulfillment.aliexpress.webhook_secret' => 'super-secret-key-1122']);

        // Generate correct signature
        $sig = hash_hmac('sha256', $timestamp . '.' . $body, 'super-secret-key-1122');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_Signature'   => $sig,
            'HTTP_X-Timestamp' => $timestamp,
        ], $body);

        $this->assertTrue($verifier->verify($request));

        // Invalid signature
        $badRequest = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_Signature'   => 'invalid-signature-here',
            'HTTP_X-Timestamp' => $timestamp,
        ], $body);
        $this->assertFalse($verifier->verify($badRequest));

        // Stale timestamp (replay protection)
        $staleTimestamp = time() - 400; // > 300 seconds
        $staleSig = hash_hmac('sha256', $staleTimestamp . '.' . $body, 'super-secret-key-1122');
        $staleRequest = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_Signature'   => $staleSig,
            'HTTP_X-Timestamp' => $staleTimestamp,
        ], $body);
        $this->assertFalse($verifier->verify($staleRequest));
    }

    /**
     * Test AliExpress webhook payload normalization using JSON fixtures.
     */
    public function test_aliexpress_webhook_payload_normalization_from_fixtures(): void
    {
        $normalizer = new AliExpressEventNormalizer();

        // 1. Load order_created.json
        $createdJson = file_get_contents(base_path('tests/Fixtures/AliExpress/order_created.json'));
        $createdPayload = json_decode($createdJson, true);
        $evtCreated = $normalizer->normalize($createdPayload);

        $this->assertEquals('ae-evt-10021', $evtCreated->eventId);
        $this->assertEquals('aliexpress', $evtCreated->externalSystem);
        $this->assertEquals('order_created', $evtCreated->eventType);
        $this->assertEquals('ae-ext-9921', $evtCreated->resourceId);
        $this->assertEquals('1.0', $evtCreated->schemaVersion);

        // 2. Load order_shipped.json
        $shippedJson = file_get_contents(base_path('tests/Fixtures/AliExpress/order_shipped.json'));
        $shippedPayload = json_decode($shippedJson, true);
        $evtShipped = $normalizer->normalize($shippedPayload);

        $this->assertEquals('ae-evt-10022', $evtShipped->eventId);
        $this->assertEquals('aliexpress', $evtShipped->externalSystem);
        $this->assertEquals('order_shipped', $evtShipped->eventType);
        $this->assertEquals('TRK987654321', $evtShipped->attributes['tracking_number']);
        $this->assertEquals('aliexpress_standard', $evtShipped->attributes['carrier']);
    }

    /**
     * Test PO Aggregate state machine transitions via State Mapper and action DTO.
     */
    public function test_purchase_order_state_machine_transitions_via_mapper(): void
    {
        $normalizer = new AliExpressEventNormalizer();
        $mapper = new ExternalStateMapper();

        // Create PO in draft/pending status
        $po = PurchaseOrder::create([
            'id'                 => 101,
            'internal_reference' => 'PO-101',
            'order_id'           => 1,
            'provider'           => 'aliexpress',
            'state'              => 'pending',
        ]);

        // Load shipped JSON, normalize, and map to action DTO
        $shippedJson = file_get_contents(base_path('tests/Fixtures/AliExpress/order_shipped.json'));
        $payload = json_decode($shippedJson, true);
        
        $normalizedEvent = $normalizer->normalize($payload);
        $actionDto = $mapper->map($normalizedEvent);

        $this->assertEquals('MARK_SHIPPED', $actionDto->action);
        $this->assertEquals('TRK987654321', $actionDto->attributes['tracking_number']);

        // First transition to submitted state
        $po->submit('ae-ext-9921', []);
        $this->assertEquals('submitted', $po->state);

        // Transition to shipped state via action DTO application
        $po->markSupplierShipped($actionDto->attributes['tracking_number'], $actionDto->attributes['carrier']);
        $this->assertEquals('shipped', $po->state);
        $this->assertEquals('TRK987654321', $po->tracking_number);

        // Assert invalid transition raises Exception
        $this->expectException(\DomainException::class);
        $po->submit('new-ext-id', []); // invalid shipped -> submitted transition
    }

    /**
     * Test processing lock recovery.
     */
    public function test_provider_failure_recovery_and_idempotency(): void
    {
        $processor = app(InboxEventProcessor::class);

        // Insert stuck processing event in inbox
        DB::table('external_inbox_events')->insert([
            'provider'              => 'aliexpress',
            'event_id'              => 'evt-stuck-1',
            'event_type'            => 'order_status_changed',
            'payload'               => json_encode(['foo' => 'bar']),
            'status'                => 'processing',
            'processing_lock_id'    => 'lock-abc-123',
            'processing_started_at' => now()->subMinutes(10), // Stale lock
            'received_at'           => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // Recover stale locks
        $recovered = $processor->recoverTimedOutEvents(timeoutSeconds: 300);
        $this->assertEquals(1, $recovered);

        $inboxEvent = DB::table('external_inbox_events')->where('event_id', 'evt-stuck-1')->first();
        $this->assertEquals('pending', $inboxEvent->status);
        $this->assertNull($inboxEvent->processing_lock_id);
    }

    /**
     * Test AliExpress and CJ payloads translate to the same exact unified domain action (Provider Independence).
     */
    public function test_external_event_is_provider_independent(): void
    {
        $aeNormalizer = new AliExpressEventNormalizer();
        $cjNormalizer = new CJEventNormalizer();
        $mapper = new ExternalStateMapper();

        // 1. AliExpress Shipped Event
        $aePayload = [
            'event_id'        => 'ae-1',
            'status'          => 'SELLER_SEND_GOODS',
            'order_id'        => 'ae-ext-9921',
            'tracking_number' => 'TRK-AE-11',
            'carrier_code'    => 'ae_standard',
        ];
        $aeNormalized = $aeNormalizer->normalize($aePayload);
        $aeAction = $mapper->map($aeNormalized);

        // 2. CJ Shipped Event
        $cjPayload = [
            'cj_event_id'      => 'cj-1',
            'cj_status'        => 'SHIPPED',
            'orderNo'          => 'cj-ext-9921',
            'trackingNumber'   => 'TRK-CJ-22',
            'logisticsCompany' => 'cj_logistics',
        ];
        $cjNormalized = $cjNormalizer->normalize($cjPayload);
        $cjAction = $mapper->map($cjNormalized);

        // Assert BOTH map to the same action DTO type
        $this->assertEquals('MARK_SHIPPED', $aeAction->action);
        $this->assertEquals('MARK_SHIPPED', $cjAction->action);

        // Assert attributes are correctly normalized
        $this->assertEquals('TRK-AE-11', $aeAction->attributes['tracking_number']);
        $this->assertEquals('TRK-CJ-22', $cjAction->attributes['tracking_number']);
    }
}
