<?php

namespace Webkul\Procurement\Services;

use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Sales\Contracts\Order;

class ProcurementProfitabilityService
{
    /**
     * Calculate profitability metrics for a customer order sourced via Procurement V2.
     *
     * @return array{
     *     order_id: int,
     *     currency: string,
     *     customer_revenue: float,
     *     is_cod: bool,
     *     cod_collected: bool,
     *     expected_procurement_cost: float,
     *     actual_procurement_cost: float,
     *     expected_margin: float,
     *     realized_margin: float,
     *     demands_count: int
     * }
     */
    public function calculateOrderProfitability(Order $order): array
    {
        $demands = ProcurementDemand::where('order_id', $order->id)->get();
        $isCod = strtolower((string) ($order->payment->method ?? '')) === 'cashondelivery';

        // Check if COD is collected (paid invoice exists or delivery cash collection recorded)
        $codCollected = (! $isCod) || $order->invoices()->where('state', 'paid')->exists();

        $customerRevenue = (float) $order->grand_total;
        $expectedProcurementCost = 0.0;
        $actualProcurementCost = 0.0;

        foreach ($demands as $demand) {
            $unitCost = (float) ($demand->source_snapshot['unit_cost'] ?? 10.0);
            $expectedProcurementCost += ($demand->qty_required_external * $unitCost);

            // Fetch allocations to get actual costs
            foreach ($demand->allocations as $alloc) {
                $actualUnitCost = $alloc->purchaseOrderItem?->actual_unit_cost ?? $unitCost;
                $actualProcurementCost += ($alloc->qty_allocated * (float) $actualUnitCost);
            }
        }

        $expectedMargin = round($customerRevenue - $expectedProcurementCost, 4);
        $realizedMargin = $codCollected ? round($customerRevenue - $actualProcurementCost, 4) : 0.0;

        return [
            'order_id' => $order->id,
            'currency' => 'USD',
            'customer_revenue' => $customerRevenue,
            'is_cod' => $isCod,
            'cod_collected' => $codCollected,
            'expected_procurement_cost' => round($expectedProcurementCost, 4),
            'actual_procurement_cost' => round($actualProcurementCost, 4),
            'expected_margin' => $expectedMargin,
            'realized_margin' => $realizedMargin,
            'demands_count' => $demands->count(),
        ];
    }

    /**
     * Calculate profitability metrics for a Procurement Batch.
     */
    public function calculateBatchProfitability(ProcurementBatch $batch): array
    {
        $expectedCost = (float) $batch->expected_total_cost;
        $actualCost = (float) ($batch->actual_total_cost ?? $expectedCost);
        $variance = (float) $batch->cost_variance_amount;

        $totalCustomerRevenue = 0.0;
        $totalRealizedRevenue = 0.0;

        foreach ($batch->demands as $demand) {
            if ($demand->order) {
                $itemRevenue = (float) ($demand->orderItem?->total ?? 0.0);
                $totalCustomerRevenue += $itemRevenue;

                $isCod = strtolower((string) ($demand->order->payment->method ?? '')) === 'cashondelivery';
                $isPaid = (! $isCod) || $demand->order->invoices()->where('state', 'paid')->exists();

                if ($isPaid) {
                    $totalRealizedRevenue += $itemRevenue;
                }
            }
        }

        return [
            'batch_id' => $batch->id,
            'currency' => 'USD',
            'total_customer_revenue' => round($totalCustomerRevenue, 4),
            'total_realized_revenue' => round($totalRealizedRevenue, 4),
            'expected_cost' => round($expectedCost, 4),
            'actual_cost' => round($actualCost, 4),
            'cost_variance' => round($variance, 4),
            'expected_margin' => round($totalCustomerRevenue - $expectedCost, 4),
            'realized_margin' => round($totalRealizedRevenue - $actualCost, 4),
        ];
    }
}
