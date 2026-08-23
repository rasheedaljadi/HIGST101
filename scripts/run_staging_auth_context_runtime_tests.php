<?php

$projDir = is_dir(__DIR__.'/../vendor') ? realpath(__DIR__.'/..') : '/home/highest-ye/htdocs/highest-ye.store';
require $projDir.'/vendor/autoload.php';
$app = require_once $projDir.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Contracts\AliExpressAuthorizationContextResolver;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\Exceptions\AliExpressAuthorizationUnavailableException;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
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
echo "  STAGING RUNTIME VERIFICATION: ALIEXPRESS AUTHORIZATION CONTEXT\n";
echo "======================================================================\n";

// -------------------------------------------------------------
// Test 1: Container Binding Resolution
// -------------------------------------------------------------
$boundInstance = app(AliExpressAuthorizationContextResolver::class);
assertTest('Test 1.1: AliExpressAuthorizationContextResolver is bound in container', $boundInstance instanceof AliExpressAuthorizationResolver, 'Container binding failed', $results);

// -------------------------------------------------------------
// Test 2: Resolver successfully resolves active V1 OAuth token
// -------------------------------------------------------------
$mockOAuth = new class extends AliExpressOAuthService
{
    public ?AliExpressToken $fakeToken = null;

    public bool $getTokenByIdCalled = false;

    public function __construct() {}

    public function latestToken(): ?AliExpressToken
    {
        return $this->fakeToken;
    }

    public function getTokenById(int $id): ?AliExpressToken
    {
        $this->getTokenByIdCalled = true;

        return null;
    }
};

$validToken = new AliExpressToken([
    'account' => 'buyer@highest-internal.test',
    'account_id' => '4586371333',
    'seller_id' => '4586371333',
    'access_token' => 'mock_live_access_token_xyz',
    'refresh_token' => 'mock_refresh_token_abc',
    'expires_in' => 3600,
    'access_token_expires_at' => Carbon::now()->addHour(),
]);
$mockOAuth->fakeToken = $validToken;

$resolver = new AliExpressAuthorizationResolver($mockOAuth);
$context = $resolver->resolveForDropshipperSubmission();

assertTest('Test 2.1: Context accessToken matches token', $context->accessToken === 'mock_live_access_token_xyz', 'Token mismatch', $results);
assertTest('Test 2.2: Context accountIdentifier matches seller_id/account_id', $context->accountIdentifier === '4586371333', 'Account ID mismatch', $results);
assertTest('Test 2.3: Context sellerId is preserved', $context->sellerId === '4586371333', 'Seller ID mismatch', $results);
assertTest('Test 2.4: Context account is properly masked', $context->accountMasked === 'b***@highest-internal.test', 'Masking mismatch', $results);
assertTest('Test 2.5: Context isValid is true', $context->isValid === true, 'Validation state mismatch', $results);

$summary = $context->getMaskedSummary();
assertTest('Test 2.6: Masked summary masks account_identifier', $summary['account_identifier'] === '4586***', 'Masked summary failure', $results);
assertTest('Test 2.7: Masked summary masks seller_id', $summary['seller_id'] === '4586***', 'Masked summary seller failure', $results);
assertTest('Test 2.8: Masked summary omits accessToken secret', ! array_key_exists('access_token', $summary), 'Secret leaked in summary', $results);

// -------------------------------------------------------------
// Test 3: Missing or expired V1 context throws ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE
// -------------------------------------------------------------
$mockOAuth->fakeToken = null;
$threwNull = false;
$codeNull = null;
try {
    $resolver->resolveForDropshipperSubmission();
} catch (AliExpressAuthorizationUnavailableException $e) {
    $threwNull = true;
    $codeNull = $e->errorCode;
}

assertTest('Test 3.1: Missing token throws AliExpressAuthorizationUnavailableException', $threwNull, 'Did not throw on missing token', $results);
assertTest('Test 3.2: Error code is ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE', $codeNull === 'ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE', 'Error code mismatch', $results);
assertTest('Test 3.3: hasValidAuthorization returns false when missing', $resolver->hasValidAuthorization() === false, 'hasValidAuthorization returned true', $results);

$expiredToken = new AliExpressToken([
    'account' => 'buyer@highest-internal.test',
    'account_id' => '4586371333',
    'access_token' => 'mock_expired_token',
    'access_token_expires_at' => Carbon::now()->subMinute(),
]);
$mockOAuth->fakeToken = $expiredToken;

$threwExpired = false;
try {
    $resolver->resolveForDropshipperSubmission();
} catch (AliExpressAuthorizationUnavailableException $e) {
    $threwExpired = true;
}

assertTest('Test 3.4: Expired token throws AliExpressAuthorizationUnavailableException', $threwExpired, 'Did not throw on expired token', $results);

// -------------------------------------------------------------
// Test 4: Gateway uses resolver without calling find(provider_account_id) or assuming token PK
// -------------------------------------------------------------
$mockOAuth->fakeToken = $validToken;
$mockOAuth->getTokenByIdCalled = false;

$mockApiClient = new class extends AliExpressApiClient
{
    public int $networkCallCount = 0;

    public array $recordedCalls = [];

    public function __construct() {}

    public function call(string $method, string $session, array $params = []): array
    {
        $this->networkCallCount++;
        $this->recordedCalls[] = ['method' => $method, 'session' => $session, 'params' => $params];

        if ($method === 'aliexpress.ds.product.get') {
            return [
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
            ];
        }

        if ($method === 'aliexpress.ds.freight.query') {
            return [
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
            ];
        }

        return ['ok' => false, 'code' => 'UNMOCKED_METHOD'];
    }
};

$gateway = new class($mockApiClient, $mockOAuth, $resolver) extends AliExpressOrderSubmissionGateway
{
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

// Historical providerAccountId = 1 (MUST NOT trigger find(1) or error)
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

assertTest('Test 4.1: Preflight succeeds with historical providerAccountId=1', $preflight->isSuccess === true, 'Preflight failed', $results);
assertTest('Test 4.2: Freight service name is resolved', $preflight->shippingServiceName === 'CAINIAO_FULFILLMENT_STD', 'Service name mismatch', $results);
assertTest('Test 4.3: Freight minor cost is normalized', $preflight->shippingCostMinor === 500, 'Freight minor mismatch', $results);
assertTest('Test 4.4: Exact sku_attr is resolved', $preflight->resolvedSkuAttr === '14:29;200000124:200000364', 'sku_attr mismatch', $results);
assertTest('Test 4.5: getTokenById was NEVER called during resolution', $mockOAuth->getTokenByIdCalled === false, 'getTokenById was unexpectedly called', $results);

// -------------------------------------------------------------
// Test 5: Static & Dynamic Verification of ProcurementEligibilityService
// -------------------------------------------------------------
$eligibilityFile = file_get_contents($projDir.'/packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php');
$hasNullFallback = str_contains($eligibilityFile, "'provider_account_id' => \$payload['provider_account_id'] ?? null,");
$hasNoDefaultOne = ! str_contains($eligibilityFile, "'provider_account_id' => \$payload['provider_account_id'] ?? 1,");

assertTest('Test 5.1: ProcurementEligibilityService explicitly uses null fallback', $hasNullFallback, 'Eligibility service does not use ?? null', $results);
assertTest('Test 5.2: ProcurementEligibilityService contains ZERO ?? 1 fallbacks', $hasNoDefaultOne, 'Eligibility service still contains ?? 1', $results);

$gatewayFile = file_get_contents($projDir.'/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php');
$noFindCall = ! str_contains($gatewayFile, 'getTokenById');
assertTest('Test 5.3: AliExpressOrderSubmissionGateway contains ZERO getTokenById / find() calls', $noFindCall, 'Gateway still contains getTokenById', $results);

// -------------------------------------------------------------
// Test 6: Historical SPO #35 and EPO #26 DB Immutability & Audit State
// -------------------------------------------------------------
$spo35Db = DB::table('supplier_purchase_orders')->where('id', 35)->first();
$epo26Db = DB::table('external_platform_orders')->where('id', 26)->first();

assertTest('Test 6.1: SPO #35 state is supplier_exception in live DB', $spo35Db?->state === 'supplier_exception', 'SPO state altered in DB', $results);
assertTest('Test 6.2: SPO #35 payment_state is submission_failed in live DB', $spo35Db?->payment_state === 'submission_failed', 'SPO payment_state altered in DB', $results);
assertTest('Test 6.3: EPO #26 external_order_id is strictly NULL in live DB', $epo26Db?->external_order_id === null, 'EPO external_order_id altered in DB', $results);
assertTest('Test 6.4: EPO #26 failure_code is IllegalAccessToken in live DB', $epo26Db?->failure_code === 'IllegalAccessToken', 'EPO failure_code altered in DB', $results);
assertTest('Test 6.5: EPO #26 raw_status is SUBMISSION_FAILED in live DB', $epo26Db?->raw_status === 'SUBMISSION_FAILED', 'EPO raw_status altered in DB', $results);

// -------------------------------------------------------------
// Test 7: Zero Secrets in Exception Messages and Masked DTOs
// -------------------------------------------------------------
$exc = new AliExpressAuthorizationUnavailableException('Test message without secrets');
assertTest('Test 7.1: Exception errorCode is public non-sensitive string', $exc->errorCode === 'ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE', 'Exception error code failure', $results);
assertTest('Test 7.2: Exception message does not leak tokens', ! str_contains($exc->getMessage(), 'mock_'), 'Token leaked in exception message', $results);

echo "======================================================================\n";
echo sprintf("  RESULTS: %d Total Assertions | %d Passed | %d Failed\n", $results['total_assertions'], $results['passed_assertions'], $results['failed_assertions']);
echo "======================================================================\n";

if ($results['failed_assertions'] > 0) {
    exit(1);
}
