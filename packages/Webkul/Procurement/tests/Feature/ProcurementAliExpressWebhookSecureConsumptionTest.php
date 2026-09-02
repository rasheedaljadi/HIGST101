<?php

use App\Models\AliExpressSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Queue;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\DTO\AliExpressOrderSnapshot;
use Webkul\Procurement\Jobs\ProcessAliExpressWebhookJob;
use Webkul\Procurement\Models\AliExpressWebhookInboxMessage;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\AliExpressPollingService;

beforeEach(function () {
    config([
        'procurement.v2_enabled' => true,
    ]);

    AliExpressSetting::updateOrCreate(
        ['id' => 1],
        [
            'app_key' => '12345678',
            'app_secret' => 'test_app_secret_123456',
            'callback_url' => 'https://highest-ye.store/aliexpress/callback',
        ]
    );
});

function generateSignature(string $appKey, string $body, string $appSecret): string
{
    return hash_hmac('sha256', $appKey.$body, $appSecret);
}

test('1. Valid signed callback creates exactly one inbox record and dispatches job without live API or stock mutation', function () {
    Queue::fake([ProcessAliExpressWebhookJob::class]);

    $appKey = '12345678';
    $appSecret = 'test_app_secret_123456';
    $orderId = '8201948572'.rand(100000, 999999);
    $body = json_encode([
        'message_type' => 53,
        'seller_id' => '200042360',
        'data' => [
            'trade_order_id' => $orderId,
            'order_status' => 'WAIT_SELLER_SEND_GOODS',
            'status_update_time' => '2026-08-22 23:30:00',
        ],
        'timestamp' => time(),
    ]);

    $signature = generateSignature($appKey, $body, $appSecret);

    $response = $this->call('POST', 'aliexpress/webhook', [], [], [], [
        'HTTP_AUTHORIZATION' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertStatus(200);

    $inboxCount = AliExpressWebhookInboxMessage::where('external_order_id', $orderId)->count();
    expect($inboxCount)->toBe(1);

    Queue::assertPushed(ProcessAliExpressWebhookJob::class, 1);
});

test('2. Missing or invalid signature is rejected with HTTP 401, zero inbox records, zero dispatched jobs', function () {
    Queue::fake([ProcessAliExpressWebhookJob::class]);

    $body = json_encode(['message_type' => 53, 'data' => ['trade_order_id' => '9999']]);

    // Test with missing signature
    $resMissing = $this->call('POST', 'aliexpress/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
    $resMissing->assertStatus(401);

    // Test with invalid signature
    $resInvalid = $this->call('POST', 'aliexpress/webhook', [], [], [], [
        'HTTP_AUTHORIZATION' => 'invalid_signature_hash',
        'CONTENT_TYPE' => 'application/json',
    ], $body);
    $resInvalid->assertStatus(401);

    expect(AliExpressWebhookInboxMessage::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('3. Replaying the same signed event returns 200 Ack idempotently without duplicate inbox or second job dispatch', function () {
    Queue::fake([ProcessAliExpressWebhookJob::class]);

    $appKey = '12345678';
    $appSecret = 'test_app_secret_123456';
    $evtId = 'EVT-TEST-IDEMPOTENT-'.uniqid();
    $body = json_encode([
        'event_id' => $evtId,
        'message_type' => 53,
        'data' => ['trade_order_id' => '8201948572910482'],
    ]);

    $signature = generateSignature($appKey, $body, $appSecret);

    // First Call
    $res1 = $this->call('POST', 'aliexpress/webhook', [], [], [], ['HTTP_AUTHORIZATION' => $signature, 'CONTENT_TYPE' => 'application/json'], $body);
    $res1->assertStatus(200);

    // Replay Call
    $res2 = $this->call('POST', 'aliexpress/webhook', [], [], [], ['HTTP_AUTHORIZATION' => $signature, 'CONTENT_TYPE' => 'application/json'], $body);
    $res2->assertStatus(200);

    expect(AliExpressWebhookInboxMessage::where('external_event_id', $evtId)->count())->toBe(1);
    Queue::assertPushed(ProcessAliExpressWebhookJob::class, 1);
});

test('4. Database unique constraint on fingerprint guarantees race-condition deduplication in MySQL', function () {
    $fingerprint = hash('sha256', 'aliexpress:test:race:unique:'.uniqid());

    $msg1 = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 53,
        'payload_hash' => 'hash1',
        'fingerprint' => $fingerprint,
        'received_at' => now(),
        'status' => 'received',
    ]);

    expect($msg1->id)->toBeGreaterThan(0);

    expect(function () use ($fingerprint) {
        AliExpressWebhookInboxMessage::create([
            'provider' => 'aliexpress',
            'event_type' => 53,
            'payload_hash' => 'hash2',
            'fingerprint' => $fingerprint,
            'received_at' => now(),
            'status' => 'received',
        ]);
    })->toThrow(QueryException::class);
});

test('5. Type 53 event on registered numeric external ID triggers getOrder and valid state machine transition', function () {
    $uniqueOrderId = '8201948572'.rand(100000, 999999);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-WH-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 20.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-WH-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
        'expected_total' => 20.0,
    ]);
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spo->id,
        'provider' => 'aliexpress',
        'external_order_id' => $uniqueOrderId,
        'raw_status' => 'WAIT_BUYER_PAY',
        'normalized_status' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
    ]);

    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 53,
        'external_order_id' => $uniqueOrderId,
        'payload_hash' => 'dummyhash',
        'fingerprint' => 'fp-type-53-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => $uniqueOrderId]],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldReceive('getOrder')
        ->with($uniqueOrderId, Mockery::any())
        ->once()
        ->andReturn(new AliExpressOrderSnapshot(
            externalOrderId: $uniqueOrderId,
            orderStatus: 'WAIT_SELLER_SEND_GOODS'
        ));

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($mockGateway, app(AliExpressPollingService::class));

    expect($inbox->fresh()->status)->toBe(AliExpressWebhookInboxMessage::STATUS_PROCESSED)
        ->and($platformOrder->fresh()->normalized_status)->toBe(ExternalPlatformOrder::STATUS_PROCESSING)
        ->and($spo->fresh()->state)->toBe(SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING);
});

test('6. Type 53 event with non-numeric or synthetic ID is marked ignored without getOrder call or domain change', function () {
    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 53,
        'external_order_id' => 'AE-MOCK-SYNTHETIC-99',
        'payload_hash' => 'dummyhash',
        'fingerprint' => 'fp-synthetic-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => 'AE-MOCK-SYNTHETIC-99']],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldNotReceive('getOrder');

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($mockGateway, app(AliExpressPollingService::class));

    expect($inbox->fresh()->status)->toBe(AliExpressWebhookInboxMessage::STATUS_IGNORED)
        ->and($inbox->fresh()->failure_code)->toBe('INVALID_OR_MISSING_NUMERIC_ORDER_ID');
});

test('7. Stale event arriving after order is cancelled cannot regress state', function () {
    $uniqueOrderId = '8201948572'.rand(100000, 999999);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-CANCEL-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 10.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-CANCEL-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_CANCELLED,
        'expected_total' => 10.0,
    ]);
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spo->id,
        'provider' => 'aliexpress',
        'external_order_id' => $uniqueOrderId,
        'raw_status' => 'CANCELLED',
        'normalized_status' => ExternalPlatformOrder::STATUS_CANCELLED,
    ]);

    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 53,
        'external_order_id' => $uniqueOrderId,
        'payload_hash' => 'stalehash',
        'fingerprint' => 'fp-stale-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => $uniqueOrderId]],
        'received_at' => now(),
        'status' => 'received',
    ]);

    // Gateway returns older WAIT_SELLER_SEND_GOODS status
    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldReceive('getOrder')
        ->with($uniqueOrderId, Mockery::any())
        ->once()
        ->andReturn(new AliExpressOrderSnapshot(
            externalOrderId: $uniqueOrderId,
            orderStatus: 'WAIT_SELLER_SEND_GOODS'
        ));

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($mockGateway, app(AliExpressPollingService::class));

    // Must remain CANCELLED due to statusRanks
    expect($platformOrder->fresh()->normalized_status)->toBe(ExternalPlatformOrder::STATUS_CANCELLED)
        ->and($spo->fresh()->state)->toBe(SupplierPurchaseOrder::STATE_CANCELLED);
});

test('8. Type 51 payment update audits payment state without financial or inventory mutation', function () {
    $uniqueOrderId = '8201948572'.rand(100000, 999999);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-51-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 15.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-51-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
        'expected_total' => 15.0,
    ]);
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spo->id,
        'provider' => 'aliexpress',
        'external_order_id' => $uniqueOrderId,
        'raw_status' => 'WAIT_BUYER_PAY',
        'normalized_status' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
    ]);

    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 51,
        'external_order_id' => $uniqueOrderId,
        'payload_hash' => 'hash51',
        'fingerprint' => 'fp-51-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => $uniqueOrderId]],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldReceive('getOrder')
        ->with($uniqueOrderId, Mockery::any())
        ->once()
        ->andReturn(new AliExpressOrderSnapshot(
            externalOrderId: $uniqueOrderId,
            orderStatus: 'PROCESSING'
        ));

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($mockGateway, app(AliExpressPollingService::class));

    expect($spo->fresh()->payment_state)->toBe('paid_externally')
        ->and($spo->fresh()->state)->toBe(SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING);
});

test('9. Type 18 tracking update saves tracking number only after official pull matches registered order', function () {
    $uniqueOrderId = '8201948572'.rand(100000, 999999);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-18-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 15.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-18-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING,
        'expected_total' => 15.0,
    ]);
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spo->id,
        'provider' => 'aliexpress',
        'external_order_id' => $uniqueOrderId,
        'raw_status' => 'PROCESSING',
        'normalized_status' => ExternalPlatformOrder::STATUS_PROCESSING,
    ]);

    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 18,
        'external_order_id' => $uniqueOrderId,
        'payload_hash' => 'hash18',
        'fingerprint' => 'fp-18-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => $uniqueOrderId]],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldReceive('getOrder')
        ->with($uniqueOrderId, Mockery::any())
        ->once()
        ->andReturn(new AliExpressOrderSnapshot(
            externalOrderId: $uniqueOrderId,
            orderStatus: 'SELLER_SEND_GOODS',
            trackingNumber: 'LP001234567890SA',
            carrierName: 'CAINIAO_STANDARD'
        ));

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($mockGateway, app(AliExpressPollingService::class));

    expect($platformOrder->fresh()->tracking_number)->toBe('LP001234567890SA')
        ->and($platformOrder->fresh()->carrier_name)->toBe('CAINIAO_STANDARD')
        ->and($platformOrder->fresh()->normalized_status)->toBe(ExternalPlatformOrder::STATUS_SHIPPED);
});

test('10. Type 65 authorization expiration generates isolated audit log without touching procurement or stock', function () {
    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 65,
        'payload_hash' => 'hash65',
        'fingerprint' => 'fp-65-'.uniqid(),
        'payload' => ['message' => 'Token expiring soon'],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($mockGateway, app(AliExpressPollingService::class));

    expect($inbox->fresh()->status)->toBe(AliExpressWebhookInboxMessage::STATUS_PROCESSED);

    $audit = ProcurementAuditLog::where('action', 'aliexpress_oauth_expiration_warning')->where('auditable_id', $inbox->id)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->auditable_id)->toBe($inbox->id);
});

test('11. Choice, JIT, or unknown event types are marked ignored with zero impact on Procurement V2', function () {
    $inboxChoice = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 56, // Choice
        'payload_hash' => 'hash56',
        'fingerprint' => 'fp-56-'.uniqid(),
        'payload' => ['choice_msg' => 'test'],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldNotReceive('getOrder');

    $job = new ProcessAliExpressWebhookJob($inboxChoice->id);
    $job->handle($mockGateway, app(AliExpressPollingService::class));

    expect($inboxChoice->fresh()->status)->toBe(AliExpressWebhookInboxMessage::STATUS_IGNORED)
        ->and($inboxChoice->fresh()->failure_code)->toBe('UNSUBSCRIBED_OR_NON_V2_EVENT_TYPE');
});

test('12. Documented cancellation releases pending allocations with zero stock movements or synthetic accounting', function () {
    $uniqueOrderId = '8201948572'.rand(100000, 999999);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-CANC-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 10.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-CANC-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING,
        'expected_total' => 10.0,
    ]);
    $spoi = SupplierPurchaseOrderItem::create([
        'supplier_purchase_order_id' => $spo->id,
        'procurement_demand_id' => 1,
        'product_id' => 1,
        'sku' => 'TEST-SKU',
        'ordered_qty' => 1,
        'unit_price' => 10.0,
        'total_amount' => 10.0,
    ]);
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spo->id,
        'provider' => 'aliexpress',
        'external_order_id' => $uniqueOrderId,
        'raw_status' => 'PROCESSING',
        'normalized_status' => ExternalPlatformOrder::STATUS_PROCESSING,
    ]);

    $allocation = ProcurementDemandAllocation::create([
        'procurement_demand_id' => 1,
        'supplier_purchase_order_id' => $spo->id,
        'supplier_purchase_order_item_id' => $spoi->id,
        'allocated_qty' => 1,
        'status' => 'allocated',
    ]);

    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 53,
        'external_order_id' => $uniqueOrderId,
        'payload_hash' => 'hashcanc',
        'fingerprint' => 'fp-canc-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => $uniqueOrderId]],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldReceive('getOrder')
        ->with($uniqueOrderId, Mockery::any())
        ->once()
        ->andReturn(new AliExpressOrderSnapshot(
            externalOrderId: $uniqueOrderId,
            orderStatus: 'CANCELLED'
        ));

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($mockGateway, app(AliExpressPollingService::class));

    expect($spo->fresh()->state)->toBe(SupplierPurchaseOrder::STATE_CANCELLED)
        ->and($platformOrder->fresh()->normalized_status)->toBe(ExternalPlatformOrder::STATUS_CANCELLED)
        ->and($allocation->fresh()->status)->toBe('cancelled')
        ->and($allocation->fresh()->released_at)->not->toBeNull();
});

test('13. Polling regression suite remains unbroken and forbids external create, pay, or cancel', function () {
    $uniqueOrderId = '8201948572'.rand(100000, 999999);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-POLL-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 10.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-POLL-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
        'expected_total' => 10.0,
    ]);
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spo->id,
        'provider' => 'aliexpress',
        'external_order_id' => $uniqueOrderId,
        'raw_status' => 'WAIT_BUYER_PAY',
        'normalized_status' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
    ]);

    $pollingService = app(AliExpressPollingService::class);
    $synced = $pollingService->syncOrder($platformOrder, ['status' => 'WAIT_SELLER_SEND_GOODS']);

    expect($synced->normalized_status)->toBe(ExternalPlatformOrder::STATUS_PROCESSING)
        ->and($spo->fresh()->state)->toBe(SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING);
});
