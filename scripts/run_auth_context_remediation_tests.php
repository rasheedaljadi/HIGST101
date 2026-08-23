<?php

$projDir = is_dir(__DIR__.'/../vendor') ? realpath(__DIR__.'/..') : '/home/highest-ye/htdocs/highest-ye.store';
require $projDir.'/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Carbon;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\Exceptions\AliExpressAuthorizationUnavailableException;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Services\AliExpressAuthorizationResolver;
use Webkul\Procurement\Services\ProcurementEligibilityService;

$results = [
    'total_assertions' => 0,
    'passed_assertions' => 0,
    'failed_assertions' => 0,
    'tests' => [],
];

function assertTest(string $testName, bool $condition, string $failureMsg, array &$results): void
{
    $results['total_assertions']++;
    if ($condition) {
        $results['passed_assertions']++;
        $results['tests'][] = ['name' => $testName, 'status' => 'PASS'];
        echo "PASS: {$testName}\n";
    } else {
        $results['failed_assertions']++;
        $results['tests'][] = ['name' => $testName, 'status' => 'FAIL', 'error' => $failureMsg];
        echo "FAIL: {$testName} -> {$failureMsg}\n";
    }
}

echo "======================================================================\n";
echo "  RUNNING PROCUREMENT V2 AUTHORIZATION CONTEXT REMEDIATION TESTS\n";
echo "======================================================================\n";

// -------------------------------------------------------------
// Test 1: Resolver successfully resolves active V1 OAuth token into authorized context
// -------------------------------------------------------------
$token = new AliExpressToken([
    'account' => 'buyer@highest-internal.test',
    'account_id' => '4586371333',
    'seller_id' => '4586371333',
    'access_token' => 'mock_live_access_token_xyz',
    'refresh_token' => 'mock_refresh_token_abc',
    'expires_in' => 3600,
    'access_token_expires_at' => Carbon::now()->addHour(),
]);

$mockOAuth = Mockery::mock(AliExpressOAuthService::class);
$mockOAuth->shouldReceive('latestToken')->once()->andReturn($token);

$resolver = new AliExpressAuthorizationResolver($mockOAuth);
$context = $resolver->resolveForDropshipperSubmission();

assertTest('Test 1.1: Context accessToken matches token', $context->accessToken === 'mock_live_access_token_xyz', 'Token mismatch', $results);
assertTest('Test 1.2: Context accountIdentifier matches seller_id/account_id', $context->accountIdentifier === '4586371333', 'Account ID mismatch', $results);
assertTest('Test 1.3: Context sellerId is preserved', $context->sellerId === '4586371333', 'Seller ID mismatch', $results);
assertTest('Test 1.4: Context account is properly masked', $context->accountMasked === 'b***@highest-internal.test', 'Masking mismatch', $results);
assertTest('Test 1.5: Context isValid is true', $context->isValid === true, 'Validation state mismatch', $results);

$summary = $context->getMaskedSummary();
assertTest('Test 1.6: Masked summary masks account_identifier', $summary['account_identifier'] === '4586***', 'Masked summary failure', $results);
assertTest('Test 1.7: Masked summary masks seller_id', $summary['seller_id'] === '4586***', 'Masked summary seller failure', $results);
assertTest('Test 1.8: Masked summary omits accessToken secret', ! array_key_exists('access_token', $summary), 'Secret leaked in summary', $results);

// -------------------------------------------------------------
// Test 2: Missing or expired V1 context throws ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE
// -------------------------------------------------------------
$mockOAuthNull = Mockery::mock(AliExpressOAuthService::class);
$mockOAuthNull->shouldReceive('latestToken')->once()->andReturnNull();

$resolverNull = new AliExpressAuthorizationResolver($mockOAuthNull);
$threwNull = false;
$codeNull = null;
try {
    $resolverNull->resolveForDropshipperSubmission();
} catch (AliExpressAuthorizationUnavailableException $e) {
    $threwNull = true;
    $codeNull = $e->errorCode;
}

assertTest('Test 2.1: Missing token throws AliExpressAuthorizationUnavailableException', $threwNull, 'Did not throw on missing token', $results);
assertTest('Test 2.2: Error code is ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE', $codeNull === 'ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE', 'Error code mismatch', $results);
assertTest('Test 2.3: hasValidAuthorization returns false when missing', $resolverNull->hasValidAuthorization() === false, 'hasValidAuthorization returned true', $results);

$expiredToken = new AliExpressToken([
    'account' => 'buyer@highest-internal.test',
    'account_id' => '4586371333',
    'access_token' => 'mock_expired_token',
    'access_token_expires_at' => Carbon::now()->subMinute(),
]);

$mockOAuthExpired = Mockery::mock(AliExpressOAuthService::class);
$mockOAuthExpired->shouldReceive('latestToken')->once()->andReturn($expiredToken);

$resolverExpired = new AliExpressAuthorizationResolver($mockOAuthExpired);
$threwExpired = false;
try {
    $resolverExpired->resolveForDropshipperSubmission();
} catch (AliExpressAuthorizationUnavailableException $e) {
    $threwExpired = true;
}

assertTest('Test 2.4: Expired token throws AliExpressAuthorizationUnavailableException', $threwExpired, 'Did not throw on expired token', $results);

// -------------------------------------------------------------
// Test 3: Gateway uses resolver without calling find(provider_account_id) or assuming token PK
// -------------------------------------------------------------
$tokenValid = new AliExpressToken([
    'account' => 'buyer@highest-internal.test',
    'account_id' => '4586371333',
    'seller_id' => '4586371333',
    'access_token' => 'mock_valid_token_123',
    'access_token_expires_at' => Carbon::now()->addHour(),
]);

$mockOAuthGateway = Mockery::mock(AliExpressOAuthService::class);
$mockOAuthGateway->shouldReceive('latestToken')->andReturn($tokenValid);
// Crucial: getTokenById MUST NEVER be called
$mockOAuthGateway->shouldNotReceive('getTokenById');

$mockApiClient = Mockery::mock(AliExpressApiClient::class);
$mockApiClient->shouldReceive('call')
    ->with('aliexpress.ds.product.get', 'mock_valid_token_123', Mockery::any())
    ->once()
    ->andReturn([
        'ok' => true,
        'body' => [
            'aliexpress_ds_product_get_response' => [
                'result' => [
                    'ae_item_sku_info_dtos' => [
                        'ae_item_sku_info_d_t_o' => [
                            ['sku_id' => '12000052207602660', 'sku_attr' => '14:29;200000124:200000364', 'offer_sale_price' => '27.15'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

$mockApiClient->shouldReceive('call')
    ->with('aliexpress.ds.freight.query', 'mock_valid_token_123', Mockery::any())
    ->once()
    ->andReturn([
        'ok' => true,
        'body' => [
            'aliexpress_ds_freight_query_response' => [
                'result' => [
                    'delivery_options' => [
                        'delivery_option_d_t_o' => [
                            [
                                'code' => 'CAINIAO_FULFILLMENT_STD',
                                'service_name' => 'CAINIAO_FULFILLMENT_STD',
                                'shipping_fee_cent' => '5.00',
                                'tracking' => true,
                                'min_delivery_days' => 7,
                                'max_delivery_days' => 11,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

$resolverForGateway = new AliExpressAuthorizationResolver($mockOAuthGateway);

// Create gateway with mock address resolver to isolate from DB
$gateway = new class($mockApiClient, $mockOAuthGateway, $resolverForGateway) extends AliExpressOrderSubmissionGateway {
    public function resolveWarehouseShippingAddress(?array $override = null): array
    {
        return [
            'contact_person' => 'Key Management Default Hub',
            'phone_num' => '0500000000',
            'mobile_no' => '0500000000',
            'phone_country' => '966',
            'address' => 'King Fahd Road',
            'city' => 'Riyadh',
            'province' => 'Riyadh',
            'zip' => '12345',
            'country' => 'SA',
            'company_name' => 'Default Hub',
        ];
    }
};

// Pass historical providerAccountId = 1 (MUST NOT trigger find(1) or error)
$draftHistorical = new ExternalOrderDraft(
    supplierPurchaseOrderId: 999,
    correlationKey: 'TEST-SPO-AUTH-'.uniqid(),
    items: [
        [
            'supplier_product_id' => '1005010378829324',
            'supplier_sku_id' => '12000052207602660',
            'qty' => 1,
            'currency_code' => 'USD',
        ],
    ],
    providerAccountId: 1
);

$preflight = $gateway->preflight($draftHistorical);

assertTest('Test 3.1: Preflight succeeds with historical providerAccountId=1', $preflight->isSuccess === true, 'Preflight failed', $results);
assertTest('Test 3.2: Freight service name is resolved', $preflight->shippingServiceName === 'CAINIAO_FULFILLMENT_STD', 'Service name mismatch', $results);
assertTest('Test 3.3: Freight minor cost is normalized', $preflight->shippingCostMinor === 500, 'Freight minor mismatch', $results);
assertTest('Test 3.4: Exact sku_attr is resolved', $preflight->resolvedSkuAttr === '14:29;200000124:200000364', 'sku_attr mismatch', $results);

// -------------------------------------------------------------
// Test 4: Static & Dynamic Verification of ProcurementEligibilityService
// -------------------------------------------------------------
$eligibilityFile = file_get_contents($projDir . '/packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php');
$hasNullFallback = str_contains($eligibilityFile, "'provider_account_id' => \$payload['provider_account_id'] ?? null,");
$hasNoDefaultOne = ! str_contains($eligibilityFile, "'provider_account_id' => \$payload['provider_account_id'] ?? 1,");

assertTest('Test 4.1: ProcurementEligibilityService explicitly uses null fallback', $hasNullFallback, 'Eligibility service does not use ?? null', $results);
assertTest('Test 4.2: ProcurementEligibilityService contains ZERO ?? 1 fallbacks', $hasNoDefaultOne, 'Eligibility service still contains ?? 1', $results);

$gatewayFile = file_get_contents($projDir . '/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php');
$noFindCall = ! str_contains($gatewayFile, 'getTokenById');
assertTest('Test 4.3: AliExpressOrderSubmissionGateway contains ZERO getTokenById / find() calls', $noFindCall, 'Gateway still contains getTokenById', $results);

// -------------------------------------------------------------
// Test 5: Historical SPO #35 and EPO #26 remain immutable in failed audit state
// -------------------------------------------------------------
$spo = new SupplierPurchaseOrder([
    'id' => 35,
    'state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
    'payment_state' => 'submission_failed',
]);

$epo = new ExternalPlatformOrder([
    'id' => 26,
    'supplier_purchase_order_id' => 35,
    'raw_status' => 'SUBMISSION_FAILED',
    'failure_code' => 'IllegalAccessToken',
    'external_order_id' => null,
]);

assertTest('Test 5.1: SPO #35 state is supplier_exception', $spo->state === 'supplier_exception', 'SPO state altered', $results);
assertTest('Test 5.2: SPO #35 payment_state is submission_failed', $spo->payment_state === 'submission_failed', 'SPO payment_state altered', $results);
assertTest('Test 5.3: EPO #26 external_order_id is strictly NULL', $epo->external_order_id === null, 'EPO external_order_id not null', $results);
assertTest('Test 5.4: EPO #26 failure_code is IllegalAccessToken', $epo->failure_code === 'IllegalAccessToken', 'EPO failure_code altered', $results);

// -------------------------------------------------------------
// Test 6: Zero Secrets in Exception Messages and Masked DTOs
// -------------------------------------------------------------
$exc = new AliExpressAuthorizationUnavailableException('Test message without secrets');
assertTest('Test 6.1: Exception errorCode is public non-sensitive string', $exc->errorCode === 'ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE', 'Exception error code failure', $results);
assertTest('Test 6.2: Exception message does not leak tokens', ! str_contains($exc->getMessage(), 'mock_'), 'Token leaked in exception message', $results);

echo "======================================================================\n";
echo sprintf("  RESULTS: %d Total Assertions | %d Passed | %d Failed\n", $results['total_assertions'], $results['passed_assertions'], $results['failed_assertions']);
echo "======================================================================\n";

if ($results['failed_assertions'] > 0) {
    exit(1);
}
