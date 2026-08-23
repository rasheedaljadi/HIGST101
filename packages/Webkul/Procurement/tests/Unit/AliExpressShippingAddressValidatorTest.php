<?php

namespace Webkul\Procurement\Tests\Unit;

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use App\Services\AliExpress\DTO\ValidatedAliExpressShippingAddress;
use App\Services\AliExpress\Exceptions\AliExpressInvalidShippingAddressException;
use App\Services\AliExpress\Shipping\AliExpressShippingAddressValidator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Webkul\Fulfillment\DataObjects\ShippingAddress;
use Webkul\Fulfillment\DataObjects\SupplierOrderLine;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressFulfillmentProvider;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;

test('Unit: SA valid fixture produces correct uppercase 8-char zip and matching V1 and V2 output', function () {
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

    expect($validated)->toBeInstanceOf(ValidatedAliExpressShippingAddress::class)
        ->and($validated->zip)->toBe('ABCD1234')
        ->and($validated->country)->toBe('SA')
        ->and($validated->phoneCountry)->toBe('966')
        ->and($validated->toLogisticsAddressArray()['zip'])->toBe('ABCD1234');

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
    expect($v1Validated->zip)->toBe('ABCD1234')
        ->and($v1Validated->toLogisticsAddressArray()['zip'])->toBe($validated->toLogisticsAddressArray()['zip']);
});

test('Unit: SA 5-digit postal fixture throws ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING and clientCalls = 0', function () {
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

    expect(fn () => AliExpressShippingAddressValidator::normalizeAndValidate($invalidPostalAddress))
        ->toThrow(AliExpressInvalidShippingAddressException::class);

    try {
        AliExpressShippingAddressValidator::normalizeAndValidate($invalidPostalAddress);
    } catch (AliExpressInvalidShippingAddressException $e) {
        expect($e->errorCode)->toBe('ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING')
            ->and($e->getMessage())->not->toContain('11564');
    }

    // Verify V1 provider rejects without calling API client
    $mockApiClient = Mockery::mock(AliExpressApiClient::class);
    $mockApiClient->shouldNotReceive('call');

    $mockOAuth = Mockery::mock(AliExpressOAuthService::class);
    $mockOAuth->shouldReceive('isConfigured')->andReturn(true);
    $mockToken = new AliExpressToken;
    $mockToken->access_token = 'mock_token';
    $mockOAuth->shouldReceive('latestToken')->andReturn($mockToken);

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
    expect($result->ok)->toBeFalse()
        ->and($result->code)->toBe('ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING');
});

test('Unit: SA missing, short, and malformed codes are guarded and lowercase normalizes to uppercase', function () {
    $base = [
        'contact_person' => 'Al-Miftah',
        'phone_num' => '0500000000',
        'address' => 'Street',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'country' => 'SA',
    ];

    // Missing zip
    expect(fn () => AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => '']))
        ->toThrow(AliExpressInvalidShippingAddressException::class);

    // 7 characters (too short)
    expect(fn () => AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => 'ABC1234']))
        ->toThrow(AliExpressInvalidShippingAddressException::class);

    // 9 characters (too long)
    expect(fn () => AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => 'ABCDE1234']))
        ->toThrow(AliExpressInvalidShippingAddressException::class);

    // Special characters
    expect(fn () => AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => 'ABCD-123']))
        ->toThrow(AliExpressInvalidShippingAddressException::class);

    // Valid lowercase 4-letter + 4-digit code
    $res = AliExpressShippingAddressValidator::normalizeAndValidate($base + ['zip' => 'rocd4124']);
    expect($res->zip)->toBe('ROCD4124');
});

test('Unit: Non-SA fixtures (US and AE) do not fail due to Saudi regex', function () {
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
    expect($usValidated->zip)->toBe('10001')
        ->and($usValidated->country)->toBe('US');

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
    expect($aeValidated->zip)->toBe('00000')
        ->and($aeValidated->country)->toBe('AE');
});

test('Unit: Masked summary and string representation do not leak raw address, phone, or secrets', function () {
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
    expect($summary['zip_masked'])->toBe('RN****24')
        ->and($summary['zip_length'])->toBe(8)
        ->and($summary)->not->toHaveKey('phone')
        ->and($summary)->not->toHaveKey('address');

    $str = (string) $validated;
    expect($str)->toContain('[ValidatedAliExpressShippingAddress: country=SA, zip_len=8]')
        ->and($str)->not->toContain('RNNA4124')
        ->and($str)->not->toContain('0501234567')
        ->and($str)->not->toContain('Secret Building');
});

test('Unit: V2 Gateway preflight and submitUnpaid are both strictly guarded', function () {
    // Set invalid postcode in DB
    DB::table('inventory_sources')->updateOrInsert(
        ['code' => 'default'],
        [
            'name' => 'Al-Miftah Main Hub',
            'contact_name' => 'Al-Miftah Transport',
            'contact_number' => '0500000000',
            'contact_email' => 'warehouse@hayest.com',
            'street' => 'Southern Ring Road',
            'city' => 'Riyadh',
            'state' => 'Riyadh',
            'country' => 'SA',
            'postcode' => '11564', // Invalid 5-digit postal code
        ]
    );

    /** @var AliExpressOrderSubmissionGateway $gateway */
    $gateway = app(AliExpressOrderSubmissionGateway::class);

    $draft = new ExternalOrderDraft(
        supplierPurchaseOrderId: 999,
        correlationKey: 'CORR-TEST-1',
        items: [
            ['supplier_product_id' => '1005001', 'supplier_sku_id' => 'sku-1', 'qty' => 1],
        ]
    );

    // Preflight should return failure with ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING
    $preflight = $gateway->preflight($draft);
    expect($preflight->isSuccess)->toBeFalse()
        ->and($preflight->errorCode)->toBe('ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING');

    // SubmitUnpaid should also fail with ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING
    $submission = $gateway->submitUnpaid($draft);
    expect($submission)->toBeInstanceOf(ExternalOrderSubmissionFailed::class)
        ->and($submission->errorCode)->toBe('ALIEXPRESS_SA_NATIONAL_ADDRESS_INVALID_OR_MISSING');
});
