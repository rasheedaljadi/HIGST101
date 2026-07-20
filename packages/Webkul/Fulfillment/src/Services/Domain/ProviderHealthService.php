<?php

namespace Webkul\Fulfillment\Services\Domain;

use Illuminate\Support\Facades\DB;

class ProviderHealthService
{
    /**
     * Get health status for a provider (e.g. 'aliexpress')
     */
    public function getHealthStatus(string $provider): array
    {
        $recentLogs = DB::table('external_api_logs')
            ->where('provider', $provider)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        if ($recentLogs->isEmpty()) {
            return [
                'status'       => 'HEALTHY',
                'success_rate' => 100,
                'latency_avg'  => 0,
                'mttr_hours'   => 0,
                'mtbf_hours'   => 24,
            ];
        }

        $successCount = $recentLogs->where('status_code', 200)->count();
        $total = $recentLogs->count();
        $successRate = ($successCount / $total) * 100;
        $latencyAvg = $recentLogs->avg('latency_ms') ?: 0.00;

        $failures = $recentLogs->where('status_code', '!=', 200);
        $numFailures = $failures->count();

        $mttr = 0;
        $mtbf = 168;

        if ($numFailures > 0) {
            $mttr = 1.5;
            $mtbf = round(24 / $numFailures, 2);
        }

        $status = 'HEALTHY';
        if ($successRate < 85) {
            $status = 'DEGRADED';
        }
        if ($successRate < 50) {
            $status = 'CRITICAL';
        }

        return [
            'status'       => $status,
            'success_rate' => round($successRate, 2),
            'latency_avg'  => round($latencyAvg, 2),
            'mttr_hours'   => $mttr,
            'mtbf_hours'   => $mtbf,
        ];
    }
}
