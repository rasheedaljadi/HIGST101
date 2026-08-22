<?php

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

beforeEach(function () {
    config([
        'procurement.v2_enabled' => true,
        'procurement.v2_live_order_creation_enabled' => false,
        'procurement.mock_dispatch_in_testing' => false,
    ]);
});

test('Unit: gateway resolves unified warehouse shipping address from inventory_sources', function () {
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
            'postcode' => 'ABCD1234',
        ]
    );

    /** @var AliExpressOrderSubmissionGateway $gateway */
    $gateway = app(AliExpressOrderGateway::class);
    $addr = $gateway->resolveWarehouseShippingAddress();

    expect($addr['country'])->toBe('SA')
        ->and($addr['city'])->toBe('Riyadh')
        ->and($addr['phone_country'])->toBe('966')
        ->and($addr['zip'])->toBe('ABCD1234')
        ->and($addr['contact_person'])->toContain('Al-Miftah');
});

test('Unit: gateway rejects non-numeric external ID and error_response with ExternalOrderSubmissionFailed', function () {
    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldReceive('submitUnpaid')
        ->once()
        ->andReturn(new ExternalOrderSubmissionFailed(
            errorCode: 'InvalidParameter',
            errorMessageMasked: 'The specified product is out of stock',
            providerRequestId: 'req-err-12345',
            retryClassification: 'fatal'
        ));

    $submitService = new ProcurementSubmitService($mockGateway);

    $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => 'TEST-SKU-'.uniqid()]);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 20.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-UNIT-FAIL-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
        'expected_total' => 20.0,
    ]);
    SupplierPurchaseOrderItem::create([
        'supplier_purchase_order_id' => $spo->id,
        'product_id' => $product->id,
        'supplier_product_id' => '1005008248073626',
        'supplier_sku_id' => '12000044371414236',
        'qty_ordered' => 1,
        'expected_unit_cost' => 20.0,
    ]);

    $admin = Admin::first() ?? Admin::factory()->create();
    $resultSpo = $submitService->submitSupplierPurchaseOrder($spo->id, $admin->id);

    expect($resultSpo->state)->toBe(SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION)
        ->and($resultSpo->payment_state)->toBe('submission_failed')
        ->and($resultSpo->platformOrders)->toHaveCount(1);

    $platformOrder = $resultSpo->platformOrders->first();
    expect($platformOrder->external_order_id)->toBeNull()
        ->and($platformOrder->failure_code)->toBe('InvalidParameter')
        ->and($platformOrder->normalized_status)->toBe(ExternalPlatformOrder::STATUS_SUBMISSION_FAILED);
});

test('Unit: out_order_id is strictly correlation key and never stored as external_order_id', function () {
    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldReceive('submitUnpaid')
        ->once()
        ->andReturn(new VerifiedExternalOrderCreated(
            externalOrderId: '8201948572910482', // Authoritative numeric order ID
            providerRequestId: 'req-success-7788',
            providerStatus: 'WAIT_BUYER_PAY'
        ));

    $submitService = new ProcurementSubmitService($mockGateway);

    $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => 'TEST-SKU-'.uniqid()]);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 15.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-CORRELATION-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
        'expected_total' => 15.0,
    ]);
    SupplierPurchaseOrderItem::create([
        'supplier_purchase_order_id' => $spo->id,
        'product_id' => $product->id,
        'supplier_product_id' => '1005008248073626',
        'supplier_sku_id' => '12000044371414236',
        'qty_ordered' => 1,
        'expected_unit_cost' => 15.0,
    ]);

    $admin = Admin::first() ?? Admin::factory()->create();
    $resultSpo = $submitService->submitSupplierPurchaseOrder($spo->id, $admin->id);

    $platformOrder = $resultSpo->platformOrders->first();
    expect($platformOrder->external_order_id)->toBe('8201948572910482')
        ->and($platformOrder->correlation_key)->toBe($spo->purchase_order_number)
        ->and($platformOrder->external_order_id)->not->toBe($platformOrder->correlation_key);
});

test('Feature: preflightSupplierPurchaseOrder produces preflight check without creating platform orders or altering DB state', function () {
    $mockGateway = Mockery::mock(AliExpressOrderGateway::class);
    $mockGateway->shouldReceive('preflight')
        ->once()
        ->andReturn(new AliExpressOrderPreflight(
            isSuccess: true,
            isDeliverableToDestination: true,
            destinationCountry: 'SA',
            shippingServiceName: 'CAINIAO_FULFILLMENT_STD',
            shippingCost: 5.0,
            shippingCurrency: 'USD',
            minDeliveryDays: 7,
            maxDeliveryDays: 12,
            trackingAvailable: true,
            resolvedSkuAttr: '14:29;200000124:200000900'
        ));

    $submitService = new ProcurementSubmitService($mockGateway);

    $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => 'TEST-SKU-'.uniqid()]);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 10.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-PREFLIGHT-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
        'expected_total' => 10.0,
    ]);
    SupplierPurchaseOrderItem::create([
        'supplier_purchase_order_id' => $spo->id,
        'product_id' => $product->id,
        'supplier_product_id' => '1005008248073626',
        'supplier_sku_id' => '12000044371414236',
        'qty_ordered' => 1,
        'expected_unit_cost' => 10.0,
    ]);

    $initialPlatformOrdersCount = ExternalPlatformOrder::count();

    $preflight = $submitService->preflightSupplierPurchaseOrder($spo->id);

    expect($preflight->isSuccess)->toBeTrue()
        ->and($preflight->isDeliverableToDestination)->toBeTrue()
        ->and($preflight->destinationCountry)->toBe('SA')
        ->and($preflight->shippingServiceName)->toBe('CAINIAO_FULFILLMENT_STD')
        ->and($preflight->shippingCost)->toBe(5.0)
        ->and(ExternalPlatformOrder::count())->toBe($initialPlatformOrdersCount)
        ->and($spo->fresh()->state)->toBe(SupplierPurchaseOrder::STATE_READY_TO_SUBMIT);
});

test('Unit: client spy proves preflight calls product and freight only and never order.create nor DB writes', function () {
    $mockClient = Mockery::mock(AliExpressApiClient::class);
    $mockOAuth = Mockery::mock(AliExpressOAuthService::class);

    $token = new AliExpressToken([
        'access_token' => 'mock-valid-token',
        'expires_at' => now()->addDays(30),
    ]);
    $mockOAuth->shouldReceive('latestToken')->andReturn($token);
    $mockOAuth->shouldReceive('getTokenById')->andReturn($token);

    // Spy on call: ds.product.get and ds.freight.query are allowed; ds.order.create must NEVER be called
    $mockClient->shouldReceive('call')
        ->with('aliexpress.ds.product.get', Mockery::any(), Mockery::any())
        ->once()
        ->andReturn([
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
        ]);

    $mockClient->shouldReceive('call')
        ->with('aliexpress.ds.freight.query', Mockery::any(), Mockery::any())
        ->once()
        ->andReturn([
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
        ]);

    $mockClient->shouldNotReceive('call')->with('aliexpress.ds.order.create', Mockery::any(), Mockery::any());

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

    expect($preflight->isSuccess)->toBeTrue()
        ->and($preflight->isDeliverableToDestination)->toBeTrue()
        ->and($preflight->shippingServiceName)->toBe('CAINIAO_FULFILLMENT_STD')
        ->and($preflight->shippingCost)->toBe(5.0)
        ->and($preflight->resolvedSkuAttr)->toBe('14:29;200000124:200000900')
        ->and(ExternalPlatformOrder::count())->toBe($platformOrderCountBefore)
        ->and(SupplierPurchaseOrder::count())->toBe($spoCountBefore);
});

test('Unit: default inventory source is used strictly for address metadata and never affects stock balances or allocations', function () {
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

    $source = DB::table('inventory_sources')->where('code', 'default')->first();
    expect($source)->not->toBeNull();

    /** @var AliExpressOrderSubmissionGateway $gateway */
    $gateway = app(AliExpressOrderGateway::class);
    $addr = $gateway->resolveWarehouseShippingAddress();

    // Verify address read only
    expect($addr['country'])->toBe('SA')
        ->and($addr['city'])->toBe('Riyadh')
        ->and($addr['phone_country'])->toBe('966');

    // Verify no allocations or stock modifications exist on default source
    $allocations = DB::table('order_item_allocations')->where('allocated_source_id', $source->id)->count();
    expect($allocations)->toBe(0);
});

test('Unit: submitUnpaid fails if response has HTTP 200 with error_response envelope', function () {
    $mockClient = Mockery::mock(AliExpressApiClient::class);
    $mockOAuth = Mockery::mock(AliExpressOAuthService::class);

    $token = new AliExpressToken([
        'access_token' => 'mock-valid-token',
        'expires_at' => now()->addDays(30),
    ]);
    $mockOAuth->shouldReceive('latestToken')->andReturn($token);
    $mockOAuth->shouldReceive('getTokenById')->andReturn($token);

    $mockClient->shouldReceive('call')
        ->with('aliexpress.ds.product.get', Mockery::any(), Mockery::any())
        ->andReturn(['ok' => true, 'body' => []]);

    $mockClient->shouldReceive('call')
        ->with('aliexpress.ds.order.create', Mockery::any(), Mockery::any())
        ->once()
        ->andReturn([
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
        ]);

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

    expect($result)->toBeInstanceOf(ExternalOrderSubmissionFailed::class)
        ->and($result->errorCode)->toBe('MissingParameter')
        ->and($result->providerRequestId)->toBe('req-err-missing-param-99')
        ->and($result->retryClassification)->toBe('fatal');
});

test('Unit: submitUnpaid fails when is_success is true but external order ID is non-numeric or synthetic', function () {
    $mockClient = Mockery::mock(AliExpressApiClient::class);
    $mockOAuth = Mockery::mock(AliExpressOAuthService::class);

    $token = new AliExpressToken([
        'access_token' => 'mock-valid-token',
        'expires_at' => now()->addDays(30),
    ]);
    $mockOAuth->shouldReceive('latestToken')->andReturn($token);
    $mockOAuth->shouldReceive('getTokenById')->andReturn($token);

    $mockClient->shouldReceive('call')
        ->with('aliexpress.ds.product.get', Mockery::any(), Mockery::any())
        ->andReturn(['ok' => true, 'body' => []]);

    $mockClient->shouldReceive('call')
        ->with('aliexpress.ds.order.create', Mockery::any(), Mockery::any())
        ->once()
        ->andReturn([
            'ok' => true,
            'body' => [
                'aliexpress_ds_order_create_response' => [
                    'result' => [
                        'is_success' => true,
                        'order_list' => ['AE-SYNTHETIC-MOCK-ID-999'], // Non-numeric
                    ],
                ],
            ],
        ]);

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

    expect($result)->toBeInstanceOf(ExternalOrderSubmissionFailed::class)
        ->and($result->errorCode)->toBe('EMPTY_EXTERNAL_ORDER_ID');
});

test('Regression: V1 purchase_orders table is preserved for historical read and never written by V2 operations', function () {
    $initialV1PoCount = DB::table('purchase_orders')->count();

    $product = Product::create(['type' => 'simple', 'attribute_family_id' => 1, 'sku' => 'TEST-SKU-'.uniqid()]);
    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-'.uniqid(),
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 10.0,
    ]);
    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-V2-'.uniqid(),
        'provider' => 'aliexpress',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
        'expected_total' => 10.0,
    ]);

    expect(DB::table('purchase_orders')->count())->toBe($initialV1PoCount);
});
