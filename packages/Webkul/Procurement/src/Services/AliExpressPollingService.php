<?php

namespace Webkul\Procurement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
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

    /**
     * Poll a specific platform order with idempotent payload processing and monotonic state protection.
     *
     * @param  array{
     *     status: string,
     *     actual_total?: float,
     *     tracking_number?: string,
     *     carrier?: string,
     *     provider_updated_at?: string
     * }|null  $payload
     */
    public function syncOrder(ExternalPlatformOrder $platformOrder, ?array $payload = null): ExternalPlatformOrder
    {
        return DB::transaction(function () use ($platformOrder, $payload) {
            /** @var ExternalPlatformOrder $platformOrder */
            $platformOrder = ExternalPlatformOrder::where('id', $platformOrder->id)->lockForUpdate()->firstOrFail();
            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $platformOrder->supplier_purchase_order_id)->lockForUpdate()->firstOrFail();

            $rawStatus = strtoupper((string) ($payload['status'] ?? 'WAIT_SELLER_SEND_GOODS'));
            $normalizedStatus = match ($rawStatus) {
                'WAIT_BUYER_PAY' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
                'WAIT_SELLER_SEND_GOODS', 'PROCESSING' => ExternalPlatformOrder::STATUS_PROCESSING,
                'SELLER_SEND_GOODS', 'SHIPPED', 'WAIT_RECEIVE' => ExternalPlatformOrder::STATUS_SHIPPED,
                'FINISH', 'COMPLETED' => ExternalPlatformOrder::STATUS_COMPLETED,
                'IN_CANCEL', 'CANCELLED', 'CLOSED' => ExternalPlatformOrder::STATUS_CANCELLED,
                default => ExternalPlatformOrder::STATUS_PROCESSING,
            };

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
                    'external_sync_state' => 'cancelled',
                ]);
            }

            return $platformOrder->fresh();
        });
    }
}
