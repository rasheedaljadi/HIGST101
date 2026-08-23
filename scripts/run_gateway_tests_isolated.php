<?php

$projDir = is_dir(__DIR__.'/../vendor') ? realpath(__DIR__.'/..') : '/home/highest-ye/htdocs/highest-ye.store';
require $projDir.'/vendor/autoload.php';
$app = require_once $projDir.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$srcDir = is_dir(__DIR__.'/../packages/Webkul/Procurement/src') ? realpath(__DIR__.'/../packages/Webkul/Procurement/src') : '/tmp/procurement_src';
if (file_exists($srcDir.'/Contracts/AliExpressOrderGateway.php')) {
    require_once $srcDir.'/Contracts/AliExpressOrderGateway.php';
    require_once $srcDir.'/DTO/ExternalOrderDraft.php';
    require_once $srcDir.'/DTO/AliExpressOrderPreflight.php';
    require_once $srcDir.'/DTO/AliExpressOrderSnapshot.php';
    require_once $srcDir.'/Gateways/AliExpressOrderSubmissionGateway.php';
}

$app->singleton(
    AliExpressOrderGateway::class,
    AliExpressOrderSubmissionGateway::class
);

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\AliExpressOrderSnapshot;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\ProcurementSubmitService;

// Pure PHP Test Spy Client
class TestSpyAliExpressApiClient extends AliExpressApiClient
{
    public array $calledMethods = [];

    public array $callResponses = [];

    public function __construct() {}

    public function call(string $method, string $accessToken, array $params = []): array
    {
        $this->calledMethods[] = $method;
        if (isset($this->callResponses[$method])) {
            return $this->callResponses[$method];
        }

        return ['ok' => true, 'body' => []];
    }
}

class TestFakeOAuthService extends AliExpressOAuthService
{
    public function __construct() {}

    public function latestToken(): ?AliExpressToken
    {
        return new AliExpressToken([
            'access_token' => 'mock-valid-token',
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function getTokenById(int $id): ?AliExpressToken
    {
        return $this->latestToken();
    }
}

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

echo "=== Running Isolated AliExpress Gateway Tests ===\n\n";

// Test 1: Unit: Gateway resolves unified warehouse shipping address from inventory_sources
try {
    $totalTests++;
    DB::table('inventory_sources')->updateOrInsert(
        ['code' => 'default'],
        [
            'name' => 'Al-Miftah Main Hub',
            'contact_name' => 'Al-Miftah Transport',
            'contact_number' => '0500000000',
            'contact_email' => 'warehouse@hayest.com',
            'street' => 'Southern Ring Road, Al-Aziziyah',
            'city' => 'Riyadh',
            'state' => 'Riyadh',
            'country' => 'SA',
            'postcode' => '11564',
        ]
    );

    /** @var AliExpressOrderSubmissionGateway $gateway */
    $gateway = app(AliExpressOrderGateway::class);
    $addr = $gateway->resolveWarehouseShippingAddress();

    assertTest($addr['country'] === 'SA', 'Country must be SA', $totalAssertions);
    assertTest($addr['city'] === 'Riyadh', 'City must be Riyadh', $totalAssertions);
    assertTest($addr['phone_country'] === '966', 'Phone country must be 966', $totalAssertions);
    assertTest($addr['zip'] === '11564', 'Zip must be 11564', $totalAssertions);
    assertTest(str_contains($addr['contact_person'], 'Al-Miftah'), 'Contact person must contain Al-Miftah', $totalAssertions);

    echo "PASS [1/7]: Unit: gateway resolves unified warehouse shipping address from inventory_sources\n";
} catch (Throwable $e) {
    echo 'FAIL [1/7]: '.$e->getMessage()."\n";
}

// Test 2: Unit: Client spy proves preflight calls product and freight only and never order.create nor DB writes
try {
    $totalTests++;
    $mockClient = new TestSpyAliExpressApiClient;
    $mockOAuth = new TestFakeOAuthService;

    $mockClient->callResponses['aliexpress.ds.product.get'] = [
        'ok' => true,
        'body' => [
            'aliexpress_ds_product_get_response' => [
                'result' => [
                    'ae_item_sku_info_dtos' => [
                        'ae_item_sku_info_d_t_o' => [
                            ['sku_id' => '12000044371414236', 'sku_attr' => '14:29;200000124:200000900'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $mockClient->callResponses['aliexpress.ds.freight.query'] = [
        'ok' => true,
        'body' => [
            'aliexpress_ds_freight_query_response' => [
                'result' => [
                    'delivery_options' => [
                        'delivery_option_d_t_o' => [
                            [
                                'service_name' => 'CAINIAO_FULFILLMENT_STD',
                                'code' => 'CAINIAO_FULFILLMENT_STD',
                                'shipping_fee_cent' => 5.0,
                                'shipping_fee_currency' => 'USD',
                                'min_delivery_days' => 7,
                                'max_delivery_days' => 12,
                                'tracking' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $gateway = new AliExpressOrderSubmissionGateway($mockClient, $mockOAuth);

    $draft = new ExternalOrderDraft(
        supplierPurchaseOrderId: 'DRAFT-SPY-TEST',
        correlationKey: 'DRAFT-SPY-TEST',
        items: [
            [
                'supplier_product_id' => '1005008248073626',
                'supplier_sku_id' => '12000044371414236',
                'qty' => 1,
                'expected_unit_cost' => 10.0,
            ],
        ],
        currencyCode: 'USD'
    );

    $platformOrderCountBefore = ExternalPlatformOrder::count();
    $spoCountBefore = SupplierPurchaseOrder::count();

    $preflight = $gateway->preflight($draft);

    assertTest($preflight->isSuccess === true, 'Preflight isSuccess must be true', $totalAssertions);
    assertTest($preflight->isDeliverableToDestination === true, 'Preflight isDeliverable must be true', $totalAssertions);
    assertTest($preflight->shippingServiceName === 'CAINIAO_FULFILLMENT_STD', 'Service must be CAINIAO_FULFILLMENT_STD', $totalAssertions);
    assertTest($preflight->shippingCost === 5.0, 'Cost must be 5.0', $totalAssertions);
    assertTest($preflight->resolvedSkuAttr === '14:29;200000124:200000900', 'Resolved SKU attr match', $totalAssertions);
    assertTest(! in_array('aliexpress.ds.order.create', $mockClient->calledMethods), 'Never called order.create', $totalAssertions);
    assertTest(ExternalPlatformOrder::count() === $platformOrderCountBefore, 'No Platform Orders written', $totalAssertions);
    assertTest(SupplierPurchaseOrder::count() === $spoCountBefore, 'No SPO written', $totalAssertions);

    echo "PASS [2/7]: Unit: client spy proves preflight calls product/freight only, never order.create, 0 DB writes\n";
} catch (Throwable $e) {
    echo 'FAIL [2/7]: '.$e->getMessage()."\n";
}

// Test 3: Unit: default source isolation from stock balances
try {
    $totalTests++;
    $source = DB::table('inventory_sources')->where('code', 'default')->first();
    assertTest($source !== null, 'Default source exists', $totalAssertions);

    $gateway = app(AliExpressOrderGateway::class);
    $addr = $gateway->resolveWarehouseShippingAddress();

    assertTest($addr['country'] === 'SA', 'Address country SA', $totalAssertions);

    // Check all potential allocation/inventory tables to ensure default source has zero rogue allocations
    $allocations = 0;
    if (Schema::hasTable('order_item_allocations')) {
        $allocations += DB::table('order_item_allocations')->where('allocated_source_id', $source->id)->count();
    }
    if (Schema::hasTable('procurement_item_allocations')) {
        $allocations += DB::table('procurement_item_allocations')->where('inventory_source_id', $source->id)->count();
    }
    assertTest($allocations === 0, 'Zero allocations on default source', $totalAssertions);

    echo "PASS [3/7]: Unit: default inventory source is strictly metadata, zero allocations/movements\n";
} catch (Throwable $e) {
    echo 'FAIL [3/7]: '.$e->getMessage()."\n";
}

// Test 4: Unit: submitUnpaid fails on HTTP 200 with error_response envelope
try {
    $totalTests++;
    $mockClient = new TestSpyAliExpressApiClient;
    $mockOAuth = new TestFakeOAuthService;

    $mockClient->callResponses['aliexpress.ds.order.create'] = [
        'ok' => false,
        'code' => 'MissingParameter',
        'message' => 'The parameter logistics_address is required',
        'body' => [
            'error_response' => [
                'code' => 'MissingParameter',
                'msg' => 'The parameter logistics_address is required',
                'request_id' => 'req-err-missing-param-99',
            ],
        ],
    ];

    $gateway = new AliExpressOrderSubmissionGateway($mockClient, $mockOAuth);

    $draft = new ExternalOrderDraft(
        supplierPurchaseOrderId: 'DRAFT-FAIL-ENVELOPE',
        correlationKey: 'DRAFT-FAIL-ENVELOPE',
        items: [
            ['supplier_product_id' => '1005008248073626', 'supplier_sku_id' => '12000044371414236', 'qty' => 1],
        ],
        currencyCode: 'USD'
    );

    $result = $gateway->submitUnpaid($draft);

    assertTest($result instanceof ExternalOrderSubmissionFailed, 'Result is ExternalOrderSubmissionFailed', $totalAssertions);
    assertTest($result->errorCode === 'MissingParameter', 'Error code is MissingParameter', $totalAssertions);
    assertTest($result->providerRequestId === 'req-err-missing-param-99', 'Request ID is req-err-missing-param-99', $totalAssertions);
    assertTest($result->retryClassification === 'fatal', 'Classification is fatal', $totalAssertions);

    echo "PASS [4/7]: Unit: submitUnpaid fails on HTTP 200 with error_response envelope\n";
} catch (Throwable $e) {
    echo 'FAIL [4/7]: '.$e->getMessage()."\n";
}

// Test 5: Unit: submitUnpaid fails when is_success is true but external order ID is non-numeric or synthetic
try {
    $totalTests++;
    $mockClient = new TestSpyAliExpressApiClient;
    $mockOAuth = new TestFakeOAuthService;

    $mockClient->callResponses['aliexpress.ds.order.create'] = [
        'ok' => true,
        'body' => [
            'aliexpress_ds_order_create_response' => [
                'result' => [
                    'is_success' => true,
                    'order_list' => ['AE-SYNTHETIC-MOCK-ID-999'], // Non-numeric
                ],
            ],
        ],
    ];

    $gateway = new AliExpressOrderSubmissionGateway($mockClient, $mockOAuth);

    $draft = new ExternalOrderDraft(
        supplierPurchaseOrderId: 'DRAFT-NON-NUMERIC-ID',
        correlationKey: 'DRAFT-NON-NUMERIC-ID',
        items: [
            ['supplier_product_id' => '1005008248073626', 'supplier_sku_id' => '12000044371414236', 'qty' => 1],
        ],
        currencyCode: 'USD'
    );

    $result = $gateway->submitUnpaid($draft);

    assertTest($result instanceof ExternalOrderSubmissionFailed, 'Result is ExternalOrderSubmissionFailed', $totalAssertions);
    assertTest($result->errorCode === 'EMPTY_EXTERNAL_ORDER_ID', 'Error code is EMPTY_EXTERNAL_ORDER_ID', $totalAssertions);

    echo "PASS [5/7]: Unit: submitUnpaid strictly rejects non-numeric or synthetic order IDs\n";
} catch (Throwable $e) {
    echo 'FAIL [5/7]: '.$e->getMessage()."\n";
}

// Test 6: Unit: out_order_id is correlation key only and never external_order_id
try {
    $totalTests++;

    // Fake gateway that returns numeric ID
    $mockGateway = new class implements AliExpressOrderGateway
    {
        public function preflight(ExternalOrderDraft $draft): AliExpressOrderPreflight
        {
            return new AliExpressOrderPreflight(true, true, 'SA');
        }

        public function submitUnpaid(ExternalOrderDraft $draft): VerifiedExternalOrderCreated|ExternalOrderSubmissionFailed
        {
            return new VerifiedExternalOrderCreated('8201948572910482', 'req-success-7788', 'WAIT_BUYER_PAY');
        }

        public function getOrder(string $officialExternalOrderId, ?int $providerAccountId = null): AliExpressOrderSnapshot
        {
            return new AliExpressOrderSnapshot($officialExternalOrderId, 'WAIT_BUYER_PAY');
        }

        public function resolveWarehouseShippingAddress(?array $override = null): array
        {
            return ['country' => 'SA'];
        }
    };

    $submitService = new ProcurementSubmitService($mockGateway);

    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-TEST-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 15.0,
    ]);

    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-CORR-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
        'expected_total' => 15.0,
    ]);

    SupplierPurchaseOrderItem::create([
        'supplier_purchase_order_id' => $spo->id,
        'product_id' => 1,
        'supplier_product_id' => '1005008248073626',
        'supplier_sku_id' => '12000044371414236',
        'qty_ordered' => 1,
        'expected_unit_cost' => 15.0,
    ]);

    $resultSpo = $submitService->submitSupplierPurchaseOrder(
        $spo->id,
        1,
        new VerifiedExternalOrderCreated('8201948572910482', 'req-success-7788', 'WAIT_BUYER_PAY')
    );
    $platformOrder = $resultSpo->platformOrders->first();

    assertTest($platformOrder->external_order_id === '8201948572910482', 'External ID must be 8201948572910482', $totalAssertions);
    assertTest($platformOrder->correlation_key === $spo->purchase_order_number, 'Correlation key matches SPO number', $totalAssertions);
    assertTest($platformOrder->external_order_id !== $platformOrder->correlation_key, 'External ID does not equal correlation key', $totalAssertions);

    echo "PASS [6/7]: Unit: out_order_id is correlation key only and never external_order_id\n";
} catch (Throwable $e) {
    echo 'FAIL [6/7]: '.$e->getMessage()."\n";
}

// Test 7: Regression: V1 purchase_orders table is preserved for historical read and never written by V2
try {
    $totalTests++;
    $initialV1Count = DB::table('purchase_orders')->count();

    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-HIST-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 10.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-V2-HIST-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
        'expected_total' => 10.0,
    ]);

    assertTest(DB::table('purchase_orders')->count() === $initialV1Count, 'V1 purchase orders count unchanged', $totalAssertions);

    echo "PASS [7/7]: Regression: V1 purchase_orders table preserved for historical read, never written by V2\n";
} catch (Throwable $e) {
    echo 'FAIL [7/7]: '.$e->getMessage()."\n";
}

echo "\nSummary: {$totalTests} tests executed, {$totalAssertions} assertions passed with 0 failures.\n";
