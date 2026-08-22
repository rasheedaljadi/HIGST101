<?php

use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\AliExpressPollingService;
use Webkul\Procurement\Services\ProcurementExternalRemediationService;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

beforeEach(function () {
    config([
        'procurement.v2_enabled' => true,
        'procurement.v2_live_order_creation_enabled' => false,
    ]);
});

function createTestSupplierPurchaseOrder(): SupplierPurchaseOrder
{
    $product = Product::create([
        'type' => 'simple',
        'attribute_family_id' => 1,
        'sku' => 'SKU-TEST-'.uniqid(),
    ]);

    $batch = ProcurementBatch::create([
        'batch_number' => 'BATCH-TEST-'.uniqid(),
        'provider' => 'aliexpress',
        'provider_account_id' => 4586371333,
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => ProcurementBatch::STATE_APPROVED,
        'expected_total_cost' => 10.1900,
        'actual_total_cost' => 0.0000,
        'cost_variance_amount' => 0.0000,
        'lock_version' => 1,
    ]);

    $spo = SupplierPurchaseOrder::create([
        'batch_id' => $batch->id,
        'purchase_order_number' => 'SPO-TEST-'.uniqid(),
        'provider' => 'aliexpress',
        'provider_account_id' => 4586371333,
        'supplier_store_id' => '4586371333',
        'supplier_store_name' => 'Official Men Polo Store',
        'currency_code' => 'USD',
        'destination_signature' => 'hayest_dropship_sa',
        'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
        'expected_items_total' => 10.1900,
        'expected_shipping_total' => 0.0000,
        'expected_discount_total' => 0.0000,
        'expected_total' => 10.1900,
        'actual_items_total' => 0.0000,
        'actual_shipping_total' => 0.0000,
        'actual_discount_total' => 0.0000,
        'actual_total' => 0.0000,
        'cost_variance_amount' => 0.0000,
    ]);

    SupplierPurchaseOrderItem::create([
        'supplier_purchase_order_id' => $spo->id,
        'product_id' => $product->id,
        'supplier_product_id' => '1005008248073626',
        'supplier_sku_id' => '12000044371414236',
        'qty_ordered' => 1,
        'qty_received' => 0,
        'qty_cancelled' => 0,
        'expected_unit_cost' => 10.1900,
        'actual_unit_cost' => 0.0000,
    ]);

    return $spo;
}

test('HTTP 200 with error_response produces submission_failed and never sets awaiting manual payment', function () {
    $spo = createTestSupplierPurchaseOrder();
    $admin = Admin::first() ?? Admin::factory()->create();
    $submitService = app(ProcurementSubmitService::class);

    $failureDto = new ExternalOrderSubmissionFailed(
        errorCode: 'IllegalAccessToken',
        errorMessageMasked: 'The specified access token is invalid or expired',
        providerRequestId: '212a73a517874213795736385',
        retryClassification: 'fatal'
    );

    $resultSpo = $submitService->submitSupplierPurchaseOrder($spo->id, $admin->id, $failureDto);

    expect($resultSpo->state)->toBe(SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION)
        ->and($resultSpo->payment_state)->toBe('submission_failed')
        ->and($resultSpo->platformOrders)->toHaveCount(1);

    $platformOrder = $resultSpo->platformOrders->first();
    expect($platformOrder->external_order_id)->toBeNull()
        ->and($platformOrder->normalized_status)->toBe(ExternalPlatformOrder::STATUS_SUBMISSION_FAILED)
        ->and($platformOrder->failure_code)->toBe('IllegalAccessToken')
        ->and($platformOrder->provider_request_id)->toBe('212a73a517874213795736385')
        ->and($platformOrder->correlation_key)->toBe($spo->purchase_order_number);

    $auditLog = ProcurementAuditLog::where('auditable_id', $spo->id)
        ->where('action', 'supplier_order_submission_failed')
        ->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->details['external_order_created'])->toBeFalse();
});

test('response missing external ID fails and produces no fallback or local ID', function () {
    $spo = createTestSupplierPurchaseOrder();
    $admin = Admin::first() ?? Admin::factory()->create();
    $submitService = app(ProcurementSubmitService::class);

    $failureDto = new ExternalOrderSubmissionFailed(
        errorCode: 'EMPTY_EXTERNAL_ORDER_ID',
        errorMessageMasked: 'AliExpress returned HTTP 200 but without authoritative order ID.',
        providerRequestId: null,
        retryClassification: 'fatal'
    );

    $resultSpo = $submitService->submitSupplierPurchaseOrder($spo->id, $admin->id, $failureDto);

    expect($resultSpo->state)->toBe(SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION)
        ->and($resultSpo->payment_state)->toBe('submission_failed')
        ->and($resultSpo->platformOrders)->toHaveCount(1);

    $platformOrder = $resultSpo->platformOrders->first();

    expect($platformOrder->external_order_id)->toBeNull()
        ->and($platformOrder->normalized_status)->toBe(ExternalPlatformOrder::STATUS_SUBMISSION_FAILED);
});

test('response with verified official external ID sets awaiting_manual_payment and records snapshots', function () {
    $spo = createTestSupplierPurchaseOrder();
    $admin = Admin::first() ?? Admin::factory()->create();
    $submitService = app(ProcurementSubmitService::class);

    $successDto = new VerifiedExternalOrderCreated(
        externalOrderId: '8201948572910482',
        providerRequestId: 'trace-82910482-req',
        providerStatus: 'WAIT_BUYER_PAY',
        responseMetadata: ['raw_status' => 'WAIT_BUYER_PAY']
    );

    $resultSpo = $submitService->submitSupplierPurchaseOrder($spo->id, $admin->id, $successDto);

    expect($resultSpo->state)->toBe(SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT)
        ->and($resultSpo->payment_state)->toBe('awaiting_manual_payment')
        ->and($resultSpo->platformOrders)->toHaveCount(1);

    $platformOrder = $resultSpo->platformOrders->first();
    expect($platformOrder->external_order_id)->toBe('8201948572910482')
        ->and($platformOrder->correlation_key)->toBe($spo->purchase_order_number)
        ->and($platformOrder->normalized_status)->toBe(ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY);
});

test('polling service strictly refuses to sync orders without authoritative external_order_id', function () {
    $spo = createTestSupplierPurchaseOrder();
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spo->id,
        'provider' => 'aliexpress',
        'external_order_id' => null, // Null / unverified
        'normalized_status' => ExternalPlatformOrder::STATUS_SUBMISSION_FAILED,
    ]);

    $pollingService = app(AliExpressPollingService::class);

    expect(fn () => $pollingService->syncOrder($platformOrder, ['status' => 'SHIPPED']))
        ->toThrow(DomainException::class);
});

test('remediation service is idempotent, cleanses synthetic IDs, and records failure audit', function () {
    $spo = createTestSupplierPurchaseOrder();
    $platformOrder = ExternalPlatformOrder::create([
        'supplier_purchase_order_id' => $spo->id,
        'provider' => 'aliexpress',
        'external_order_id' => 'AE-LIVE-20260822-4586371333', // Synthetic ID to cleanse
        'normalized_status' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
    ]);
    $spo->update(['state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT]);

    $remediationService = app(ProcurementExternalRemediationService::class);
    $admin = Admin::first() ?? Admin::factory()->create();

    // First call
    $remediatedSpo = $remediationService->markFailedExternalSubmission(
        $spo->id,
        $admin->id,
        'IllegalAccessToken',
        'The specified access token is invalid or expired',
        '212a73a517874213795736385',
        'AE-LIVE-20260822-4586371333'
    );

    expect($remediatedSpo->state)->toBe(SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION)
        ->and($remediatedSpo->payment_state)->toBe('submission_failed');

    $cleanPlatformOrder = $remediatedSpo->platformOrders->first();
    expect($cleanPlatformOrder->external_order_id)->toBeNull()
        ->and($cleanPlatformOrder->normalized_status)->toBe(ExternalPlatformOrder::STATUS_SUBMISSION_FAILED)
        ->and($cleanPlatformOrder->snapshots['synthetic_fallback_rejected'])->toBe('AE-LIVE-20260822-4586371333');

    $auditCount = ProcurementAuditLog::where('auditable_id', $spo->id)
        ->where('action', 'synthetic_external_order_remediated')
        ->count();
    expect($auditCount)->toBe(1);

    // Second call (Idempotency check)
    $secondCall = $remediationService->markFailedExternalSubmission(
        $spo->id,
        $admin->id,
        'IllegalAccessToken',
        'The specified access token is invalid or expired',
        '212a73a517874213795736385',
        'AE-LIVE-20260822-4586371333'
    );

    $auditCountAfterSecondCall = ProcurementAuditLog::where('auditable_id', $spo->id)
        ->where('action', 'synthetic_external_order_remediated')
        ->count();
    expect($auditCountAfterSecondCall)->toBe(1); // No duplicate audit created
});
