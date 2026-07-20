<?php

namespace Webkul\Fulfillment\Services\Domain;

use Illuminate\Support\Facades\DB;

class FulfillmentMetricTracker
{
    public function track(string $provider, bool $success, float $submitTime = 0, float $shippingTime = 0, ?string $failureReason = null): void
    {
        $metric = DB::table('procurement_metrics')->where('provider', $provider)->first();

        if (! $metric) {
            DB::table('procurement_metrics')->insert([
                'provider'              => $provider,
                'total_orders'          => 1,
                'success_rate'          => $success ? 100.00 : 0.00,
                'average_submit_time'   => $submitTime,
                'average_shipping_time' => $shippingTime,
                'failure_rate'          => $success ? 0.00 : 100.00,
                'last_failure_reason'   => $failureReason,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
            return;
        }

        $total = $metric->total_orders + 1;
        $successes = ($metric->success_rate / 100 * $metric->total_orders) + ($success ? 1 : 0);
        $successRate = ($successes / $total) * 100;
        $failureRate = 100 - $successRate;

        $newSubmitTime = (($metric->average_submit_time * $metric->total_orders) + $submitTime) / $total;
        $newShippingTime = (($metric->average_shipping_time * $metric->total_orders) + $shippingTime) / $total;

        DB::table('procurement_metrics')
            ->where('provider', $provider)
            ->update([
                'total_orders'          => $total,
                'success_rate'          => round($successRate, 2),
                'average_submit_time'   => round($newSubmitTime, 2),
                'average_shipping_time' => round($newShippingTime, 2),
                'failure_rate'          => round($failureRate, 2),
                'last_failure_reason'   => $failureReason ?: $metric->last_failure_reason,
                'updated_at'            => now(),
            ]);
    }
}
