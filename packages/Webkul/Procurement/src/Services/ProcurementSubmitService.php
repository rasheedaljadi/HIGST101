<?php

namespace Webkul\Procurement\Services;

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Exceptions\ExternalOrderSubmissionException;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ExternalPlatformOrderItem;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Security\ProcurementAcl;

class ProcurementSubmitService
{
    /**
     * Submit an approved batch and its supplier purchase orders to AliExpress.
     *
     * @throws DomainException
     */
    public function submitBatch(int $batchId, int $actorId): ProcurementBatch
    {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_SUBMIT);

        return DB::transaction(function () use ($batchId, $actorId) {
            /** @var ProcurementBatch $batch */
            $batch = ProcurementBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            if ($batch->state !== ProcurementBatch::STATE_APPROVED && $batch->state !== ProcurementBatch::STATE_READY_FOR_REVIEW) {
                throw new DomainException("Cannot submit batch in '{$batch->state}' state. Must be approved first.");
            }

            if (strtoupper((string) $batch->currency_code) !== 'USD') {
                throw new DomainException("Batch currency is '{$batch->currency_code}'. V2 strictly allows USD only. Submission halted for manual review.");
            }

            $batch->update([
                'state' => ProcurementBatch::STATE_SUBMITTED_TO_PROVIDER,
            ]);

            foreach ($batch->supplierOrders as $spo) {
                $this->submitSupplierPurchaseOrder($spo->id, $actorId);
            }

            $batch->update([
                'state' => ProcurementBatch::STATE_AWAITING_MANUAL_PAYMENT,
            ]);

            ProcurementAuditLog::create([
                'auditable_type' => ProcurementBatch::class,
                'auditable_id' => $batch->id,
                'action' => 'batch_submitted',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => ProcurementBatch::STATE_APPROVED,
                'new_state' => ProcurementBatch::STATE_AWAITING_MANUAL_PAYMENT,
                'details' => ['submitted_orders_count' => $batch->supplierOrders->count()],
                'correlation_id' => "batch-{$batch->id}",
            ]);

            return $batch->fresh(['supplierOrders.platformOrders']);
        });
    }

    /**
     * Submit a single Supplier Purchase Order to AliExpress.
     *
     * @throws ExternalOrderSubmissionException|DomainException
     */
    public function submitSupplierPurchaseOrder(
        int $spoId,
        int $actorId,
        VerifiedExternalOrderCreated|ExternalOrderSubmissionFailed|null $providedResult = null
    ): SupplierPurchaseOrder {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_SUBMIT);

        return DB::transaction(function () use ($spoId, $actorId, $providedResult) {
            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $spoId)->lockForUpdate()->firstOrFail();

            // 1. Re-verify price and currency
            if (strtoupper((string) $spo->currency_code) !== 'USD') {
                $spo->update([
                    'state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                    'payment_state' => 'currency_exception',
                    'external_sync_state' => 'currency_exception',
                ]);
                throw new DomainException("Supplier order #{$spo->id} has non-USD currency. Flagged for review.");
            }

            $correlationKey = $spo->purchase_order_number;

            // 2. Resolve submission result (provided or dispatched)
            $result = $providedResult ?? $this->dispatchToProvider($spo, $correlationKey);

            // 3. Handle Failure: Strictly reject unverified responses and record failure state
            if ($result instanceof ExternalOrderSubmissionFailed) {
                $platformOrder = ExternalPlatformOrder::create([
                    'supplier_purchase_order_id' => $spo->id,
                    'provider' => $spo->provider,
                    'provider_account_id' => $spo->provider_account_id,
                    'supplier_store_id' => $spo->supplier_store_id,
                    'external_order_id' => null, // Strictly null, never synthetic
                    'correlation_key' => $correlationKey,
                    'provider_request_id' => $result->providerRequestId,
                    'failure_code' => $result->errorCode,
                    'failure_message' => $result->errorMessageMasked,
                    'raw_status' => 'SUBMISSION_FAILED',
                    'normalized_status' => ExternalPlatformOrder::STATUS_SUBMISSION_FAILED,
                    'currency_code' => 'USD',
                    'last_synced_at' => now(),
                    'snapshots' => [
                        'created_via' => 'ProcurementSubmitService',
                        'submitted_at' => now()->toIso8601String(),
                        'expected_total' => (float) $spo->expected_total,
                        'submission_failed' => true,
                        'error_code' => $result->errorCode,
                        'error_message' => $result->errorMessageMasked,
                        'provider_request_id' => $result->providerRequestId,
                        'retry_classification' => $result->retryClassification,
                    ],
                ]);

                foreach ($spo->items as $item) {
                    ExternalPlatformOrderItem::create([
                        'external_platform_order_id' => $platformOrder->id,
                        'supplier_purchase_order_item_id' => $item->id,
                        'external_sku_id' => $item->supplier_sku_id,
                        'quantity' => $item->qty_ordered,
                        'actual_item_amount' => $item->qty_ordered * $item->expected_unit_cost,
                        'actual_shipping_amount' => 0.0000,
                        'actual_tax_amount' => 0.0000,
                    ]);
                }

                $spo->update([
                    'state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                    'payment_state' => 'submission_failed',
                    'external_sync_state' => 'submission_failed',
                ]);

                ProcurementAuditLog::create([
                    'auditable_type' => SupplierPurchaseOrder::class,
                    'auditable_id' => $spo->id,
                    'action' => 'supplier_order_submission_failed',
                    'actor_id' => $actorId,
                    'actor_type' => 'admin',
                    'old_state' => $spo->getOriginal('state') ?? SupplierPurchaseOrder::STATE_DRAFT,
                    'new_state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                    'details' => [
                        'error_code' => $result->errorCode,
                        'error_message' => $result->errorMessageMasked,
                        'provider_request_id' => $result->providerRequestId,
                        'correlation_key' => $correlationKey,
                        'external_order_created' => false,
                    ],
                    'correlation_id' => "spo-{$spo->id}-fail",
                ]);

                return $spo->fresh(['platformOrders']);
            }

            // 4. Handle Verified Success
            /** @var VerifiedExternalOrderCreated $result */
            $externalOrderId = $result->externalOrderId;
            if (empty($externalOrderId)) {
                throw new DomainException('Authoritative external order ID is empty. Submission aborted.');
            }

            $platformOrder = ExternalPlatformOrder::create([
                'supplier_purchase_order_id' => $spo->id,
                'provider' => $spo->provider,
                'provider_account_id' => $spo->provider_account_id,
                'supplier_store_id' => $spo->supplier_store_id,
                'external_order_id' => $externalOrderId,
                'correlation_key' => $correlationKey,
                'provider_request_id' => $result->providerRequestId,
                'raw_status' => $result->providerStatus,
                'normalized_status' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
                'currency_code' => 'USD',
                'last_synced_at' => now(),
                'snapshots' => array_merge([
                    'created_via' => 'ProcurementSubmitService',
                    'submitted_at' => now()->toIso8601String(),
                    'expected_total' => (float) $spo->expected_total,
                ], $result->responseMetadata),
            ]);

            foreach ($spo->items as $item) {
                ExternalPlatformOrderItem::create([
                    'external_platform_order_id' => $platformOrder->id,
                    'supplier_purchase_order_item_id' => $item->id,
                    'external_sku_id' => $item->supplier_sku_id,
                    'quantity' => $item->qty_ordered,
                    'actual_item_amount' => $item->qty_ordered * $item->expected_unit_cost,
                    'actual_shipping_amount' => 0.0000,
                    'actual_tax_amount' => 0.0000,
                ]);
            }

            $spo->update([
                'state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
                'payment_state' => 'awaiting_manual_payment',
                'external_sync_state' => 'order_created_wait_buyer_pay',
            ]);

            // Create immutable pre-submit snapshot
            ProcurementCostSnapshot::create([
                'snapshotable_type' => SupplierPurchaseOrder::class,
                'snapshotable_id' => $spo->id,
                'snapshot_type' => ProcurementCostSnapshot::TYPE_EXPECTED_BEFORE_SUBMIT,
                'items_subtotal' => $spo->expected_items_total,
                'shipping_amount' => $spo->expected_shipping_total,
                'discount_amount' => $spo->expected_discount_total,
                'tax_fee_amount' => 0.0000,
                'total_amount' => $spo->expected_total,
                'currency_code' => 'USD',
                'exchange_rate' => 1.000000,
                'allocation_basis' => 'proportionate_subtotal',
                'breakdown' => [
                    'external_order_id' => $externalOrderId,
                    'items_count' => $spo->items->count(),
                ],
                'external_reference' => $externalOrderId,
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'correlation_id' => "spo-{$spo->id}-submit",
                'snapshot_hash' => hash('sha256', "submit-{$spo->id}-{$externalOrderId}-{$spo->expected_total}"),
                'created_at' => now(),
            ]);

            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $spo->id,
                'action' => 'supplier_order_submitted',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => SupplierPurchaseOrder::STATE_DRAFT,
                'new_state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
                'details' => [
                    'external_order_id' => $externalOrderId,
                    'expected_total' => $spo->expected_total,
                    'correlation_key' => $correlationKey,
                ],
                'correlation_id' => "spo-{$spo->id}",
            ]);

            return $spo->fresh(['platformOrders.items']);
        });
    }

    /**
     * Dispatch call to external provider or return classified failure if live creation is not granted.
     */
    protected function dispatchToProvider(SupplierPurchaseOrder $spo, string $correlationKey): VerifiedExternalOrderCreated|ExternalOrderSubmissionFailed
    {
        // If in automated testing suite and mock dispatch is explicitly enabled for unit tests
        if (app()->environment('testing') && config('procurement.mock_dispatch_in_testing', true)) {
            return new VerifiedExternalOrderCreated(
                externalOrderId: 'TEST-EXT-ORD-'.$spo->id.'-'.substr(hash('crc32', $correlationKey), 0, 6),
                providerRequestId: 'test-req-'.$spo->id,
                providerStatus: 'WAIT_BUYER_PAY',
                responseMetadata: ['mock_test_dispatch' => true]
            );
        }

        // In Production/Staging: STRICTLY require verified live API grant and live verified response
        if (! config('procurement.v2_live_order_creation_enabled', false)) {
            return new ExternalOrderSubmissionFailed(
                errorCode: 'ORDER_CREATE_API_NOT_GRANTED',
                errorMessageMasked: 'Live external order creation is disabled pending verified Open Platform grant.',
                providerRequestId: null,
                retryClassification: 'non_retryable'
            );
        }

        // Live AliExpress API invocation with strict validation
        if (class_exists(AliExpressApiClient::class) && class_exists(AliExpressToken::class)) {
            $tokenRow = AliExpressToken::orderBy('id', 'desc')->first();
            if (! $tokenRow || empty($tokenRow->access_token)) {
                return new ExternalOrderSubmissionFailed(
                    errorCode: 'IllegalAccessToken',
                    errorMessageMasked: 'No valid AliExpress OAuth access token configured on server.',
                    providerRequestId: null,
                    retryClassification: 'non_retryable'
                );
            }

            try {
                /** @var AliExpressApiClient $client */
                $client = app(AliExpressApiClient::class);
                $firstItem = $spo->items()->first();

                $params = [
                    'param_place_order_request4_open_api_d_t_o' => [
                        'product_items' => [
                            [
                                'product_id' => (int) ($firstItem->supplier_product_id ?? 0),
                                'sku_attr' => (string) ($firstItem->supplier_sku_id ?? ''),
                                'product_count' => (int) ($firstItem->qty_ordered ?? 1),
                                'logistics_service_name' => 'CAINIAO_STANDARD',
                            ],
                        ],
                        'logistics_address' => [
                            'contact_person' => 'Hayest SA Ops',
                            'full_name' => 'Hayest Saudi Fulfillment Hub',
                            'address' => 'Ring Road Sorting Center',
                            'city' => 'Riyadh',
                            'province' => 'Riyadh',
                            'country' => 'SA',
                            'zip' => '11564',
                            'phone_country' => '+966',
                            'mobile_no' => '500000000',
                        ],
                        'out_order_id' => $correlationKey,
                    ],
                ];

                $response = $client->call('aliexpress.ds.order.create', $tokenRow->access_token, $params);

                // Check for transport or business error
                if (! $response['ok'] || ! empty($response['code']) || ! empty($response['body']['error_response'])) {
                    $errCode = $response['code'] ?? $response['body']['error_response']['code'] ?? 'API_ERROR';
                    $errMsg = $response['message'] ?? $response['body']['error_response']['msg'] ?? 'External order creation failed';
                    $reqId = $response['body']['error_response']['request_id'] ?? null;

                    return new ExternalOrderSubmissionFailed(
                        errorCode: (string) $errCode,
                        errorMessageMasked: (string) $errMsg,
                        providerRequestId: $reqId,
                        retryClassification: 'fatal',
                        rawResponse: $response
                    );
                }

                // Extract authoritative external order ID
                $orderList = $response['body']['aliexpress_ds_order_create_response']['result']['order_list'] ?? null;
                $numericOrderId = $response['body']['aliexpress_ds_order_create_response']['order_id'] ?? null;

                $extractedId = null;
                if (is_array($orderList) && ! empty($orderList[0])) {
                    $extractedId = (string) $orderList[0];
                } elseif (is_string($orderList) && ! empty($orderList)) {
                    $extractedId = $orderList;
                } elseif (! empty($numericOrderId)) {
                    $extractedId = (string) $numericOrderId;
                }

                if (! empty($extractedId)) {
                    return new VerifiedExternalOrderCreated(
                        externalOrderId: $extractedId,
                        providerRequestId: $response['body']['aliexpress_ds_order_create_response']['_trace_id_'] ?? null,
                        providerStatus: 'WAIT_BUYER_PAY',
                        responseMetadata: $response['body']
                    );
                }

                return new ExternalOrderSubmissionFailed(
                    errorCode: 'EMPTY_EXTERNAL_ORDER_ID',
                    errorMessageMasked: 'AliExpress returned HTTP 200 but without authoritative order ID.',
                    providerRequestId: null,
                    retryClassification: 'fatal',
                    rawResponse: $response
                );
            } catch (\Throwable $e) {
                return new ExternalOrderSubmissionFailed(
                    errorCode: 'TRANSPORT_EXCEPTION',
                    errorMessageMasked: $e->getMessage(),
                    providerRequestId: null,
                    retryClassification: 'transient'
                );
            }
        }

        return new ExternalOrderSubmissionFailed(
            errorCode: 'PROVIDER_CLIENT_NOT_FOUND',
            errorMessageMasked: 'AliExpress client is not available in current runtime.',
            providerRequestId: null,
            retryClassification: 'non_retryable'
        );
    }
}
