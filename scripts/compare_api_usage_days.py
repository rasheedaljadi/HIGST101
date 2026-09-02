import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Carbon\\Carbon;

$today = Carbon::today()->toDateString();
$yesterday = Carbon::yesterday()->toDateString();

echo "=========================================================\\n";
echo "COMPARATIVE ANALYSIS: ALIEXPRESS API USAGE ({$yesterday} VS {$today})\\n";
echo "=========================================================\\n";

// 1. Overall Summary
function getDayStats($date) {
    $records = DB::table('external_api_logs')
        ->where('provider', 'aliexpress')
        ->whereDate('created_at', $date);

    $total = (clone $records)->count();
    $success = (clone $records)->where('status_code', 200)->count();
    $failed = (clone $records)->where('status_code', '!=', 200)->count();
    $avgLatency = (clone $records)->avg('latency_ms');
    $minLatency = (clone $records)->min('latency_ms');
    $maxLatency = (clone $records)->max('latency_ms');

    return [
        'total' => $total,
        'success' => $success,
        'failed' => $failed,
        'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
        'avg_latency' => round((float) $avgLatency, 1),
        'min_latency' => $minLatency,
        'max_latency' => $maxLatency,
    ];
}

$statsYesterday = getDayStats($yesterday);
$statsToday = getDayStats($today);

echo "\\n--- [ 1. OVERALL METRICS ] ---\\n";
printf("%-25s | %-15s | %-15s | %-15s\\n", "Metric", "Yesterday ({$yesterday})", "Today ({$today})", "Change");
printf("%'-80s\\n", "");

$diffTotal = $statsToday['total'] - $statsYesterday['total'];
$diffTotalPct = $statsYesterday['total'] > 0 ? round(($diffTotal / $statsYesterday['total']) * 100, 1) : 0;
printf("%-25s | %-15d | %-15d | %+d (%+.1f%%)\\n", "Total API Calls", $statsYesterday['total'], $statsToday['total'], $diffTotal, $diffTotalPct);

printf("%-25s | %-15d | %-15d | %+d\\n", "Successful (200 OK)", $statsYesterday['success'], $statsToday['success'], $statsToday['success'] - $statsYesterday['success']);
printf("%-25s | %-15d | %-15d | %+d\\n", "Failed Calls", $statsYesterday['failed'], $statsToday['failed'], $statsToday['failed'] - $statsYesterday['failed']);
printf("%-25s | %-15s | %-15s | %s\\n", "Success Rate", $statsYesterday['success_rate'] . "%", $statsToday['success_rate'] . "%", ($statsToday['success_rate'] >= $statsYesterday['success_rate'] ? "🟢 Equal/Improved" : "🔴 Decreased"));
printf("%-25s | %-15s | %-15s | %s\\n", "Avg Latency", $statsYesterday['avg_latency'] . " ms", $statsToday['avg_latency'] . " ms", ($statsToday['avg_latency'] <= $statsYesterday['avg_latency'] ? "⚡ Faster" : "Slower"));
printf("%-25s | %-15s | %-15s | -\\n", "Latency Range", "{$statsYesterday['min_latency']} - {$statsYesterday['max_latency']} ms", "{$statsToday['min_latency']} - {$statsToday['max_latency']} ms");

// 2. Breakdown by Endpoint
echo "\\n--- [ 2. BREAKDOWN BY API ENDPOINT ] ---\\n";

$endpointsYesterday = DB::table('external_api_logs')
    ->where('provider', 'aliexpress')
    ->whereDate('created_at', $yesterday)
    ->select('endpoint', DB::raw('count(*) as total'), DB::raw('ROUND(AVG(latency_ms), 0) as avg_lat'))
    ->groupBy('endpoint')
    ->pluck('total', 'endpoint')
    ->toArray();

$endpointsToday = DB::table('external_api_logs')
    ->where('provider', 'aliexpress')
    ->whereDate('created_at', $today)
    ->select('endpoint', DB::raw('count(*) as total'), DB::raw('ROUND(AVG(latency_ms), 0) as avg_lat'))
    ->groupBy('endpoint')
    ->pluck('total', 'endpoint')
    ->toArray();

$allEndpoints = array_unique(array_merge(array_keys($endpointsYesterday), array_keys($endpointsToday)));

printf("%-45s | %-15s | %-15s\\n", "Endpoint", "Yesterday Calls", "Today Calls");
printf("%'-80s\\n", "");
foreach ($allEndpoints as $ep) {
    $yCount = $endpointsYesterday[$ep] ?? 0;
    $tCount = $endpointsToday[$ep] ?? 0;
    printf("%-45s | %-15d | %-15d\\n", $ep, $yCount, $tCount);
}

// 3. Hourly Breakdown for Yesterday
echo "\\n--- [ 3. HOURLY BREAKDOWN FOR YESTERDAY ({$yesterday}) ] ---\\n";
$hourlyYesterday = DB::table('external_api_logs')
    ->where('provider', 'aliexpress')
    ->whereDate('created_at', $yesterday)
    ->select(DB::raw('HOUR(created_at) as hr'), DB::raw('count(*) as count'))
    ->groupBy(DB::raw('HOUR(created_at)'))
    ->orderBy('hr')
    ->get();

foreach ($hourlyYesterday as $h) {
    $bar = str_repeat('█', min(50, (int)($h->count / 20) + 1));
    printf("Hour %02d:00 -> %4d calls %s\\n", $h->hr, $h->count, $bar);
}

// 4. Checking past 7 days trend
echo "\\n--- [ 4. LAST 7 DAYS TREND ] ---\\n";
$sevenDays = DB::table('external_api_logs')
    ->where('provider', 'aliexpress')
    ->where('created_at', '>=', Carbon::today()->subDays(6)->startOfDay())
    ->select(DB::raw('DATE(created_at) as dt'), DB::raw('count(*) as total'), DB::raw('SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END) as success'), DB::raw('ROUND(AVG(latency_ms), 0) as avg_lat'))
    ->groupBy(DB::raw('DATE(created_at)'))
    ->orderBy('dt')
    ->get();

printf("%-15s | %-12s | %-12s | %-15s\\n", "Date", "Total Calls", "Success", "Avg Latency");
printf("%'-60s\\n", "");
foreach ($sevenDays as $d) {
    printf("%-15s | %-12d | %-12d | %-15s\\n", $d->dt, $d->total, $d->success, $d->avg_lat . " ms");
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/compare_api_usage.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 compare_api_usage.php && rm compare_api_usage.php")
print(f"OUT:\n{out}")

client.close()
