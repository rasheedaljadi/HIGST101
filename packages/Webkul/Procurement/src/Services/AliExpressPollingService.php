<?php

namespace Webkul\Procurement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;

class AliExpressPollingService
{
    /**
     * State rank map to guarantee monotonic state progression.
     */
    protected array $statusRanks = [
        'wait_buyer_pay' => 10,
        'payment_confirmed' => 20,
        'processing' => 20,
        'shipped' => 30,
        'completed' => 40,
        'cancelled' => 50,
    ];

    public function __construct(
        protected ?AliExpressOrderGateway $orderGateway = null
    ) {
        $this->orderGateway ??= app(AliExpressOrderGateway::class);
    }

    /**
     * Poll a specific platform order with idempotent payload processing and monotonic state protection.
     * If $payload is null, performs a live authoritative query via AliExpressOrderGateway.
     *
     * @param  array{
     *     status: string,
     *     actual_total?: float,
     *     tracking_number?: string,
     *     carrier?: string,
     *     end_reason?: string,
     *     end_reason_desc?: string,
     *     provider_updated_at?: string
     * }|null  $payload
     */
    public function syncOrder(ExternalPlatformOrder $platformOrder, ?array $payload = null): ExternalPlatformOrder
    {
        if ($payload === null) {
            $payload = $this->fetchLiveOrderPayload($platformOrder);
        }

        return DB::transaction(function () use ($platformOrder, $payload) {
            /** @var ExternalPlatformOrder $platformOrder */
            $platformOrder = ExternalPlatformOrder::where('id', $platformOrder->id)->lockForUpdate()->firstOrFail();

            if (empty($platformOrder->external_order_id)) {
                throw new \DomainException("Cannot sync ExternalPlatformOrder #{$platformOrder->id} without an authoritative external_order_id.");
            }

            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $platformOrder->supplier_purchase_order_id)->lockForUpdate()->firstOrFail();

            $rawStatus = strtoupper((string) ($payload['status'] ?? 'WAIT_SELLER_SEND_GOODS'));
            $endReason = strtoupper((string) ($payload['end_reason'] ?? ''));
            $endReasonDesc = (string) ($payload['end_reason_desc'] ?? '');

            $normalizedStatus = $this->resolveNormalizedStatus($rawStatus, $endReason, $endReasonDesc);

            $currentRank = $this->statusRanks[$platformOrder->normalized_status] ?? 0;
            $newRank = $this->statusRanks[$normalizedStatus] ?? 0;

            // Enforce Monotonic Invariant: Never regress state on out-of-order polling response
            if ($newRank < $currentRank && $normalizedStatus !== ExternalPlatformOrder::STATUS_CANCELLED) {
                Log::warning("[Procurement Polling] Stale/out-of-order payload skipped for ExternalOrder #{$platformOrder->external_order_id}. Current rank {$currentRank} > incoming {$newRank}");

                return $platformOrder;
            }

            $actualTotal = isset($payload['actual_total']) ? (float) $payload['actual_total'] : (float) $spo->expected_total;
            $trackingNumber = $payload['tracking_number'] ?? $platformOrder->tracking_number;
            $carrier = $payload['carrier'] ?? $platformOrder->carrier_name;

            $platformOrder->update([
                'raw_status' => $rawStatus,
                'normalized_status' => $normalizedStatus,
                'tracking_number' => $trackingNumber,
                'carrier_name' => $carrier,
                'last_synced_at' => now(),
            ]);

            // Handle Transitions for SupplierPurchaseOrder
            if ($normalizedStatus === ExternalPlatformOrder::STATUS_PROCESSING) {
                // Check Cost Variance
                $costVariance = round($actualTotal - (float) $spo->expected_total, 4);

                // Create actual cost snapshot if not already present
                $hasActualSnapshot = ProcurementCostSnapshot::where('snapshotable_type', SupplierPurchaseOrder::class)
                    ->where('snapshotable_id', $spo->id)
                    ->where('snapshot_type', ProcurementCostSnapshot::TYPE_ACTUAL_AFTER_MANUAL_PAYMENT)
                    ->exists();

                if (! $hasActualSnapshot) {
                    ProcurementCostSnapshot::create([
                        'snapshotable_type' => SupplierPurchaseOrder::class,
                        'snapshotable_id' => $spo->id,
                        'snapshot_type' => ProcurementCostSnapshot::TYPE_ACTUAL_AFTER_MANUAL_PAYMENT,
                        'items_subtotal' => $actualTotal,
                        'shipping_amount' => 0.0000,
                        'discount_amount' => 0.0000,
                        'tax_fee_amount' => 0.0000,
                        'total_amount' => $actualTotal,
                        'currency_code' => 'USD',
                        'exchange_rate' => 1.000000,
                        'allocation_basis' => 'proportionate_subtotal',
                        'breakdown' => [
                            'actual_total' => $actualTotal,
                            'expected_total' => (float) $spo->expected_total,
                            'variance' => $costVariance,
                        ],
                        'external_reference' => $platformOrder->external_order_id,
                        'actor_id' => null,
                        'actor_type' => 'system_polling',
                        'correlation_id' => "spo-{$spo->id}-actual-cost",
                        'snapshot_hash' => hash('sha256', "actual-cost-{$spo->id}-{$actualTotal}"),
                        'created_at' => now(),
                    ]);
                }

                if (abs($costVariance) > 0.001) {
                    $spo->update([
                        'actual_total' => $actualTotal,
                        'cost_variance_amount' => $costVariance,
                        'state' => SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW,
                        'payment_state' => 'variance_under_review',
                        'external_sync_state' => 'supplier_processing_variance_review',
                    ]);

                    if ($spo->batch) {
                        $spo->batch->update([
                            'state' => ProcurementBatch::STATE_COST_VARIANCE_REVIEW,
                            'actual_total_cost' => $actualTotal,
                            'cost_variance_amount' => $costVariance,
                        ]);
                    }

                    ProcurementAuditLog::create([
                        'auditable_type' => SupplierPurchaseOrder::class,
                        'auditable_id' => $spo->id,
                        'action' => 'cost_variance_detected',
                        'old_state' => $spo->getOriginal('state'),
                        'new_state' => SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW,
                        'details' => [
                            'expected_total' => $spo->expected_total,
                            'actual_total' => $actualTotal,
                            'variance' => $costVariance,
                        ],
                        'correlation_id' => "spo-{$spo->id}-variance",
                    ]);
                } else {
                    $spo->update([
                        'actual_total' => $actualTotal,
                        'cost_variance_amount' => 0.0000,
                        'state' => SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING,
                        'payment_state' => 'paid_externally',
                        'external_sync_state' => 'supplier_processing',
                    ]);

                    if ($spo->batch) {
                        $spo->batch->update([
                            'state' => ProcurementBatch::STATE_SUPPLIER_PROCESSING,
                            'actual_total_cost' => $actualTotal,
                        ]);
                    }
                }
            } elseif ($normalizedStatus === ExternalPlatformOrder::STATUS_SHIPPED) {
                // If in variance review, maintain variance status until approved, but log shipping info
                if ($spo->state !== SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW) {
                    $spo->update([
                        'state' => SupplierPurchaseOrder::STATE_SUPPLIER_SHIPPED,
                        'external_sync_state' => 'supplier_shipped',
                    ]);
                }
            } elseif ($normalizedStatus === ExternalPlatformOrder::STATUS_COMPLETED) {
                $spo->update([
                    'state' => SupplierPurchaseOrder::STATE_CLOSED,
                    'external_sync_state' => 'completed',
                ]);
            } elseif ($normalizedStatus === ExternalPlatformOrder::STATUS_CANCELLED) {
                $spo->update([
                    'state' => SupplierPurchaseOrder::STATE_CANCELLED,
                    'payment_state' => 'cancelled',
                    'external_sync_state' => 'cancelled',
                ]);

                // Update batch if all SPOs are cancelled/closed
                if ($spo->batch) {
                    $hasActiveSpos = SupplierPurchaseOrder::where('batch_id', $spo->batch_id)
                        ->whereNotIn('state', [
                            SupplierPurchaseOrder::STATE_CANCELLED,
                            SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                            SupplierPurchaseOrder::STATE_CLOSED,
                        ])
                        ->exists();

                    if (! $hasActiveSpos) {
                        $spo->batch->update([
                            'state' => ProcurementBatch::STATE_CANCELLED,
                        ]);
                    }
                }

                // Release demand allocations and restore customer demands to open pool
                if ($spo->items) {
                    foreach ($spo->items as $item) {
                        $allocations = ProcurementDemandAllocation::where('supplier_purchase_order_item_id', $item->id)->get();

                        foreach ($allocations as $allocation) {
                            $releasedQty = (int) ($allocation->qty_allocated > 0 ? $allocation->qty_allocated : $allocation->qty_cancelled);

                            $allocation->update([
                                'state' => ProcurementDemandAllocation::STATE_CANCELLED,
                                'qty_cancelled' => $releasedQty,
                                'qty_allocated' => 0,
                            ]);

                            $demand = $allocation->demand;
                            if ($demand) {
                                $newQtyBatched = max(0, $demand->qty_batched - $releasedQty);
                                $demand->update([
                                    'qty_batched' => $newQtyBatched,
                                    'state' => $newQtyBatched == 0 ? ProcurementDemand::STATE_OPEN_FOR_BATCHING : $demand->state,
                                ]);
                            }
                        }
                    }
                }

                ProcurementAuditLog::create([
                    'auditable_type' => SupplierPurchaseOrder::class,
                    'auditable_id' => $spo->id,
                    'action' => 'supplier_order_cancelled_externally',
                    'old_state' => $spo->getOriginal('state'),
                    'new_state' => SupplierPurchaseOrder::STATE_CANCELLED,
                    'details' => [
                        'external_order_id' => $platformOrder->external_order_id,
                        'raw_status' => $rawStatus,
                        'end_reason' => $endReason,
                        'end_reason_desc' => $endReasonDesc,
                    ],
                    'correlation_id' => "spo-{$spo->id}-cancel",
                ]);
            }

            return $platformOrder->fresh();
        });
    }

    /**
     * Fetch live order payload from AliExpress API via AliExpressOrderGateway.
     *
     * @return array{
     *     status: string,
     *     tracking_number: ?string,
     *     carrier: ?string,
     *     actual_total: ?float,
     *     end_reason: string,
     *     end_reason_desc: string,
     *     provider_updated_at: string
     * }
     */
    protected function fetchLiveOrderPayload(ExternalPlatformOrder $platformOrder): array
    {
        $snapshot = $this->orderGateway->getOrder(
            $platformOrder->external_order_id,
            $platformOrder->provider_account_id
        );

        if (in_array($snapshot->orderStatus, ['AUTH_UNAVAILABLE', 'QUERY_FAILED', 'TRANSPORT_ERROR', 'INVALID_EXTERNAL_ORDER_ID'], true)) {
            throw new \RuntimeException("AliExpress query failed for order #{$platformOrder->external_order_id}: {$snapshot->rawStatus}");
        }

        $endReason = '';
        $endReasonDesc = '';
        $resp = $snapshot->rawResponse['aliexpress_trade_ds_order_get_response']['result']
            ?? $snapshot->rawResponse['result']
            ?? $snapshot->rawResponse;

        $childList = $resp['child_order_list']['aeop_child_order_info'] ?? [];
        if (! empty($childList)) {
            $firstChild = is_array($childList) ? ($childList[0] ?? $childList) : [];
            $endReason = (string) ($firstChild['end_reason'] ?? '');
            $endReasonDesc = (string) ($firstChild['end_reason_desc'] ?? '');
        }

        $actualTotal = null;
        if (isset($resp['order_amount']['amount'])) {
            $actualTotal = (float) $resp['order_amount']['amount'];
        }

        return [
            'status' => $snapshot->orderStatus,
            'tracking_number' => $snapshot->trackingNumber,
            'carrier' => $snapshot->carrierName,
            'actual_total' => $actualTotal,
            'end_reason' => $endReason,
            'end_reason_desc' => $endReasonDesc,
            'provider_updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Resolve normalized status taking into account AliExpress order_status and end_reason.
     */
    public function resolveNormalizedStatus(string $rawStatus, string $endReason = '', string $endReasonDesc = ''): string
    {
        $cancelIndicators = [
            'PAYMENT_TIMEOUT',
            'PAYMENT_TIMEOUT_BUYER',
            'BUYER_CANCEL_ORDER',
            'BUYER_CANCELLED',
            'BUYER_CANCEL',
            'BUYER_NOT_PAY',
            'SELLER_CANCEL',
            'RISK_CONTROL_CANCEL',
            'CANCEL',
            'CLOSED',
            'IN_CANCEL',
            'CANCELLED',
        ];

        foreach ($cancelIndicators as $indicator) {
            if (str_contains($endReason, $indicator) || str_contains($rawStatus, $indicator)) {
                return ExternalPlatformOrder::STATUS_CANCELLED;
            }
        }

        if (! empty($endReasonDesc) && (
            stripos($endReasonDesc, 'No payment') !== false ||
            stripos($endReasonDesc, 'cancel') !== false ||
            stripos($endReasonDesc, 'closed') !== false
        )) {
            return ExternalPlatformOrder::STATUS_CANCELLED;
        }

        return match ($rawStatus) {
            'WAIT_BUYER_PAY' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
            'WAIT_SELLER_SEND_GOODS', 'PROCESSING' => ExternalPlatformOrder::STATUS_PROCESSING,
            'SELLER_SEND_GOODS', 'SHIPPED', 'WAIT_RECEIVE' => ExternalPlatformOrder::STATUS_SHIPPED,
            'FINISH', 'COMPLETED' => ExternalPlatformOrder::STATUS_COMPLETED,
            'IN_CANCEL', 'CANCELLED', 'CLOSED' => ExternalPlatformOrder::STATUS_CANCELLED,
            default => ExternalPlatformOrder::STATUS_PROCESSING,
        };
    }
}
