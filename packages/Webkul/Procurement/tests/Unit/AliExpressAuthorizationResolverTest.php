<?php

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\Exceptions\AliExpressAuthorizationUnavailableException;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Services\AliExpressAuthorizationResolver;
use Webkul\Procurement\Services\ProcurementEligibilityService;
use Webkul\Product\Contracts\Product;
use Webkul\Sales\Contracts\OrderItem;

test('Unit: resolver successfully resolves active V1 OAuth token into authorized context', function () {
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
    $mockOAuth->shouldReceive('latestToken')
        ->once()
        ->andReturn($token);

    $resolver = new AliExpressAuthorizationResolver($mockOAuth);
    $context = $resolver->resolveForDropshipperSubmission();

    expect($context->accessToken)->toBe('mock_live_access_token_xyz')
        ->and($context->accountIdentifier)->toBe('4586371333')
        ->and($context->sellerId)->toBe('4586371333')
        ->and($context->accountMasked)->toBe('b***@highest-internal.test')
        ->and($context->isValid)->toBeTrue();

    $summary = $context->getMaskedSummary();
    expect($summary['account_identifier'])->toBe('4586***')
        ->and($summary['seller_id'])->toBe('4586***')
        ->and($summary['is_valid'])->toBeTrue()
        ->and(array_key_exists('access_token', $summary))->toBeFalse();
});

test('Unit: resolver throws ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE when no token is stored', function () {
    $mockOAuth = Mockery::mock(AliExpressOAuthService::class);
    $mockOAuth->shouldReceive('latestToken')
        ->once()
        ->andReturnNull();

    $resolver = new AliExpressAuthorizationResolver($mockOAuth);

    expect(fn () => $resolver->resolveForDropshipperSubmission())
        ->toThrow(AliExpressAuthorizationUnavailableException::class);

    expect($resolver->hasValidAuthorization())->toBeFalse();
});

test('Unit: resolver throws ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE when token is expired', function () {
    $expiredToken = new AliExpressToken([
        'account' => 'buyer@highest-internal.test',
        'account_id' => '4586371333',
        'access_token' => 'mock_expired_token',
        'access_token_expires_at' => Carbon::now()->subMinute(),
    ]);

    $mockOAuth = Mockery::mock(AliExpressOAuthService::class);
    $mockOAuth->shouldReceive('latestToken')
        ->once()
        ->andReturn($expiredToken);

    $resolver = new AliExpressAuthorizationResolver($mockOAuth);

    expect(fn () => $resolver->resolveForDropshipperSubmission())
        ->toThrow(AliExpressAuthorizationUnavailableException::class);
});

test('Unit: gateway uses resolver without calling find(provider_account_id) or assuming token PK', function () {
    $token = new AliExpressToken([
        'account' => 'buyer@highest-internal.test',
        'account_id' => '4586371333',
        'seller_id' => '4586371333',
        'access_token' => 'mock_valid_token_123',
        'access_token_expires_at' => Carbon::now()->addHour(),
    ]);

    $mockOAuth = Mockery::mock(AliExpressOAuthService::class);
    $mockOAuth->shouldReceive('latestToken')->andReturn($token);
    // Crucial: getTokenById MUST NEVER be called
    $mockOAuth->shouldNotReceive('getTokenById');

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

    DB::table('inventory_sources')->updateOrInsert(
        ['code' => 'default'],
        [
            'name' => 'Key Management Default Hub',
            'contact_name' => 'Logistics Admin',
            'contact_number' => '0500000000',
            'contact_email' => 'hub@hayest.com',
            'street' => 'King Fahd Road',
            'city' => 'Riyadh',
            'state' => 'Riyadh',
            'country' => 'SA',
            'postcode' => '12345',
        ]
    );

    $resolver = new AliExpressAuthorizationResolver($mockOAuth);
    $gateway = new AliExpressOrderSubmissionGateway($mockApiClient, $mockOAuth, $resolver);

    // Draft with historical providerAccountId = 1 (MUST NOT trigger find(1) or error)
    $draft = new ExternalOrderDraft(
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

    $preflight = $gateway->preflight($draft);

    expect($preflight->isSuccess)->toBeTrue()
        ->and($preflight->shippingServiceName)->toBe('CAINIAO_FULFILLMENT_STD')
        ->and($preflight->shippingCostMinor)->toBe(500)
        ->and($preflight->resolvedSkuAttr)->toBe('14:29;200000124:200000364');
});

test('Unit: ProcurementEligibilityService classifies order item with provider_account_id as null without default ?? 1', function () {
    $service = app(ProcurementEligibilityService::class);

    $mockProduct = Mockery::mock(Product::class);
    $mockProduct->shouldReceive('getAttribute')->with('type')->andReturn('simple');
    $mockProduct->shouldReceive('getAttribute')->with('id')->andReturn(3163);
    $mockProduct->shouldReceive('getAttribute')->with('cost')->andReturn(27.15);

    $mockOrderItem = Mockery::mock(OrderItem::class);
    $mockOrderItem->shouldReceive('getAttribute')->with('product')->andReturn($mockProduct);
    $mockOrderItem->shouldReceive('getAttribute')->with('additional')->andReturn([
        'supplier_product_id' => '1005010378829324',
        'supplier_sku_id' => '12000052207602660',
        'supplier_store_id' => '1102890756',
        'supplier_store_name' => 'Shop1102890756 Store',
        'supplier_unit_cost' => 27.15,
        // Notice: NO provider_account_id in payload
    ]);

    $classification = $service->classifyOrderItem($mockOrderItem);

    expect($classification['is_imported'])->toBeTrue()
        ->and($classification['provider'])->toBe('aliexpress')
        ->and($classification['provider_account_id'])->toBeNull()
        ->and($classification['supplier_store_id'])->toBe('1102890756')
        ->and($classification['metadata_status'])->toBe('valid');
});

test('Unit: Historical SPO #35 and EPO #26 remain immutable in failed audit state', function () {
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

    expect($spo->state)->toBe('supplier_exception')
        ->and($spo->payment_state)->toBe('submission_failed')
        ->and($epo->external_order_id)->toBeNull()
        ->and($epo->failure_code)->toBe('IllegalAccessToken');
});
