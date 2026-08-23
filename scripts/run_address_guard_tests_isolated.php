<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use App\Services\AliExpress\DTO\ValidatedAliExpressShippingAddress;
use App\Services\AliExpress\Exceptions\AliExpressInvalidShippingAddressException;
use App\Services\AliExpress\Shipping\AliExpressShippingAddressValidator;
use Illuminate\Contracts\Console\Kernel;
use Webkul\Fulfillment\DataObjects\ShippingAddress;
use Webkul\Fulfillment\DataObjects\SupplierOrderLine;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressFulfillmentProvider;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;

class IsolatedMockApiClient extends AliExpressApiClient
{
    public int $callCount = 0;

    public function call(string $method, string $accessToken, array $params = []): array
    {
        $this->callCount++;
        throw new RuntimeException("API Client call was unexpectedly triggered for {$method}!");
    }
}

class IsolatedMockOAuthService extends AliExpressOAuthService
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function latestToken(): ?AliExpressToken
    {
        $token = new AliExpressToken;
        $token->access_token = 'mock_token';

        return $token;
    }
}

$totalAssertions = 0;
$totalTests = 0;

function assertTest(bool $condition, string $description, int &$totalAssertions): void
{
    $totalAssertions++;
    if (! $condition) {
        echo " [FAIL] Assertion failed: {$description}\n";
        throw new RuntimeException("Assertion failed: {$description}");
    }
}

echo "\n======================================================================\n";
echo "  ALIEXPRESS SAUDI NATIONAL ADDRESS GUARD ISOLATED TEST SUITE\n";
echo "======================================================================\n\n";

// Test 1: SA valid fixture produces correct uppercase 8-char zip and matching V1 and V2 output
$totalTests++;
echo 'Test 1: SA valid fixture produces correct uppercase 8-char zip and matching V1/V2 output... ';
$rawSaAddress = [
    'contact_person' => 'Al-Miftah Transport',
    'phone_num' => '0500000000',
    'mobile_no' => '0500000000',
    'phone_country' => '+966',
    'address' => 'Southern Ring Road, Al-Aziziyah',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => 'abcd1234', // lowercase input
    'country' => 'sa',
    'company_name' => 'Al-Miftah Hub',
];

$validated = AliExpressShippingAddressValidator::normalizeAndValidate($rawSaAddress);
assertTest($validated instanceof ValidatedAliExpressShippingAddress, 'Must return ValidatedAliExpressShippingAddress DTO', $totalAssertions);
assertTest($validated->zip === 'ABCD1234', 'Zip must be normalized to uppercase ABCD1234', $totalAssertions);
assertTest($validated->country === 'SA', 'Country must be normalized to SA', $totalAssertions);
assertTest($validated->phoneCountry === '966', 'Phone country must be stripped of + to 966', $totalAssertions);
assertTest($validated->toLogisticsAddressArray()['zip'] === 'ABCD1234', 'Array payload zip must be ABCD1234', $totalAssertions);

// Test with V1 ShippingAddress object
$v1Obj = new ShippingAddress(
    firstName: 'Al-Miftah',
    lastName: 'Transport Office',
    address: 'Southern Ring Road, Al-Aziziyah',
    city: 'Riyadh',
    state: 'Riyadh',
    postcode: 'ABCD1234',
    country: 'SA',
    phone: '0500000000',
    email: 'warehouse@example.com',
    companyName: 'Al-Miftah Hub'
);

$v1Validated = AliExpressShippingAddressValidator::normalizeAndValidate($v1Obj);
assertTest($v1Validated->zip === 'ABCD1234', 'V1 object zip must be ABCD1234', $totalAssertions);
assertTest($v1Validated->toLogisticsAddressArray()['zip'] === $validated->toLogisticsAddressArray()['zip'], 'V1 and array zip must match exactly', $totalAssertions);
echo "PASSED\n";

// Test 2: SA 5-digit postal fixture throws ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING and clientCalls = 0
$totalTests++;
echo 'Test 2: SA 5-digit postal fixture throws domain error and prevents API client call... ';
$invalidPostalAddress = [
    'contact_person' => 'Al-Miftah Transport',
    'phone_num' => '0500000000',
    'phone_country' => '966',
    'address' => 'Southern Ring Road',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => '11564', // 5 digits - invalid for SA
    'country' => 'SA',
];

$threw = false;
$errorCode = null;
$msg = null;
try {
    AliExpressShippingAddressValidator::normalizeAndValidate($invalidPostalAddress);
} catch (AliExpressInvalidShippingAddressException $e) {
    $threw = true;
    $errorCode = $e->errorCode;
    $msg = $e->getMessage();
}
assertTest($threw === true, 'Must throw AliExpressInvalidShippingAddressException for 5-digit postal', $totalAssertions);
assertTest($errorCode === 'ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING', 'Error code must be ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING', $totalAssertions);
assertTest(! str_contains($msg, '11564'), 'Exception message must NEVER leak the raw postal code', $totalAssertions);

// Verify V1 provider rejects without calling API client
$mockApiClient = new IsolatedMockApiClient;
$mockOAuth = new IsolatedMockOAuthService;

$v1Provider = new AliExpressFulfillmentProvider($mockOAuth, $mockApiClient);
$v1Request = new SupplierOrderRequest(
    internalReference: 'PO-TEST-1',
    idempotencyKey: 'idemp-1',
    shippingAddress: new ShippingAddress(
        firstName: 'Test',
        lastName: 'Warehouse',
        address: '123 St',
        city: 'Riyadh',
        state: 'Riyadh',
        postcode: '11564',
        country: 'SA',
        phone: '0500000000',
        email: 'test@example.com'
    ),
    items: [new SupplierOrderLine('1005001', 'sku-1', 1)],
    currency: 'USD'
);

$result = $v1Provider->createSupplierOrder($v1Request);
assertTest($result->ok === false, 'V1 creation must fail', $totalAssertions);
assertTest($result->code === 'ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING', 'V1 failure code must match', $totalAssertions);
assertTest($mockApiClient->callCount === 0, 'API client must not be called', $totalAssertions);
echo "PASSED\n";

// Test 3: SA missing, short, and malformed codes are guarded and lowercase normalizes to uppercase
$totalTests++;
echo 'Test 3: SA missing, short, malformed codes guarded and lowercase normalizes to uppercase... ';
$base = [
    'contact_person' => 'Al-Miftah',
    'phone_num' => '0500000000',
    'address' => 'Street',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'country' => 'SA',
];

$threwMissing = false;
try {
    AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => '']);
} catch (AliExpressInvalidShippingAddressException $e) {
    $threwMissing = true;
}
assertTest($threwMissing, 'Missing zip must throw exception', $totalAssertions);

$threwShort = false;
try {
    AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => 'ABC1234']); // 7 chars
} catch (AliExpressInvalidShippingAddressException $e) {
    $threwShort = true;
}
assertTest($threwShort, '7-char zip must throw exception', $totalAssertions);

$threwLong = false;
try {
    AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => 'ABCDE1234']); // 9 chars
} catch (AliExpressInvalidShippingAddressException $e) {
    $threwLong = true;
}
assertTest($threwLong, '9-char zip must throw exception', $totalAssertions);

$threwSpecial = false;
try {
    AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => 'ABCD-123']); // special char
} catch (AliExpressInvalidShippingAddressException $e) {
    $threwSpecial = true;
}
assertTest($threwSpecial, 'Special chars in zip must throw exception', $totalAssertions);

$res = AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => 'rocd4124']);
assertTest($res->zip === 'ROCD4124', 'Lowercase valid code must normalize to uppercase ROCD4124', $totalAssertions);
echo "PASSED\n";

// Test 4: Non-SA fixtures (US and AE) do not fail due to Saudi regex
$totalTests++;
echo 'Test 4: Non-SA fixtures (US and AE) do not fail due to Saudi regex... ';
$usAddress = [
    'contact_person' => 'John Doe',
    'phone_num' => '1234567890',
    'phone_country' => '1',
    'address' => '123 Main St',
    'city' => 'New York',
    'province' => 'NY',
    'zip' => '10001',
    'country' => 'US',
];

$usValidated = AliExpressShippingAddressValidator::normalizeAndValidate($usAddress);
assertTest($usValidated->zip === '10001', 'US zip must be preserved', $totalAssertions);
assertTest($usValidated->country === 'US', 'US country must be US', $totalAssertions);

$aeAddress = [
    'contact_person' => 'Dubai Hub',
    'phone_num' => '0501234567',
    'phone_country' => '971',
    'address' => 'Business Bay',
    'city' => 'Dubai',
    'province' => 'Dubai',
    'zip' => '00000',
    'country' => 'AE',
];

$aeValidated = AliExpressShippingAddressValidator::normalizeAndValidate($aeAddress);
assertTest($aeValidated->zip === '00000', 'AE zip must be preserved', $totalAssertions);
assertTest($aeValidated->country === 'AE', 'AE country must be AE', $totalAssertions);
echo "PASSED\n";

// Test 5: Masked summary and string representation do not leak raw address, phone, or secrets
$totalTests++;
echo 'Test 5: Masked summary and string representation do not leak raw address, phone, or secrets... ';
$validated = AliExpressShippingAddressValidator::normalizeAndValidate([
    'contact_person' => 'Al-Miftah Warehouse Officer',
    'phone_num' => '0501234567',
    'phone_country' => '966',
    'address' => 'Secret Building 123, Private Way',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => 'RNNA4124',
    'country' => 'SA',
]);

$summary = $validated->getMaskedSummary();
assertTest($summary['zip_masked'] === 'RN****24', 'Masked zip must be RN****24', $totalAssertions);
assertTest($summary['zip_length'] === 8, 'Zip length must be 8', $totalAssertions);
assertTest(! isset($summary['phone']), 'Phone must not be in summary', $totalAssertions);
assertTest(! isset($summary['address']), 'Address must not be in summary', $totalAssertions);

$str = (string) $validated;
assertTest(str_contains($str, '[ValidatedAliExpressShippingAddress: country=SA, zip_len=8]'), 'String must contain safe signature', $totalAssertions);
assertTest(! str_contains($str, 'RNNA4124'), 'String representation must not contain raw zip', $totalAssertions);
assertTest(! str_contains($str, '0501234567'), 'String representation must not contain phone', $totalAssertions);
assertTest(! str_contains($str, 'Secret Building'), 'String representation must not contain address line', $totalAssertions);
echo "PASSED\n";

// Test 6: V2 Gateway preflight and submitUnpaid are both strictly guarded
$totalTests++;
echo 'Test 6: V2 Gateway preflight and submitUnpaid are both strictly guarded... ';
$mockApiClientV2 = new IsolatedMockApiClient;
$mockOAuthV2 = new IsolatedMockOAuthService;

$gateway = new AliExpressOrderSubmissionGateway($mockApiClientV2, $mockOAuthV2);

$origEnv = app()->environment();
app()['env'] = 'testing';

$draft = new ExternalOrderDraft(
    supplierPurchaseOrderId: 999,
    correlationKey: 'CORR-TEST-1',
    items: [
        ['supplier_product_id' => '1005001', 'supplier_sku_id' => 'sku-1', 'qty' => 1],
    ],
    overrideShippingAddress: [
        'contact_person' => 'Al-Miftah Transport',
        'phone_num' => '0500000000',
        'address' => 'Southern Ring Road',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '11564', // Invalid 5-digit postal code
        'country' => 'SA',
    ]
);

// In testing env, override is allowed and normalized through validator
$preflight = $gateway->preflight($draft);
assertTest($preflight->isSuccess === false, 'Preflight must fail for invalid SA address', $totalAssertions);
assertTest($preflight->errorCode === 'ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING', 'Preflight error code must match', $totalAssertions);

$submission = $gateway->submitUnpaid($draft);
assertTest($submission instanceof ExternalOrderSubmissionFailed, 'Submission must fail for invalid SA address', $totalAssertions);
assertTest($submission->errorCode === 'ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING', 'Submission error code must match', $totalAssertions);
assertTest($mockApiClientV2->callCount === 0, 'V2 API client must not be called', $totalAssertions);

app()['env'] = $origEnv;
echo "PASSED\n";

echo "\n======================================================================\n";
echo "  TEST SUMMARY: {$totalTests} tests passed, {$totalAssertions} assertions verified\n";
echo "======================================================================\n";
