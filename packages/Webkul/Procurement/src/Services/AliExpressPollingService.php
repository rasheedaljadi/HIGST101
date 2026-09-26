<?php

namespace Webkul\Procurement\Services;

use App\Models\AliExpressSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementCostSnapshot;
use Webkul\Procurement\Models\ProcurementDemand;
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
            $logisticsStatus = strtoupper((string) ($payload['logistics_status'] ?? ''));
            $trackingNumber = $payload['tracking_number'] ?? $platformOrder->tracking_number;

            $normalizedStatus = $this->resolveNormalizedStatus(
                $rawStatus,
                $endReason,
                $endReasonDesc,
                $logisticsStatus,
                $trackingNumber
            );

            // If order or SPO is already cancelled, enforce cancellation invariant UNLESS live provider proves delivery/completion
            if ($platformOrder->normalized_status === ExternalPlatformOrder::STATUS_CANCELLED || ($spo && $spo->state === SupplierPurchaseOrder::STATE_CANCELLED)) {
                if ($normalizedStatus === ExternalPlatformOrder::STATUS_COMPLETED || $logisticsStatus === 'BUYER_ACCEPT_GOODS') {
                    Log::info("[Procurement Polling] Recovering erroneously cancelled order #{$platformOrder->external_order_id} to completed because provider confirmed delivery.");
                } else {
                    $normalizedStatus = ExternalPlatformOrder::STATUS_CANCELLED;
                }
            }

            $currentRank = $this->statusRanks[$platformOrder->normalized_status] ?? 0;
            $newRank = $this->statusRanks[$normalizedStatus] ?? 0;

            // Enforce Monotonic Invariant: Never regress state on out-of-order polling response unless correcting initial draft/placement status or recovering from erroneous cancellation
            $isErroneousCancellationRecovery = ($platformOrder->normalized_status === ExternalPlatformOrder::STATUS_CANCELLED && ($normalizedStatus === ExternalPlatformOrder::STATUS_COMPLETED || $logisticsStatus === 'BUYER_ACCEPT_GOODS'));

            if ($newRank < $currentRank && ! $isErroneousCancellationRecovery && $normalizedStatus !== ExternalPlatformOrder::STATUS_CANCELLED && ! in_array($rawStatus, ['PLACE_ORDER_SUCCESS', 'WAIT_BUYER_PAY'], true)) {
                Log::warning("[Procurement Polling] Stale/out-of-order payload skipped for ExternalOrder #{$platformOrder->external_order_id}. Current rank {$currentRank} > incoming {$newRank}");

                return $platformOrder;
            }

            $actualTotal = isset($payload['actual_total']) ? (float) $payload['actual_total'] : (float) $spo->expected_total;
            $carrier = $payload['carrier'] ?? $platformOrder->carrier_name;

            $updateData = [
                'raw_status' => $rawStatus,
                'normalized_status' => $normalizedStatus,
                'tracking_number' => $trackingNumber,
                'carrier_name' => $carrier,
                'last_synced_at' => now(),
            ];

            if (! empty($payload['payment_deadline_at'])) {
                $updateData['payment_deadline_at'] = $payload['payment_deadline_at'];
            }

            $snapshots = is_array($platformOrder->snapshots) ? $platformOrder->snapshots : (json_decode($platformOrder->snapshots ?? '[]', true) ?: []);
            if (isset($payload['actual_total'])) {
                $snapshots['order_amount'] = (float) $payload['actual_total'];
            }
            if (! empty($payload['payment_deadline_at'])) {
                $snapshots['payment_deadline_at'] = $payload['payment_deadline_at'];
            }
            if (isset($payload['over_time_left'])) {
                $snapshots['over_time_left'] = $payload['over_time_left'];
            }
            $updateData['snapshots'] = $snapshots;

            $platformOrder->update($updateData);

            $siblingOrders = ExternalPlatformOrder::where('supplier_purchase_order_id', $spo->id)->get();
            $isMultiOrder = $siblingOrders->count() > 1;

            if ($isMultiOrder) {
                $totalActualForSpo = 0.0;
                $allOrdersPaidOrFinished = true;
                $hasAnyProcessing = false;
                $allOrdersShippedOrDone = true;
                $allOrdersClosed = true;
                $allOrdersCancelled = true;

                foreach ($siblingOrders as $sOrder) {
                    $sStatus = $sOrder->normalized_status;
                    $sSnap = is_array($sOrder->snapshots) ? $sOrder->snapshots : json_decode($sOrder->snapshots ?? '[]', true);
                    $sAmt = (float) ($sSnap['order_amount'] ?? $sSnap['expected_total'] ?? 0.0);
                    if ($sAmt <= 0) {
                        $sAmt = (float) $sOrder->items()->sum('actual_item_amount');
                    }
                    $totalActualForSpo += $sAmt;

                    if ($sStatus === ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY) {
                        $allOrdersPaidOrFinished = false;
                        $allOrdersShippedOrDone = false;
                        $allOrdersClosed = false;
                        $allOrdersCancelled = false;
                    } elseif (in_array($sStatus, [ExternalPlatformOrder::STATUS_PROCESSING, ExternalPlatformOrder::STATUS_PAYMENT_CONFIRMED], true)) {
                        $hasAnyProcessing = true;
                        $allOrdersShippedOrDone = false;
                        $allOrdersClosed = false;
                        $allOrdersCancelled = false;
                    } elseif (in_array($sStatus, [ExternalPlatformOrder::STATUS_SHIPPED], true)) {
                        $hasAnyProcessing = true;
                        $allOrdersClosed = false;
                        $allOrdersCancelled = false;
                    } elseif (in_array($sStatus, [ExternalPlatformOrder::STATUS_COMPLETED, ExternalPlatformOrder::STATUS_DELIVERED], true)) {
                        $allOrdersCancelled = false;
                    } elseif ($sStatus === ExternalPlatformOrder::STATUS_CANCELLED) {
                        // Sub-order cancelled
                    } else {
                        $allOrdersClosed = false;
                    }
                }

                // If this specific platform order cancelled and not all cancelled:
                if ($normalizedStatus === ExternalPlatformOrder::STATUS_CANCELLED && ! $allOrdersCancelled) {
                    foreach ($platformOrder->items as $pItem) {
                        $allocations = ProcurementDemandAllocation::where('supplier_purchase_order_item_id', $pItem->supplier_purchase_order_item_id)->get();
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

                    ProcurementAuditLog::create([
                        'auditable_type' => SupplierPurchaseOrder::class,
                        'auditable_id' => $spo->id,
                        'action' => 'sub_order_cancelled_externally',
                        'old_state' => $spo->state,
                        'new_state' => $spo->state,
                        'details' => [
                            'external_order_id' => $platformOrder->external_order_id,
                            'raw_status' => $rawStatus,
                            'end_reason' => $endReason,
                            'end_reason_desc' => $endReasonDesc,
                        ],
                        'correlation_id' => "spo-{$spo->id}-sub-cancel",
                    ]);

                    return $platformOrder->fresh();
                }

                // If ALL cancelled
                if ($allOrdersCancelled) {
                    $spo->update([
                        'state' => SupplierPurchaseOrder::STATE_CANCELLED,
                        'payment_state' => 'cancelled',
                        'external_sync_state' => 'cancelled',
                    ]);

                    if ($spo->batch) {
                        $hasActiveSpos = SupplierPurchaseOrder::where('batch_id', $spo->batch_id)
                            ->whereNotIn('state', [
                                SupplierPurchaseOrder::STATE_CANCELLED,
                                SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                                SupplierPurchaseOrder::STATE_CLOSED,
                            ])
                            ->exists();

                        if (! $hasActiveSpos) {
                            $spo->batch->update(['state' => ProcurementBatch::STATE_CANCELLED]);
                        }
                    }

                    // Release all allocations
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

                    return $platformOrder->fresh();
                }

                // Update SPO state based on rollup
                if ($allOrdersClosed) {
                    $spo->update([
                        'actual_total' => $totalActualForSpo,
                        'state' => SupplierPurchaseOrder::STATE_CLOSED,
                        'external_sync_state' => 'completed',
                    ]);
                } elseif ($allOrdersShippedOrDone) {
                    if ($spo->state !== SupplierPurchaseOrder::STATE_COST_VARIANCE_REVIEW) {
                        $spo->update([
                            'actual_total' => $totalActualForSpo,
                            'state' => SupplierPurchaseOrder::STATE_SUPPLIER_SHIPPED,
                            'external_sync_state' => 'supplier_shipped',
                        ]);
                    }
                } elseif ($hasAnyProcessing) {
                    if (in_array($spo->state, [SupplierPurchaseOrder::STATE_DRAFT, SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT, SupplierPurchaseOrder::STATE_READY_TO_SUBMIT], true)) {
                        $spo->update([
                            'state' => SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING,
                            'payment_state' => 'paid_externally',
                            'external_sync_state' => 'supplier_processing',
                        ]);
                    }
                    if ($allOrdersPaidOrFinished && $totalActualForSpo > 0) {
                        $spo->update(['actual_total' => $totalActualForSpo]);
                    }
                }

                return $platformOrder->fresh();
            }

            // Handle Transitions for SupplierPurchaseOrder (Single-Order)
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

                $expectedTotal = (float) $spo->expected_total;
                $costVariance = round($actualTotal - $expectedTotal, 4);

                $aeSetting = AliExpressSetting::current();
                $autoApprove = (bool) ($aeSetting->variance_auto_approve ?? true);
                $profitGuardEnabled = (bool) ($aeSetting->variance_profit_guard_enabled ?? true);
                $minProfitMargin = (float) ($aeSetting->variance_min_profit_margin ?? 5.0);

                // Calculate allowed threshold based on settings
                $shippingLimitType = $aeSetting->variance_shipping_type ?? 'percentage';
                $shippingLimitValue = (float) ($aeSetting->variance_shipping_limit ?? 15.0);
                $productLimitType = $aeSetting->variance_product_type ?? 'percentage';
                $productLimitValue = (float) ($aeSetting->variance_product_limit ?? 10.0);

                $isWithinTolerance = false;
                if ($costVariance <= 0.001) {
                    $isWithinTolerance = true;
                } else {
                    $maxAllowedDelta = 0.0;
                    if ($shippingLimitType === 'fixed' || $productLimitType === 'fixed') {
                        $maxAllowedDelta = max(
                            $shippingLimitType === 'fixed' ? $shippingLimitValue : 0,
                            $productLimitType === 'fixed' ? $productLimitValue : 0
                        );
                    }
                    if ($expectedTotal > 0) {
                        $maxPercent = max(
                            $shippingLimitType === 'percentage' ? $shippingLimitValue : 0,
                            $productLimitType === 'percentage' ? $productLimitValue : 0
                        );
                        $percentDelta = ($expectedTotal * $maxPercent) / 100.0;
                        $maxAllowedDelta = max($maxAllowedDelta, $percentDelta);
                    }

                    $isWithinTolerance = ($costVariance <= $maxAllowedDelta);
                }

                // Check profit margin safe guard if applicable
                $isProfitProtected = false;
                if (! $isWithinTolerance && $profitGuardEnabled && $actualTotal > 0) {
                    $spo->load('items.allocations.procurementDemand');
                    $customerSellingRevenue = 0.0;
                    foreach ($spo->items as $spoItem) {
                        foreach ($spoItem->allocations as $alloc) {
                            $orderItem = DB::table('order_items')
                                ->where('id', $alloc->procurementDemand->order_item_id ?? 0)
                                ->first();
                            if ($orderItem) {
                                $customerSellingRevenue += ((float) $orderItem->price * (int) $alloc->qty_allocated);
                            }
                        }
                    }
                    if ($customerSellingRevenue > 0) {
                        $effectiveProfitMargin = (($customerSellingRevenue - $actualTotal) / $customerSellingRevenue) * 100;
                        if ($effectiveProfitMargin >= $minProfitMargin) {
                            $isProfitProtected = true;
                        }
                    }
                }

                $shouldAutoPass = $autoApprove && ($isWithinTolerance || $isProfitProtected);

                if (! $shouldAutoPass && abs($costVariance) > 0.001) {
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
                            'reason' => 'Exceeded tolerance limit and breached minimum profit safeguard',
                        ],
                        'correlation_id' => "spo-{$spo->id}-variance",
                    ]);
                } else {
                    $spo->update([
                        'actual_total' => $actualTotal,
                        'cost_variance_amount' => $costVariance,
                        'state' => SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING,
                        'payment_state' => 'paid_externally',
                        'external_sync_state' => 'supplier_processing',
                    ]);

                    if ($spo->batch) {
                        $spo->batch->update([
                            'state' => ProcurementBatch::STATE_SUPPLIER_PROCESSING,
                            'actual_total_cost' => $actualTotal,
                            'cost_variance_amount' => $costVariance,
                        ]);
                    }

                    if (abs($costVariance) > 0.001) {
                        ProcurementAuditLog::create([
                            'auditable_type' => SupplierPurchaseOrder::class,
                            'auditable_id' => $spo->id,
                            'action' => $isWithinTolerance ? 'cost_variance_auto_approved_within_tolerance' : 'cost_variance_auto_approved_by_profit_guard',
                            'old_state' => $spo->getOriginal('state'),
                            'new_state' => SupplierPurchaseOrder::STATE_SUPPLIER_PROCESSING,
                            'details' => [
                                'expected_total' => $expectedTotal,
                                'actual_total' => $actualTotal,
                                'variance' => $costVariance,
                                'reason' => $isWithinTolerance ? 'Within configured variance tolerance' : 'Protected by profit margin safe-guard',
                            ],
                            'correlation_id' => "spo-{$spo->id}-auto-variance",
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
                $spoUpdate = [
                    'state' => SupplierPurchaseOrder::STATE_CLOSED,
                    'external_sync_state' => 'completed',
                ];
                if ($actualTotal > 0) {
                    $spoUpdate['actual_total'] = $actualTotal;
                }
                if (! empty($trackingNumber)) {
                    $spoUpdate['tracking_number'] = $trackingNumber;
                }
                if ($spo->payment_state === 'cancelled') {
                    $spoUpdate['payment_state'] = 'paid_externally';
                }
                $spo->update($spoUpdate);

                // If recovering from erroneously cancelled state, restore demand allocations
                if ($spo->items) {
                    foreach ($spo->items as $item) {
                        $allocations = ProcurementDemandAllocation::where('supplier_purchase_order_item_id', $item->id)->get();
                        foreach ($allocations as $allocation) {
                            if ($allocation->state === ProcurementDemandAllocation::STATE_CANCELLED) {
                                $restoredQty = (int) ($allocation->qty_cancelled > 0 ? $allocation->qty_cancelled : 1);
                                $allocation->update([
                                    'state' => ProcurementDemandAllocation::STATE_ORDERED,
                                    'qty_allocated' => $restoredQty,
                                    'qty_cancelled' => 0,
                                ]);
                                $demand = $allocation->demand;
                                if ($demand) {
                                    $demand->update([
                                        'state' => ProcurementDemand::STATE_ORDERED,
                                        'qty_batched' => max((int) $demand->qty_batched, $restoredQty),
                                    ]);
                                }
                            }
                        }
                    }
                }
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

        $resp = $snapshot->rawResponse['aliexpress_trade_ds_order_get_response']['result']
            ?? $snapshot->rawResponse['result']
            ?? $snapshot->rawResponse;

        $logisticsStatus = (string) ($resp['logistics_status'] ?? '');

        $endReason = (string) ($resp['order_end_reason'] ?? $resp['end_reason'] ?? '');
        $endReasonDesc = (string) ($resp['order_end_reason_desc'] ?? $resp['end_reason_desc'] ?? '');
        $childList = $resp['child_order_list']['aeop_child_order_info'] ?? [];
        if (! empty($childList)) {
            $firstChild = is_array($childList) ? ($childList[0] ?? $childList) : [];
            if (empty($endReason)) {
                $endReason = (string) ($firstChild['end_reason'] ?? '');
            }
            if (empty($endReasonDesc)) {
                $endReasonDesc = (string) ($firstChild['end_reason_desc'] ?? '');
            }
        }

        $actualTotal = null;
        if (isset($resp['order_amount']['amount'])) {
            $actualTotal = (float) $resp['order_amount']['amount'];
        }

        $overTimeLeft = $snapshot->overTimeLeft;
        $paymentDeadlineAt = $snapshot->paymentDeadlineAt;

        if ($paymentDeadlineAt === null) {
            if (isset($resp['pay_timeout_second']) && is_numeric($resp['pay_timeout_second'])) {
                $payTimeoutSecond = (int) $resp['pay_timeout_second'];
                $baseTime = ! empty($resp['gmt_create'])
                    ? Carbon::parse($resp['gmt_create'], 'Asia/Shanghai')->setTimezone(config('app.timezone'))
                    : now();
                $deadlineCarbon = $baseTime->copy()->addSeconds($payTimeoutSecond);
                $paymentDeadlineAt = $deadlineCarbon->toIso8601String();
                if ($overTimeLeft === null) {
                    $overTimeLeft = max(0, (int) now()->diffInSeconds($deadlineCarbon, false));
                }
            } elseif (isset($resp['over_time_left']) && is_numeric($resp['over_time_left'])) {
                $overTimeLeft = (int) $resp['over_time_left'];
                $paymentDeadlineAt = now()->addSeconds($overTimeLeft)->toIso8601String();
            } elseif (isset($resp['left_time']) && is_numeric($resp['left_time'])) {
                $overTimeLeft = (int) $resp['left_time'];
                $paymentDeadlineAt = now()->addSeconds($overTimeLeft)->toIso8601String();
            } elseif (! empty($resp['expire_time'])) {
                $paymentDeadlineAt = Carbon::parse($resp['expire_time'], 'Asia/Shanghai')->setTimezone(config('app.timezone'))->toIso8601String();
            } elseif (! empty($resp['gmt_pay_deadline'])) {
                $paymentDeadlineAt = Carbon::parse($resp['gmt_pay_deadline'], 'Asia/Shanghai')->setTimezone(config('app.timezone'))->toIso8601String();
            }
        }

        return [
            'status' => $snapshot->orderStatus,
            'tracking_number' => $snapshot->trackingNumber,
            'carrier' => $snapshot->carrierName,
            'actual_total' => $actualTotal,
            'end_reason' => $endReason,
            'end_reason_desc' => $endReasonDesc,
            'logistics_status' => $logisticsStatus,
            'over_time_left' => $overTimeLeft,
            'payment_deadline_at' => $paymentDeadlineAt,
            'provider_updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Resolve normalized status taking into account AliExpress order_status, logistics_status, tracking, and end_reason.
     */
    public function resolveNormalizedStatus(
        string $rawStatus,
        string $endReason = '',
        string $endReasonDesc = '',
        string $logisticsStatus = '',
        ?string $trackingNumber = null
    ): string {
        $rawUpper = strtoupper(trim($rawStatus));
        $logisticsUpper = strtoupper(trim($logisticsStatus));
        $endReasonUpper = strtoupper(trim($endReason));

        // 1. Definite Delivery & Acceptance: Buyer confirmed receipt of goods
        if ($logisticsUpper === 'BUYER_ACCEPT_GOODS') {
            return ExternalPlatformOrder::STATUS_COMPLETED;
        }

        // 2. Finished on platform with delivered/shipped goods
        if (in_array($rawUpper, ['FINISH', 'COMPLETED'], true)) {
            if (in_array($logisticsUpper, ['BUYER_ACCEPT_GOODS', 'WAIT_BUYER_ACCEPT_GOODS'], true)) {
                return ExternalPlatformOrder::STATUS_COMPLETED;
            }

            // If tracking number exists and goods were shipped, and root end reason is not an unpaid/pre-shipment timeout
            $preShipmentCancelReasons = [
                'PAYMENT_TIMEOUT',
                'PAYMENT_TIMEOUT_BUYER',
                'BUYER_PAY_TIMEOUT',
                'BUYER_NOT_PAY',
                'NO_PAYMENT',
            ];
            $hasPreShipmentCancel = false;
            foreach ($preShipmentCancelReasons as $r) {
                if (stripos($endReasonUpper, $r) !== false) {
                    $hasPreShipmentCancel = true;
                    break;
                }
            }

            if (! empty($trackingNumber) && ! $hasPreShipmentCancel) {
                return ExternalPlatformOrder::STATUS_COMPLETED;
            }
        }

        // 3. Check cancellation indicators
        $cancelIndicators = [
            'PAYMENT_TIMEOUT',
            'PAYMENT_TIMEOUT_BUYER',
            'BUYER_PAY_TIMEOUT',
            'BUYER_CANCEL_ORDER',
            'BUYER_CANCELLED',
            'BUYER_CANCEL',
            'BUYER_NOT_PAY',
            'SELLER_CANCEL',
            'RISK_CONTROL_CANCEL',
            'NO_PAYMENT',
            'CANCEL',
            'CLOSED',
            'IN_CANCEL',
            'CANCELLED',
            'TIMEOUT',
        ];

        foreach ($cancelIndicators as $indicator) {
            if (stripos($endReasonUpper, $indicator) !== false || stripos($rawUpper, $indicator) !== false) {
                return ExternalPlatformOrder::STATUS_CANCELLED;
            }
        }

        if (! empty($endReasonDesc) && (
            stripos($endReasonDesc, 'No payment') !== false ||
            stripos($endReasonDesc, 'timeout') !== false ||
            stripos($endReasonDesc, 'cancel') !== false ||
            stripos($endReasonDesc, 'closed') !== false
        )) {
            return ExternalPlatformOrder::STATUS_CANCELLED;
        }

        return match ($rawUpper) {
            'PLACE_ORDER_SUCCESS', 'WAIT_BUYER_PAY' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
            'RISK_CONTROL', 'WAIT_SELLER_SEND_GOODS', 'PROCESSING', 'PAYMENT_CONFIRMED' => ExternalPlatformOrder::STATUS_PROCESSING,
            'SELLER_SEND_GOODS', 'SHIPPED', 'WAIT_RECEIVE', 'WAIT_BUYER_ACCEPT_GOODS', 'SELLER_SEND_PART_GOODS' => ExternalPlatformOrder::STATUS_SHIPPED,
            'FINISH', 'COMPLETED' => ! empty($trackingNumber) ? ExternalPlatformOrder::STATUS_COMPLETED : ExternalPlatformOrder::STATUS_CANCELLED,
            'IN_CANCEL', 'CANCELLED', 'CLOSED' => ExternalPlatformOrder::STATUS_CANCELLED,
            default => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
        };
    }
}
