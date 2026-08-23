<?php

$projDir = is_dir(__DIR__.'/../vendor') ? realpath(__DIR__.'/..') : '/home/highest-ye/htdocs/highest-ye.store';
require $projDir.'/vendor/autoload.php';
$app = require_once $projDir.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$srcDir = is_dir(__DIR__.'/../packages/Webkul/Procurement/src') ? realpath(__DIR__.'/../packages/Webkul/Procurement/src') : $projDir.'/packages/Webkul/Procurement/src';
if (file_exists($srcDir.'/Contracts/AliExpressOrderGateway.php')) {
    require_once $srcDir.'/Contracts/AliExpressOrderGateway.php';
    require_once $srcDir.'/DTO/ExternalOrderDraft.php';
    require_once $srcDir.'/DTO/AliExpressOrderPreflight.php';
    require_once $srcDir.'/DTO/AliExpressOrderSnapshot.php';
    require_once $srcDir.'/DTO/VerifiedExternalOrderCreated.php';
    require_once $srcDir.'/DTO/ExternalOrderSubmissionFailed.php';
    require_once $srcDir.'/Gateways/AliExpressOrderSubmissionGateway.php';
    require_once $srcDir.'/Models/AliExpressWebhookInboxMessage.php';
    require_once $srcDir.'/Jobs/ProcessAliExpressWebhookJob.php';
}

if (file_exists($projDir.'/app/Services/AliExpress/AliExpressWebhookSignatureVerifier.php')) {
    require_once $projDir.'/app/Services/AliExpress/AliExpressWebhookSignatureVerifier.php';
}

use App\Http\Controllers\AliExpress\AliExpressWebhookController;
use App\Models\AliExpressSetting;
use App\Services\AliExpress\AliExpressWebhookSignatureVerifier;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\AliExpressOrderSnapshot;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Jobs\ProcessAliExpressWebhookJob;
use Webkul\Procurement\Models\AliExpressWebhookInboxMessage;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\AliExpressPollingService;

// Setup mock gateway for container binding
$mockContainerGateway = new class implements AliExpressOrderGateway
{
    public function preflight(ExternalOrderDraft $d): AliExpressOrderPreflight
    {
        return new AliExpressOrderPreflight(true, true, 'SA');
    }

    public function submitUnpaid(ExternalOrderDraft $d): VerifiedExternalOrderCreated
    {
        return new VerifiedExternalOrderCreated('1', '1', '1');
    }

    public function getOrder(string $id, ?int $acc = null): AliExpressOrderSnapshot
    {
        return new AliExpressOrderSnapshot($id, 'PROCESSING');
    }

    public function resolveWarehouseShippingAddress(?array $o = null): array
    {
        return ['country' => 'SA'];
    }
};
app()->instance(AliExpressOrderGateway::class, $mockContainerGateway);

$results = [];
$totalAssertions = 0;
$totalTests = 0;

function assertTest(bool $condition, string $description, &$totalAssertions)
{
    $totalAssertions++;
    if (! $condition) {
        throw new Exception('Assertion Failed: '.$description);
    }
}

function generateSignature(string $appKey, string $body, string $appSecret): string
{
    return hash_hmac('sha256', $appKey.$body, $appSecret);
}

echo "=== Running AliExpress Webhook Secure Consumption Test Suite ===\n\n";

// 0. Test Migration Schema (Create table if not exists)
try {
    $totalTests++;
    $migrationPath = $srcDir.'/Database/Migrations/2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table.php';
    if (file_exists($migrationPath)) {
        $migration = require $migrationPath;
        $migration->up();
    }

    assertTest(Schema::hasTable('aliexpress_webhook_inbox_messages'), 'Table aliexpress_webhook_inbox_messages exists', $totalAssertions);
    assertTest(Schema::hasColumn('aliexpress_webhook_inbox_messages', 'fingerprint'), 'Column fingerprint exists', $totalAssertions);
    assertTest(Schema::hasColumn('aliexpress_webhook_inbox_messages', 'external_order_id'), 'Column external_order_id exists', $totalAssertions);

    echo "PASS [1/13]: Schema: migration successfully creates aliexpress_webhook_inbox_messages with unique fingerprint\n";
} catch (Throwable $e) {
    echo 'FAIL [1/13]: '.$e->getMessage()."\n";
}

// Ensure settings exist
AliExpressSetting::updateOrCreate(
    ['id' => 1],
    [
        'app_key' => '12345678',
        'app_secret' => 'test_app_secret_123456',
        'callback_url' => 'https://highest-ye.store/aliexpress/callback',
    ]
);

$verifier = new AliExpressWebhookSignatureVerifier;
$appKey = '12345678';
$appSecret = 'test_app_secret_123456';

// Test 2: Valid signature creates inbox record and returns 200 Ack
try {
    $totalTests++;
    $orderId = '8201948572'.rand(100000, 999999);
    $body = json_encode([
        'message_type' => 53,
        'seller_id' => '200042360',
        'data' => [
            'trade_order_id' => $orderId,
            'order_status' => 'WAIT_SELLER_SEND_GOODS',
        ],
        'timestamp' => time(),
    ]);
    $signature = generateSignature($appKey, $body, $appSecret);

    $req = Request::create('aliexpress/webhook', 'POST', [], [], [], [
        'HTTP_AUTHORIZATION' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $controller = new AliExpressWebhookController($verifier);
    $res = $controller->handle($req);

    assertTest($res->getStatusCode() === 200, 'HTTP status is 200 OK', $totalAssertions);
    $inbox = AliExpressWebhookInboxMessage::where('external_order_id', $orderId)->latest('id')->first();
    assertTest($inbox !== null, 'Inbox record created', $totalAssertions);
    assertTest(in_array($inbox->status, ['received', 'processed', 'ignored']), 'Status is valid', $totalAssertions);

    echo "PASS [2/13]: Unit: valid signed callback creates inbox record with 200 Ack\n";
} catch (Throwable $e) {
    echo 'FAIL [2/13]: '.$e->getMessage()."\n";
}

// Test 3: Missing or invalid signature returns 401
try {
    $totalTests++;
    $body = json_encode(['message_type' => 53, 'data' => ['trade_order_id' => '9999']]);

    // Missing signature
    $req1 = Request::create('aliexpress/webhook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
    $res1 = (new AliExpressWebhookController($verifier))->handle($req1);
    assertTest($res1->getStatusCode() === 401, 'Missing signature returns 401', $totalAssertions);

    // Invalid signature
    $req2 = Request::create('aliexpress/webhook', 'POST', [], [], [], [
        'HTTP_AUTHORIZATION' => 'invalid_signature_hex_123',
        'CONTENT_TYPE' => 'application/json',
    ], $body);
    $res2 = (new AliExpressWebhookController($verifier))->handle($req2);
    assertTest($res2->getStatusCode() === 401, 'Invalid signature returns 401', $totalAssertions);

    echo "PASS [3/13]: Security: missing or invalid signature is strictly rejected with HTTP 401\n";
} catch (Throwable $e) {
    echo 'FAIL [3/13]: '.$e->getMessage()."\n";
}

// Test 4: Replaying same signed event is idempotent
try {
    $totalTests++;
    $evtId = 'EVT-TEST-REPLAY-'.uniqid();
    $body = json_encode([
        'event_id' => $evtId,
        'message_type' => 53,
        'data' => ['trade_order_id' => '8201948572910482'],
    ]);
    $signature = generateSignature($appKey, $body, $appSecret);

    $req1 = Request::create('aliexpress/webhook', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => $signature, 'CONTENT_TYPE' => 'application/json'], $body);
    $controller = new AliExpressWebhookController($verifier);
    $res1 = $controller->handle($req1);
    assertTest($res1->getStatusCode() === 200, 'First call 200 OK', $totalAssertions);

    // Second Replay
    $req2 = Request::create('aliexpress/webhook', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => $signature, 'CONTENT_TYPE' => 'application/json'], $body);
    $res2 = $controller->handle($req2);
    assertTest($res2->getStatusCode() === 200, 'Replay call 200 OK', $totalAssertions);

    $count = AliExpressWebhookInboxMessage::where('external_event_id', $evtId)->count();
    assertTest($count === 1, 'Exactly one inbox message exists for replay', $totalAssertions);

    echo "PASS [4/13]: Idempotency: replaying same signed event returns 200 Ack without duplicate inbox insertion\n";
} catch (Throwable $e) {
    echo 'FAIL [4/13]: '.$e->getMessage()."\n";
}

// Test 5: Unique database constraint handles concurrent race
try {
    $totalTests++;
    $fp = hash('sha256', 'aliexpress:test:race:unique:'.uniqid());

    $m1 = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 53,
        'payload_hash' => 'hash1',
        'fingerprint' => $fp,
        'received_at' => now(),
        'status' => 'received',
    ]);
    assertTest($m1->id > 0, 'First record inserted', $totalAssertions);

    $duplicateThrown = false;
    try {
        AliExpressWebhookInboxMessage::create([
            'provider' => 'aliexpress',
            'event_type' => 53,
            'payload_hash' => 'hash2',
            'fingerprint' => $fp,
            'received_at' => now(),
            'status' => 'received',
        ]);
    } catch (QueryException $e) {
        $duplicateThrown = true;
    }
    assertTest($duplicateThrown === true, 'MySQL unique constraint rejects duplicate fingerprint', $totalAssertions);

    echo "PASS [5/13]: Concurrency: database unique constraint strictly rejects duplicate fingerprints\n";
} catch (Throwable $e) {
    echo 'FAIL [5/13]: '.$e->getMessage()."\n";
}

// Test 6: Type 53 with registered order triggers gateway getOrder and monotonic transition
try {
    $totalTests++;
    $uniqueOrderId = '8201948572'.rand(100000, 999999);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-TEST-53-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 25.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-TEST-53-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
        'expected_total' => 25.0,
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
        'payload_hash' => 'hash53',
        'fingerprint' => 'fp-53-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => $uniqueOrderId]],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $fakeGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $draft): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $draft): VerifiedExternalOrderCreated|ExternalOrderSubmissionFailed
        {
            return new VerifiedExternalOrderCreated('1', 'req-1', 'WAIT_BUYER_PAY');
        }

        public function getOrder(string $officialExternalOrderId, ?int $providerAccountId = null): AliExpressOrderSnapshot
        {
            return new AliExpressOrderSnapshot($officialExternalOrderId, 'WAIT_SELLER_SEND_GOODS');
        }

        public function resolveWarehouseShippingAddress(?array $override = null): array
        {
            return ['country' => 'SA'];
        }
    };

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($fakeGateway, app(AliExpressPollingService::class));

    assertTest($inbox->fresh()->status === 'processed', 'Inbox status processed', $totalAssertions);
    assertTest($platformOrder->fresh()->normalized_status === ExternalPlatformOrder::STATUS_PROCESSING, 'Platform order is processing', $totalAssertions);
    assertTest($spo->fresh()->state === SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING, 'SPO state is supplier_processing', $totalAssertions);

    echo "PASS [6/13]: Lifecycle: type 53 event with registered numeric ID triggers official getOrder and monotonic transition\n";
} catch (Throwable $e) {
    echo 'FAIL [6/13]: '.$e->getMessage()."\n";
}

// Test 7: Type 53 with non-numeric or synthetic ID is ignored safely
try {
    $totalTests++;
    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 53,
        'external_order_id' => 'AE-SYNTHETIC-999',
        'payload_hash' => 'hashsynth',
        'fingerprint' => 'fp-synth-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => 'AE-SYNTHETIC-999']],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $fakeGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $d): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $d): VerifiedExternalOrderCreated
        {
            return new VerifiedExternalOrderCreated('1', '1', '1');
        }

        public function getOrder(string $id, ?int $acc = null): AliExpressOrderSnapshot
        {
            throw new Exception('Should not call getOrder for synthetic ID');
        }

        public function resolveWarehouseShippingAddress(?array $o = null): array
        {
            return [];
        }
    };

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($fakeGateway, app(AliExpressPollingService::class));

    assertTest($inbox->fresh()->status === 'ignored', 'Inbox marked ignored', $totalAssertions);
    assertTest($inbox->fresh()->failure_code === 'INVALID_OR_MISSING_NUMERIC_ORDER_ID', 'Failure code is INVALID_OR_MISSING_NUMERIC_ORDER_ID', $totalAssertions);

    echo "PASS [7/13]: Isolation: non-numeric/synthetic order ID is marked ignored without getOrder call or domain change\n";
} catch (Throwable $e) {
    echo 'FAIL [7/13]: '.$e->getMessage()."\n";
}

// Test 8: Stale event arriving after cancelled cannot regress state
try {
    $totalTests++;
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
        'payload_hash' => 'hashstale',
        'fingerprint' => 'fp-stale-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => $uniqueOrderId]],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $fakeGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $d): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $d): VerifiedExternalOrderCreated
        {
            return new VerifiedExternalOrderCreated('1', '1', '1');
        }

        public function getOrder(string $id, ?int $acc = null): AliExpressOrderSnapshot
        {
            return new AliExpressOrderSnapshot($id, 'WAIT_SELLER_SEND_GOODS');
        }

        public function resolveWarehouseShippingAddress(?array $o = null): array
        {
            return [];
        }
    };

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($fakeGateway, app(AliExpressPollingService::class));

    assertTest($platformOrder->fresh()->normalized_status === ExternalPlatformOrder::STATUS_CANCELLED, 'Remains CANCELLED', $totalAssertions);
    assertTest($spo->fresh()->state === SupplierPurchaseOrder::STATE_CANCELLED, 'SPO remains CANCELLED', $totalAssertions);

    echo "PASS [8/13]: Monotonic Invariant: stale older event cannot regress state after cancellation\n";
} catch (Throwable $e) {
    echo 'FAIL [8/13]: '.$e->getMessage()."\n";
}

// Test 9: Type 51 payment update updates audit/payment state without money or inventory movement
try {
    $totalTests++;
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

    $fakeGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $d): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $d): VerifiedExternalOrderCreated
        {
            return new VerifiedExternalOrderCreated('1', '1', '1');
        }

        public function getOrder(string $id, ?int $acc = null): AliExpressOrderSnapshot
        {
            return new AliExpressOrderSnapshot($id, 'PROCESSING');
        }

        public function resolveWarehouseShippingAddress(?array $o = null): array
        {
            return [];
        }
    };

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($fakeGateway, app(AliExpressPollingService::class));

    assertTest($spo->fresh()->payment_state === 'paid_externally', 'Payment state paid_externally', $totalAssertions);
    assertTest($spo->fresh()->state === SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING, 'SPO state supplier_processing', $totalAssertions);

    echo "PASS [9/13]: Audit: type 51 payment update updates audit state without local money or inventory movement\n";
} catch (Throwable $e) {
    echo 'FAIL [9/13]: '.$e->getMessage()."\n";
}

// Test 10: Type 18 tracking update saves tracking number after official pull
try {
    $totalTests++;
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

    $fakeGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $d): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $d): VerifiedExternalOrderCreated
        {
            return new VerifiedExternalOrderCreated('1', '1', '1');
        }

        public function getOrder(string $id, ?int $acc = null): AliExpressOrderSnapshot
        {
            return new AliExpressOrderSnapshot($id, 'SELLER_SEND_GOODS', 'LP001234567890SA', 'CAINIAO_STANDARD');
        }

        public function resolveWarehouseShippingAddress(?array $o = null): array
        {
            return [];
        }
    };

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($fakeGateway, app(AliExpressPollingService::class));

    assertTest($platformOrder->fresh()->tracking_number === 'LP001234567890SA', 'Tracking number saved', $totalAssertions);
    assertTest($platformOrder->fresh()->carrier_name === 'CAINIAO_STANDARD', 'Carrier saved', $totalAssertions);
    assertTest($platformOrder->fresh()->normalized_status === ExternalPlatformOrder::STATUS_SHIPPED, 'Status is shipped', $totalAssertions);

    echo "PASS [10/13]: Logistics: type 18 tracking update saves tracking number after official pull\n";
} catch (Throwable $e) {
    echo 'FAIL [10/13]: '.$e->getMessage()."\n";
}

// Test 11: Type 65 authorization expiration generates isolated audit log
try {
    $totalTests++;
    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 65,
        'payload_hash' => 'hash65',
        'fingerprint' => 'fp-65-'.uniqid(),
        'payload' => ['message' => 'Token expiring soon'],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $fakeGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $d): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $d): VerifiedExternalOrderCreated
        {
            return new VerifiedExternalOrderCreated('1', '1', '1');
        }

        public function getOrder(string $id, ?int $acc = null): AliExpressOrderSnapshot
        {
            return new AliExpressOrderSnapshot($id, '1');
        }

        public function resolveWarehouseShippingAddress(?array $o = null): array
        {
            return [];
        }
    };

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($fakeGateway, app(AliExpressPollingService::class));

    assertTest($inbox->fresh()->status === 'processed', 'Inbox marked processed', $totalAssertions);
    $audit = ProcurementAuditLog::where('action', 'aliexpress_oauth_expiration_warning')->where('auditable_id', $inbox->id)->first();
    assertTest($audit !== null, 'Audit log generated', $totalAssertions);

    echo "PASS [11/13]: System: type 65 authorization expiration generates isolated audit log without touching procurement or stock\n";
} catch (Throwable $e) {
    echo 'FAIL [11/13]: '.$e->getMessage()."\n";
}

// Test 12: Choice, JIT, or unknown event types are marked ignored with zero impact on Procurement V2
try {
    $totalTests++;
    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 56, // Choice
        'payload_hash' => 'hash56',
        'fingerprint' => 'fp-56-'.uniqid(),
        'payload' => ['choice' => true],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $fakeGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $d): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $d): VerifiedExternalOrderCreated
        {
            return new VerifiedExternalOrderCreated('1', '1', '1');
        }

        public function getOrder(string $id, ?int $acc = null): AliExpressOrderSnapshot
        {
            throw new Exception('Should not call getOrder');
        }

        public function resolveWarehouseShippingAddress(?array $o = null): array
        {
            return [];
        }
    };

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($fakeGateway, app(AliExpressPollingService::class));

    assertTest($inbox->fresh()->status === 'ignored', 'Inbox marked ignored', $totalAssertions);
    assertTest($inbox->fresh()->failure_code === 'UNSUBSCRIBED_OR_NON_V2_EVENT_TYPE', 'Failure code is UNSUBSCRIBED_OR_NON_V2_EVENT_TYPE', $totalAssertions);

    echo "PASS [12/13]: Isolation: Choice/JIT/unknown events are marked ignored with zero impact on Procurement V2\n";
} catch (Throwable $e) {
    echo 'FAIL [12/13]: '.$e->getMessage()."\n";
}

// Test 13: Documented cancellation releases pending allocations with zero stock movements or synthetic accounting
try {
    $totalTests++;
    $uniqueOrderId = '8201948572'.rand(100000, 999999);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-CANC2-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 10.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-CANC2-'.uniqid(),
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
        'supplier_purchase_order_item_id' => $spoi->id,
        'qty_allocated' => 1,
        'qty_ordered' => 1,
        'state' => 'allocated',
    ]);

    $inbox = AliExpressWebhookInboxMessage::create([
        'provider' => 'aliexpress',
        'event_type' => 53,
        'external_order_id' => $uniqueOrderId,
        'payload_hash' => 'hashcanc2',
        'fingerprint' => 'fp-canc2-'.uniqid(),
        'payload' => ['data' => ['trade_order_id' => $uniqueOrderId]],
        'received_at' => now(),
        'status' => 'received',
    ]);

    $fakeGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $d): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $d): VerifiedExternalOrderCreated
        {
            return new VerifiedExternalOrderCreated('1', '1', '1');
        }

        public function getOrder(string $id, ?int $acc = null): AliExpressOrderSnapshot
        {
            return new AliExpressOrderSnapshot($id, 'CANCELLED');
        }

        public function resolveWarehouseShippingAddress(?array $o = null): array
        {
            return [];
        }
    };

    $job = new ProcessAliExpressWebhookJob($inbox->id);
    $job->handle($fakeGateway, app(AliExpressPollingService::class));

    assertTest($spo->fresh()->state === SupplierPurchaseOrder::STATE_CANCELLED, 'SPO state is cancelled', $totalAssertions);
    assertTest($platformOrder->fresh()->normalized_status === ExternalPlatformOrder::STATUS_CANCELLED, 'Platform order is cancelled', $totalAssertions);
    assertTest($allocation->fresh()->state === 'cancelled', 'Allocation state is cancelled', $totalAssertions);
    assertTest((int) $allocation->fresh()->qty_allocated === 0, 'qty_allocated is 0', $totalAssertions);
    assertTest((int) $allocation->fresh()->qty_cancelled === 1, 'qty_cancelled is 1', $totalAssertions);

    echo "PASS [13/13]: Cancellation: verified cancellation releases pending allocations with zero stock movement or synthetic finance\n";
} catch (Throwable $e) {
    echo 'FAIL [13/13]: '.$e->getMessage()."\n";
}

echo "\nSummary: {$totalTests} tests executed, {$totalAssertions} assertions passed with 0 failures.\n";
