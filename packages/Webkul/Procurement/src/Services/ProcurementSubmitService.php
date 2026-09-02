<?php

namespace Webkul\Procurement\Services;

use App\Models\AliExpressSetting;
use App\Services\AliExpress\AliExpressApiClient;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\Contracts\AliExpressAuthorizationContextResolver;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Exceptions\ExternalOrderSubmissionException;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
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
            $batch = ProcurementBatch::with(['supplierOrders.items'])->where('id', $batchId)->lockForUpdate()->firstOrFail();

            if (! in_array($batch->state, [
                ProcurementBatch::STATE_APPROVED,
                ProcurementBatch::STATE_READY_FOR_REVIEW,
                ProcurementBatch::STATE_EXCEPTION,
                ProcurementBatch::STATE_PARTIALLY_SUBMITTED,
            ], true)) {
                throw new DomainException("لا يمكن إرسال الدفعة في حالتها الحالية ({$batch->state}). يجب أن تكون معتمدة أولاً.");
            }

            if (strtoupper((string) $batch->currency_code) !== 'USD') {
                throw new DomainException("عملة الدفعة هي '{$batch->currency_code}'. النظام يقبل عملة USD فقط.");
            }

            // Identify unsubmitted SPOs in this batch
            $pendingSpos = $batch->supplierOrders->filter(function ($spo) {
                $hasLiveOrder = $spo->platformOrders()
                    ->whereNotNull('external_order_id')
                    ->where('external_order_id', '!=', '')
                    ->where('normalized_status', '!=', ExternalPlatformOrder::STATUS_SUBMISSION_FAILED)
                    ->exists();

                return ! $hasLiveOrder;
            });

            if ($pendingSpos->isEmpty()) {
                throw new DomainException('جميع أوامر الشراء في هذه الدفعة تم إرسالها مسبقاً إلى علي إكسبرس.');
            }

            // =========================================================================
            // PHASE 1: STRICT PREFLIGHT VALIDATION (All-or-Nothing Pre-Check)
            // =========================================================================
            $preflightErrors = [];
            $aeSetting = AliExpressSetting::current();
            $varType = $aeSetting->variance_product_type ?? 'percentage';
            $varLimit = (float) ($aeSetting->variance_product_limit ?? 10.0);

            foreach ($pendingSpos as $spo) {
                $storeName = $spo->supplier_store_name ?: "متجر #{$spo->supplier_store_id}";

                // 1. Currency check
                if (strtoupper((string) $spo->currency_code) !== 'USD') {
                    $preflightErrors[] = "أمر المورد [{$storeName} - {$spo->purchase_order_number}]: العملة غير متوافقة ({$spo->currency_code}).";

                    continue;
                }

                // 2. Preflight via API Gateway (Stock, Deliverability, Address)
                try {
                    $preflight = $this->preflightSupplierPurchaseOrder($spo->id);
                    if (! $preflight->isSuccess || ! $preflight->isDeliverableToDestination) {
                        $rawErr = $preflight->errorMessage ?: $preflight->errorCode ?: 'فشل التحقق من توفر الشحن أو المنتج لدى المورد';
                        $errMsg = AliExpressOrderSubmissionGateway::mapAliExpressErrorMessage((string) $preflight->errorCode, $rawErr);
                        $preflightErrors[] = "أمر المورد [{$storeName} - {$spo->purchase_order_number}]: {$errMsg}";

                        continue;
                    }
                } catch (\Throwable $e) {
                    $preflightErrors[] = "أمر المورد [{$storeName} - {$spo->purchase_order_number}]: ".$e->getMessage();

                    continue;
                }

                // 3. Live Cost Variance Guard
                foreach ($spo->items as $item) {
                    $expectedCost = (float) $item->expected_unit_cost;
                    if ($expectedCost <= 0) {
                        continue;
                    }

                    $liveCost = $this->fetchLiveSkuCost($spo, $item);
                    if ($liveCost !== null && $liveCost > 0) {
                        // Only flag cost variance if the supplier INCREASES the price above threshold.
                        // If the price decreased, it is savings/profit for the store!
                        if ($liveCost > $expectedCost) {
                            $varianceDelta = $liveCost - $expectedCost;
                            $variancePercent = ($varianceDelta / $expectedCost) * 100;
                            $isExceeded = $varType === 'fixed' ? ($varianceDelta > $varLimit) : ($variancePercent > $varLimit);

                            if ($isExceeded && $spo->state !== SupplierPurchaseOrder::STATE_READY_TO_SUBMIT) {
                                $limitDisplay = $varType === 'fixed' ? "\${$varLimit}" : "{$varLimit}%";
                                $preflightErrors[] = "أمر المورد [{$storeName} - {$spo->purchase_order_number}]: ارتفاع سعر الصنف (SKU: {$item->supplier_sku_id}) تجاوز حد التسامح المسموح ({$limitDisplay}). المتوقع: \${$expectedCost}، الحالي لدى المورد: \${$liveCost}.";
                            }
                        } elseif ($liveCost < $expectedCost) {
                            $item->update(['expected_unit_cost' => $liveCost]);
                        }
                    }
                }
            }

            // If ANY preflight check failed across the batch: HALT IMMEDIATELY WITH ZERO ORDERS SENT
            if (! empty($preflightErrors)) {
                $batch->update([
                    'state' => ProcurementBatch::STATE_EXCEPTION,
                ]);

                ProcurementAuditLog::create([
                    'auditable_type' => ProcurementBatch::class,
                    'auditable_id' => $batch->id,
                    'action' => 'batch_preflight_halted',
                    'actor_id' => $actorId,
                    'actor_type' => 'admin',
                    'old_state' => $batch->getOriginal('state') ?? ProcurementBatch::STATE_APPROVED,
                    'new_state' => ProcurementBatch::STATE_EXCEPTION,
                    'details' => [
                        'reasons' => $preflightErrors,
                        'pending_spos_count' => $pendingSpos->count(),
                        'orders_sent' => 0,
                    ],
                    'correlation_id' => "batch-{$batch->id}-preflight-halt",
                ]);

                $errList = implode(' | ', $preflightErrors);
                throw new DomainException("تعذر إرسال الدفعة لوجود تعثر في الفحص المسبق لأوامر الموردين: {$errList}. تم إيقاف الإرسال بالكامل لحماية الطلب — يمكنك إزالة أمر المورد المتعثر من الدفعة أو معالجته لإعادة الإرسال.");
            }

            // =========================================================================
            // PHASE 2: ATOMIC EXECUTION (Submit All Verified SPOs)
            // =========================================================================
            $batch->update([
                'state' => ProcurementBatch::STATE_SUBMITTED_TO_PROVIDER,
            ]);

            $submittedCount = 0;
            $failedCount = 0;
            $failedMessages = [];

            foreach ($pendingSpos as $spo) {
                try {
                    $submittedSpo = $this->submitSupplierPurchaseOrder($spo->id, $actorId);
                    $hasLiveOrder = $submittedSpo->platformOrders()
                        ->whereNotNull('external_order_id')
                        ->where('external_order_id', '!=', '')
                        ->where('normalized_status', '!=', ExternalPlatformOrder::STATUS_SUBMISSION_FAILED)
                        ->exists();

                    if ($hasLiveOrder) {
                        $submittedCount++;
                    } else {
                        $failedCount++;
                        $lastFail = $submittedSpo->platformOrders()->latest()->first();
                        $errMsg = $lastFail?->failure_message ?: $lastFail?->failure_code ?: 'تعذر إرسال الطلب للمورد';
                        $storeName = $spo->supplier_store_name ?: "متجر #{$spo->supplier_store_id}";
                        $failedMessages[] = "أمر المورد [{$storeName} - {$spo->purchase_order_number}]: {$errMsg}";
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $storeName = $spo->supplier_store_name ?: "متجر #{$spo->supplier_store_id}";
                    $failedMessages[] = "أمر المورد [{$storeName} - {$spo->purchase_order_number}]: ".$e->getMessage();
                }
            }

            $finalState = ($failedCount > 0)
                ? ($submittedCount > 0 ? ProcurementBatch::STATE_PARTIALLY_SUBMITTED : ProcurementBatch::STATE_EXCEPTION)
                : ProcurementBatch::STATE_AWAITING_MANUAL_PAYMENT;

            $batch->update([
                'state' => $finalState,
            ]);

            ProcurementAuditLog::create([
                'auditable_type' => ProcurementBatch::class,
                'auditable_id' => $batch->id,
                'action' => 'batch_submitted',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => ProcurementBatch::STATE_SUBMITTED_TO_PROVIDER,
                'new_state' => $finalState,
                'details' => [
                    'submitted_orders_count' => $submittedCount,
                    'failed_orders_count' => $failedCount,
                    'failed_messages' => $failedMessages,
                ],
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

            // 1b. Pre-Submit Cost Guard: verify live AliExpress prices match expected costs
            $aeSetting = AliExpressSetting::current();
            $varType = $aeSetting->variance_product_type ?? 'percentage';
            $varLimit = (float) ($aeSetting->variance_product_limit ?? 10.0);

            $spo->load('items');
            foreach ($spo->items as $item) {
                $expectedCost = (float) $item->expected_unit_cost;
                if ($expectedCost <= 0) {
                    continue;
                }

                $liveCost = $this->fetchLiveSkuCost($spo, $item);
                if ($liveCost !== null && $liveCost > 0) {
                    if ($liveCost > $expectedCost) {
                        $varianceDelta = $liveCost - $expectedCost;
                        $variancePercent = ($varianceDelta / $expectedCost) * 100;
                        $isExceeded = $varType === 'fixed' ? ($varianceDelta > $varLimit) : ($variancePercent > $varLimit);

                        // If the order was already approved by admin, do not re-halt on already-approved price
                        $alreadyApproved = in_array($spo->state, [
                            SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
                            SupplierPurchaseOrder::STATE_SUBMITTED,
                        ], true);

                        if ($isExceeded && ! $alreadyApproved) {
                            $varianceAmount = round($varianceDelta, 4);
                            DB::transaction(function () use ($spo, $varianceAmount, $actorId, $item, $expectedCost, $liveCost, $variancePercent, $varLimit, $varType) {
                                $spo->update([
                                    'state' => SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW,
                                    'cost_variance_amount' => $varianceAmount,
                                ]);

                                if ($spo->batch_id) {
                                    DB::table('procurement_batches')->where('id', $spo->batch_id)->update(['state' => ProcurementBatch::STATE_EXCEPTION]);
                                }

                                ProcurementAuditLog::create([
                                    'auditable_type' => SupplierPurchaseOrder::class,
                                    'auditable_id' => $spo->id,
                                    'action' => 'cost_variance_guard_triggered',
                                    'actor_id' => $actorId,
                                    'actor_type' => 'admin',
                                    'old_state' => $spo->getOriginal('state') ?? SupplierPurchaseOrder::STATE_DRAFT,
                                    'new_state' => SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW,
                                    'details' => [
                                        'item_id' => $item->id,
                                        'supplier_sku_id' => $item->supplier_sku_id,
                                        'expected_unit_cost' => $expectedCost,
                                        'live_unit_cost' => $liveCost,
                                        'variance_percent' => round($variancePercent, 2),
                                        'threshold_limit' => $varLimit,
                                        'threshold_type' => $varType,
                                    ],
                                    'correlation_id' => "spo-{$spo->id}-cost-guard",
                                ]);
                            });

                            $limitDisplay = $varType === 'fixed' ? "\${$varLimit}" : "{$varLimit}%";
                            throw new DomainException(
                                "تم تحويل أمر الشراء {$spo->purchase_order_number} إلى مراجعة فروقات التكلفة: ارتفاع السعر للصنف (SKU: {$item->supplier_sku_id}) تجاوز الحد المسموح ({$limitDisplay}). المتوقع: \${$expectedCost}، الحالي لدى المورد: \${$liveCost}. يرجى مراجعته وقبوله أو رفضه من صفحة (فروقات التكلفة)."
                            );
                        }
                    } elseif ($liveCost < $expectedCost) {
                        // Profit/Discount: quietly update expected_unit_cost to the lower price
                        $item->update(['expected_unit_cost' => $liveCost]);
                    }
                }
            }

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

            $metadata = $result->responseMetadata ?? [];
            $paymentDeadlineAt = null;
            $overTimeLeft = null;
            if (! empty($metadata['payment_deadline_at'])) {
                $paymentDeadlineAt = $metadata['payment_deadline_at'];
            } elseif (isset($metadata['over_time_left']) && is_numeric($metadata['over_time_left'])) {
                $overTimeLeft = (int) $metadata['over_time_left'];
                $paymentDeadlineAt = now()->addSeconds($overTimeLeft)->toIso8601String();
            } else {
                $defaultTimeout = (int) config('procurement.default_payment_timeout_seconds', 86400);
                $paymentDeadlineAt = now()->addSeconds($defaultTimeout)->toIso8601String();
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
                'payment_deadline_at' => $paymentDeadlineAt,
                'last_synced_at' => now(),
                'snapshots' => array_merge([
                    'created_via' => 'ProcurementSubmitService',
                    'submitted_at' => now()->toIso8601String(),
                    'expected_total' => (float) $spo->expected_total,
                    'payment_deadline_at' => $paymentDeadlineAt,
                    'over_time_left' => $overTimeLeft,
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
                'logistics_service_name' => 'CAINIAO_FULFILLMENT_STD',
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

    /**
     * Fetch live AliExpress unit cost for a specific SKU.
     * Best-effort: returns null if API call fails.
     */
    public function fetchLiveSkuCost(SupplierPurchaseOrder $spo, $item): ?float
    {
        try {
            /** @var AliExpressAuthorizationContextResolver $authResolver */
            $authResolver = app(AliExpressAuthorizationContextResolver::class);
            $auth = $authResolver->resolveForDropshipperSubmission(
                $spo->provider_account_id ? (string) $spo->provider_account_id : null
            );

            $result = app(AliExpressApiClient::class)->call('aliexpress.ds.product.get', $auth->accessToken, [
                'product_id' => (string) $item->supplier_product_id,
                'ship_to_country' => 'SA',
                'target_currency' => 'USD',
                'target_language' => 'en',
            ]);

            if (! ($result['ok'] ?? false)) {
                return null;
            }

            $body = $result['body'] ?? [];
            $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
            $skus = $resp['result']['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
            if (isset($skus['sku_id'])) {
                $skus = [$skus];
            }

            foreach ($skus as $sku) {
                if ((string) ($sku['sku_id'] ?? '') === (string) $item->supplier_sku_id) {
                    return (float) ($sku['offer_sale_price'] ?? $sku['sku_price'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ProcurementSubmitService: Cost guard API lookup failed', [
                'spo_id' => $spo->id,
                'sku_id' => $item->supplier_sku_id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
