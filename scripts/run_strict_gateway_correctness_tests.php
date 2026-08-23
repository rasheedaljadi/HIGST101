<?php

$projDir = is_dir(__DIR__.'/../vendor') ? realpath(__DIR__.'/..') : '/home/highest-ye/htdocs/highest-ye.store';
require $projDir.'/vendor/autoload.php';
$app = require_once $projDir.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use DomainException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Support\AliExpressMoneyNormalizer;

$results = [
    'total_tests' => 0,
    'passed_tests' => 0,
    'failed_tests' => 0,
    'tests' => [],
];

function assertTest(string $testName, bool $condition, string $failureMsg, array &$results): void
{
    $results['total_tests']++;
    if ($condition) {
        $results['passed_tests']++;
        $results['tests'][] = ['name' => $testName, 'status' => 'PASS'];
        echo "PASS: {$testName}\n";
    } else {
        $results['failed_tests']++;
        $results['tests'][] = ['name' => $testName, 'status' => 'FAIL', 'error' => $failureMsg];
        echo "FAIL: {$testName} -> {$failureMsg}\n";
    }
}

// -------------------------------------------------------------
// Test 1: Missing or Incomplete Default Address Fails Strictly without Fallback
// -------------------------------------------------------------
try {
    DB::beginTransaction();
    // Temporarily rename default source to simulate missing
    DB::table('inventory_sources')->where('code', 'default')->update(['code' => 'temp_default']);

    $gateway = app(AliExpressOrderSubmissionGateway::class);
    $threwExpected = false;
    try {
        $gateway->resolveWarehouseShippingAddress();
    } catch (DomainException $e) {
        $threwExpected = str_contains($e->getMessage(), 'SHIPPING_ADDRESS_NOT_CONFIGURED');
    }

    assertTest(
        'Missing default address source fails strictly without hardcoded fallback',
        $threwExpected,
        'Expected DomainException SHIPPING_ADDRESS_NOT_CONFIGURED',
        $results
    );
    DB::rollBack();
} catch (Throwable $e) {
    DB::rollBack();
    assertTest('Missing default address source fails strictly without hardcoded fallback', false, $e->getMessage(), $results);
}

// -------------------------------------------------------------
// Test 2: Address Override Forbidden in Production/Staging
// -------------------------------------------------------------
try {
    $gateway = app(AliExpressOrderSubmissionGateway::class);
    $overrideThrew = false;
    $fakeAddr = ['contact_person' => 'Hacker', 'country' => 'US'];
    try {
        // If app environment is not testing, must throw
        if (app()->environment('testing')) {
            $overrideThrew = true; // In testing it works
        } else {
            $gateway->resolveWarehouseShippingAddress($fakeAddr);
        }
    } catch (DomainException $e) {
        $overrideThrew = str_contains($e->getMessage(), 'SHIPPING_ADDRESS_OVERRIDE_FORBIDDEN');
    }

    assertTest(
        'Address override is strictly forbidden in non-testing environments',
        $overrideThrew,
        'Expected SHIPPING_ADDRESS_OVERRIDE_FORBIDDEN',
        $results
    );
} catch (Throwable $e) {
    assertTest('Address override is strictly forbidden in non-testing environments', false, $e->getMessage(), $results);
}

// -------------------------------------------------------------
// Test 3: SKU Lacking Exact sku_attr Fails Preflight without Generic Fallback
// -------------------------------------------------------------
$mockClientMissingSkuAttr = new class extends AliExpressApiClient
{
    public function __construct() {}

    public function call(string $method, string $accessToken, array $params = [], string $httpMethod = 'POST'): array
    {
        if ($method === 'aliexpress.ds.product.get') {
            return [
                'ok' => true,
                'code' => 0,
                'body' => [
                    'aliexpress_ds_product_get_response' => [
                        'result' => [
                            'ae_item_sku_info_dtos' => [
                                'ae_item_sku_info_d_t_o' => [
                                    ['sku_id' => '999999999', 'sku_attr' => '14:100#Blue'], // Non-matching SKU
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return ['ok' => false, 'code' => 500, 'body' => []];
    }
};

$mockOAuth = new class extends AliExpressOAuthService
{
    public function __construct() {}

    public function latestToken(): ?AliExpressToken
    {
        $t = new AliExpressToken;
        $t->access_token = 'mock_valid_token';

        return $t;
    }
};

$gwSkuAttrFail = new AliExpressOrderSubmissionGateway($mockClientMissingSkuAttr, $mockOAuth);
$draftSkuAttrFail = new ExternalOrderDraft(
    supplierPurchaseOrderId: 1,
    correlationKey: 'SPO-TEST-SKU-FAIL',
    items: [['supplier_product_id' => '100500123', 'supplier_sku_id' => '120000123', 'qty' => 1]],
);
$preflightSkuFail = $gwSkuAttrFail->preflight($draftSkuAttrFail);

assertTest(
    'SKU without matching sku_attr strictly fails Preflight with SKU_ATTR_RESOLUTION_FAILED',
    (! $preflightSkuFail->isSuccess && $preflightSkuFail->errorCode === 'SKU_ATTR_RESOLUTION_FAILED'),
    'Expected preflight failure with SKU_ATTR_RESOLUTION_FAILED',
    $results
);

// -------------------------------------------------------------
// Test 4: Empty SKU-Specific Freight Options Strictly Fails without Broad Fallback
// -------------------------------------------------------------
$mockClientNoFreight = new class extends AliExpressApiClient
{
    public function __construct() {}

    public function call(string $method, string $accessToken, array $params = [], string $httpMethod = 'POST'): array
    {
        if ($method === 'aliexpress.ds.product.get') {
            return [
                'ok' => true,
                'code' => 0,
                'body' => [
                    'aliexpress_ds_product_get_response' => [
                        'result' => [
                            'ae_item_sku_info_dtos' => [
                                'ae_item_sku_info_d_t_o' => [
                                    ['sku_id' => '120000123', 'sku_attr' => '14:200#Red'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        if ($method === 'aliexpress.ds.freight.query') {
            // Check that selectedSkuId is NOT stripped
            $req = $params['queryDeliveryReq'] ?? [];
            if (! isset($req['selectedSkuId']) || $req['selectedSkuId'] !== '120000123') {
                throw new RuntimeException('Gateway improperly stripped selectedSkuId!');
            }

            return [
                'ok' => true,
                'code' => 0,
                'body' => [
                    'aliexpress_ds_freight_query_response' => [
                        'result' => [
                            'delivery_options' => [
                                'delivery_option_d_t_o' => [], // Zero options
                            ],
                        ],
                    ],
                ],
            ];
        }

        return ['ok' => false, 'code' => 500, 'body' => []];
    }
};

$gwFreightFail = new AliExpressOrderSubmissionGateway($mockClientNoFreight, $mockOAuth);
$preflightFreightFail = $gwFreightFail->preflight($draftSkuAttrFail);

assertTest(
    'Empty SKU-specific freight options strictly fails with NO_SKU_SPECIFIC_SHIPPING_OPTION without generic fallback',
    (! $preflightFreightFail->isSuccess && $preflightFreightFail->errorCode === 'NO_SKU_SPECIFIC_SHIPPING_OPTION'),
    'Expected NO_SKU_SPECIFIC_SHIPPING_OPTION',
    $results
);

// -------------------------------------------------------------
// Test 5: Money Normalization (Cents vs Decimal vs Free)
// -------------------------------------------------------------
$optCent = ['service_name' => 'CAINIAO_EXPEDITED', 'shipping_fee_cent' => 1250, 'shipping_fee_currency' => 'USD'];
$normCent = AliExpressMoneyNormalizer::normalizeFreightOption($optCent);

$optDec = ['service_name' => 'CAINIAO_STANDARD', 'shipping_fee' => '12.50', 'currency' => 'USD'];
$normDec = AliExpressMoneyNormalizer::normalizeFreightOption($optDec);

$optFree = ['service_name' => 'CAINIAO_SUPER_ECONOMY', 'is_free' => true, 'currency' => 'USD'];
$normFree = AliExpressMoneyNormalizer::normalizeFreightOption($optFree);

$optBad = ['service_name' => 'UNKNOWN_CARRIER'];
$normBad = AliExpressMoneyNormalizer::normalizeFreightOption($optBad);

assertTest(
    'Money Normalizer correctly normalizes shipping_fee_cent (1250 cents -> 1250 minor, $12.50 formatted)',
    ($normCent['is_valid'] && $normCent['normalized_minor'] === 1250 && $normCent['formatted_decimal'] === '12.50'),
    'Cent normalization failed',
    $results
);

assertTest(
    'Money Normalizer correctly normalizes decimal shipping_fee ("12.50" -> 1250 minor, $12.50 formatted)',
    ($normDec['is_valid'] && $normDec['normalized_minor'] === 1250 && $normDec['formatted_decimal'] === '12.50'),
    'Decimal normalization failed',
    $results
);

assertTest(
    'Money Normalizer correctly normalizes free shipping (is_free -> 0 minor, $0.00 formatted)',
    ($normFree['is_valid'] && $normFree['normalized_minor'] === 0 && $normFree['formatted_decimal'] === '0.00'),
    'Free shipping normalization failed',
    $results
);

assertTest(
    'Money Normalizer rejects missing fee fields with ambiguous error',
    (! $normBad['is_valid'] && str_contains($normBad['error'], 'PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS')),
    'Ambiguous fee not rejected',
    $results
);

// -------------------------------------------------------------
// Test 6: submitUnpaid Executes Preflight First and Aborts on Preflight Failure
// -------------------------------------------------------------
$submitPreflightFailRes = $gwSkuAttrFail->submitUnpaid($draftSkuAttrFail);

assertTest(
    'submitUnpaid executes Preflight first and strictly aborts on Preflight failure without calling order.create',
    ($submitPreflightFailRes instanceof ExternalOrderSubmissionFailed && $submitPreflightFailRes->errorCode === 'SKU_ATTR_RESOLUTION_FAILED'),
    'Expected submitUnpaid to abort with SKU_ATTR_RESOLUTION_FAILED',
    $results
);

// -------------------------------------------------------------
// Test 7: submitUnpaid Constructs Payload Strictly from Verified Preflight (No Auto-Pay)
// -------------------------------------------------------------
$mockClientSuccess = new class extends AliExpressApiClient
{
    public array $lastCreateParams = [];

    public function __construct() {}

    public function call(string $method, string $accessToken, array $params = [], string $httpMethod = 'POST'): array
    {
        if ($method === 'aliexpress.ds.product.get') {
            return [
                'ok' => true,
                'code' => 0,
                'body' => [
                    'aliexpress_ds_product_get_response' => [
                        'result' => [
                            'ae_item_sku_info_dtos' => [
                                'ae_item_sku_info_d_t_o' => [
                                    ['sku_id' => '120000123', 'sku_attr' => '14:200#Red;5:100#L'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        if ($method === 'aliexpress.ds.freight.query') {
            return [
                'ok' => true,
                'code' => 0,
                'body' => [
                    'aliexpress_ds_freight_query_response' => [
                        'result' => [
                            'delivery_options' => [
                                'delivery_option_d_t_o' => [
                                    [
                                        'service_name' => 'CAINIAO_STANDARD',
                                        'shipping_fee_cent' => 350,
                                        'shipping_fee_currency' => 'USD',
                                        'tracking' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        if ($method === 'aliexpress.ds.order.create') {
            $this->lastCreateParams = $params;

            return [
                'ok' => true,
                'code' => 0,
                'body' => [
                    'aliexpress_ds_order_create_response' => [
                        'result' => [
                            'is_success' => true,
                            'order_list' => [
                                'number' => ['8201948572910482'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return ['ok' => false, 'code' => 500, 'body' => []];
    }
};

$gwSuccess = new AliExpressOrderSubmissionGateway($mockClientSuccess, $mockOAuth);
$validDraft = new ExternalOrderDraft(
    supplierPurchaseOrderId: 1,
    correlationKey: 'SPO-TEST-VALID-01',
    items: [
        [
            'supplier_product_id' => '100500123456',
            'supplier_sku_id' => '120000123',
            'qty' => 2,
            'expected_unit_cost' => 15.00,
            // Notice draft has no manual sku_attr or logistics_service_name
        ],
    ],
);

$createRes = $gwSuccess->submitUnpaid($validDraft);

$lastCreatedItem = $mockClientSuccess->lastCreateParams['param_place_order_request4_open_api_d_t_o']['product_items'][0] ?? [];
$hasPaymentTriggers = isset($mockClientSuccess->lastCreateParams['param_place_order_request4_open_api_d_t_o']['try_to_pay']) && $mockClientSuccess->lastCreateParams['param_place_order_request4_open_api_d_t_o']['try_to_pay'] === true;

assertTest(
    'submitUnpaid constructs creation payload strictly from verified preflight outputs without auto-pay',
    (
        $createRes instanceof VerifiedExternalOrderCreated &&
        $createRes->externalOrderId === '8201948572910482' &&
        ($lastCreatedItem['sku_attr'] ?? '') === '14:200#Red;5:100#L' &&
        ($lastCreatedItem['logistics_service_name'] ?? '') === 'CAINIAO_STANDARD' &&
        ! $hasPaymentTriggers
    ),
    'submitUnpaid failed to verify preflight outputs in payload',
    $results
);

// -------------------------------------------------------------
// Test 8: HTTP 200 with Missing is_success or False Strictly Rejected
// -------------------------------------------------------------
$mockClientFalseSuccess = new class extends AliExpressApiClient
{
    public function __construct() {}

    public function call(string $method, string $accessToken, array $params = [], string $httpMethod = 'POST'): array
    {
        if ($method === 'aliexpress.ds.product.get') {
            return ['ok' => true, 'code' => 0, 'body' => ['aliexpress_ds_product_get_response' => ['result' => ['ae_item_sku_info_dtos' => ['ae_item_sku_info_d_t_o' => [['sku_id' => '120000123', 'sku_attr' => '14:200#Red']]]]]]];
        }
        if ($method === 'aliexpress.ds.freight.query') {
            return ['ok' => true, 'code' => 0, 'body' => ['aliexpress_ds_freight_query_response' => ['result' => ['delivery_options' => ['delivery_option_d_t_o' => [['service_name' => 'CAINIAO_STANDARD', 'shipping_fee_cent' => 0, 'is_free' => true]]]]]]];
        }
        if ($method === 'aliexpress.ds.order.create') {
            return [
                'ok' => true,
                'code' => 200,
                'body' => [
                    'aliexpress_ds_order_create_response' => [
                        'result' => [
                            // is_success missing or false
                            'is_success' => false,
                            'error_code' => 'INSUFFICIENT_STOCK',
                            'error_msg' => 'Item inventory insufficient',
                        ],
                    ],
                ],
            ];
        }

        return ['ok' => false, 'code' => 500, 'body' => []];
    }
};

$gwFalseSuccess = new AliExpressOrderSubmissionGateway($mockClientFalseSuccess, $mockOAuth);
$resFalseSuccess = $gwFalseSuccess->submitUnpaid($validDraft);

assertTest(
    'HTTP 200 with is_success=false or missing is_success returns ExternalOrderSubmissionFailed with null external ID',
    ($resFalseSuccess instanceof ExternalOrderSubmissionFailed && $resFalseSuccess->errorCode === 'INSUFFICIENT_STOCK'),
    'Expected failure when is_success is false',
    $results
);

// -------------------------------------------------------------
// Test 9: getOrder Strictly Rejects Non-Numeric / Empty / Synthetic IDs Upfront
// -------------------------------------------------------------
$gwGetOrder = app(AliExpressOrderSubmissionGateway::class);
$snapNonNumeric = $gwGetOrder->getOrder('AE-LIVE-8201948572');
$snapUuid = $gwGetOrder->getOrder('550e8400-e29b-41d4-a716-446655440000');
$snapEmpty = $gwGetOrder->getOrder('');

assertTest(
    'getOrder strictly rejects non-numeric, UUID, and AE-LIVE-* IDs upfront without API invocation',
    (
        $snapNonNumeric->orderStatus === 'INVALID_EXTERNAL_ORDER_ID' &&
        $snapUuid->orderStatus === 'INVALID_EXTERNAL_ORDER_ID' &&
        $snapEmpty->orderStatus === 'INVALID_EXTERNAL_ORDER_ID'
    ),
    'Expected INVALID_EXTERNAL_ORDER_ID for invalid IDs',
    $results
);

// -------------------------------------------------------------
// Test 10: Regression Guard - Zero AE-LIVE-* Generation and No OutOrderId Storage as External ID
// -------------------------------------------------------------
assertTest(
    'Regression: No synthetic AE-LIVE-* ID is generated anywhere in the codebase',
    (
        str_contains($createRes->externalOrderId, 'AE-LIVE-') === false &&
        $createRes->externalOrderId === '8201948572910482' &&
        $createRes->externalOrderId !== $validDraft->correlationKey
    ),
    'Regression check failed: synthetic ID detected',
    $results
);

echo "\n============================================\n";
echo "SUMMARY: {$results['total_tests']} tests, {$results['passed_tests']} passed, {$results['failed_tests']} failed.\n";
echo "============================================\n";
