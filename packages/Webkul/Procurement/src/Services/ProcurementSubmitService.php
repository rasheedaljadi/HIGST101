<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\ExternalOrderDraft;
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
    public function __construct(
        protected ?AliExpressOrderGateway $orderGateway = null
    ) {
        $this->orderGateway ??= app(AliExpressOrderGateway::class);
    }

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
     * Execute a preflight check for a Supplier Purchase Order without modifying DB or creating order.
     */
    public function preflightSupplierPurchaseOrder(int $spoId): AliExpressOrderPreflight
    {
        /** @var SupplierPurchaseOrder $spo */
        $spo = SupplierPurchaseOrder::with('items')->findOrFail($spoId);
        $draft = $this->buildOrderDraft($spo, $spo->purchase_order_number);

        return $this->orderGateway->preflight($draft);
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

            // 2. Resolve submission result (provided or dispatched via unified gateway)
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
     * Dispatch call to external provider via the unified gateway or return classified failure if live creation is disabled.
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

        // In Production/Staging: STRICTLY require verified live API grant
        if (! config('procurement.v2_live_order_creation_enabled', false)) {
            return new ExternalOrderSubmissionFailed(
                errorCode: 'ORDER_CREATE_API_NOT_GRANTED',
                errorMessageMasked: 'Live external order creation is disabled pending verified Open Platform grant.',
                providerRequestId: null,
                retryClassification: 'non_retryable'
            );
        }

        $draft = $this->buildOrderDraft($spo, $correlationKey);

        return $this->orderGateway->submitUnpaid($draft);
    }

    /**
     * Build an ExternalOrderDraft from a SupplierPurchaseOrder.
     */
    protected function buildOrderDraft(SupplierPurchaseOrder $spo, string $correlationKey): ExternalOrderDraft
    {
        $items = [];
        foreach ($spo->items as $item) {
            $items[] = [
                'supplier_product_id' => (string) $item->supplier_product_id,
                'supplier_sku_id' => (string) $item->supplier_sku_id,
                'qty' => (int) $item->qty_ordered,
                'expected_unit_cost' => (float) $item->expected_unit_cost,
            ];
        }

        return new ExternalOrderDraft(
            supplierPurchaseOrderId: $spo->id,
            correlationKey: $correlationKey,
            items: $items,
            currencyCode: (string) ($spo->currency_code ?: 'USD'),
            providerAccountId: $spo->provider_account_id ? (int) $spo->provider_account_id : null
        );
    }
}
