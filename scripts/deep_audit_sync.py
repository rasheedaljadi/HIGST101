import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Carbon\\Carbon;

$startTime = '2026-08-30 02:20:00';
$endTime   = '2026-08-30 02:41:35';

echo "=========================================================\\n";
echo "DEEP AUDIT REPORT FOR MANUAL SYNC RUN ({$startTime} TO {$endTime})\\n";
echo "=========================================================\\n";

// 1. Total API Calls during sync
$apiCalls = DB::table('external_api_logs')
    ->where('provider', 'aliexpress')
    ->whereBetween('created_at', [$startTime, $endTime])
    ->orderBy('id', 'asc')
    ->get();

echo "1. TOTAL API CALLS EXECUTED: " . $apiCalls->count() . "\\n";

$byEndpoint = [];
$statusCodes = [];
$totalLatency = 0;
foreach ($apiCalls as $c) {
    $byEndpoint[$c->endpoint] = ($byEndpoint[$c->endpoint] ?? 0) + 1;
    $statusCodes[$c->status_code] = ($statusCodes[$c->status_code] ?? 0) + 1;
    $totalLatency += (float) $c->latency_ms;
}

echo "   Breakdown by Endpoint:\\n";
foreach ($byEndpoint as $ep => $cnt) {
    echo "     - {$ep}: {$cnt} requests\\n";
}
echo "   Status Codes: " . json_encode($statusCodes) . "\\n";
echo "   Average Latency: " . round($totalLatency / max(1, $apiCalls->count()), 1) . " ms\\n";

// 2. Exact Products / Imports synced
echo "\\n2. PRODUCTS / IMPORTS SYNCED IN THIS RUN:\\n";
$updatedImports = DB::table('aliexpress_product_imports')
    ->whereBetween('updated_at', [$startTime, $endTime])
    ->orderBy('updated_at', 'asc')
    ->get();

echo "   Total Products Processed: " . $updatedImports->count() . "\\n";

foreach ($updatedImports as $imp) {
    $prodFlat = DB::table('product_flat')->where('product_id', $imp->product_id)->first();
    $prodName = $prodFlat?->name ?? 'Product #' . $imp->product_id;
    $invCount = DB::table('product_inventories')->where('product_id', $imp->product_id)->sum('qty');

    echo "   • Product #{$imp->product_id} (AE ID: {$imp->aliexpress_product_id}):\\n";
    echo "     - Name: {$prodName}\\n";
    echo "     - Status: {$imp->status}\\n";
    echo "     - Total Current Stock in DB: {$invCount}\\n";
    echo "     - Updated At: {$imp->updated_at}\\n";
}

// 3. Log snippet from aliexpress-2026-08-30.log
echo "\\n3. LOG ENTRIES FOR THIS MANUAL SYNC:\\n";
$logPath = storage_path('logs/aliexpress-2026-08-30.log');
if (file_exists($logPath)) {
    $lines = file($logPath);
    $matching = [];
    foreach ($lines as $line) {
        if (str_contains($line, '2026-08-30 02:') && (str_contains($line, 'sync') || str_contains($line, 'Sync') || str_contains($line, 'Stock') || str_contains($line, 'Price') || str_contains($line, 'Demands') || str_contains($line, 'Product'))) {
            $matching[] = trim($line);
        }
    }
    echo "   Found " . count($matching) . " log entries during this hour:\\n";
    foreach (array_slice($matching, -20) as $ml) {
        echo "   [LOG] " . substr($ml, 0, 140) . "...\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/deep_audit_sync.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 deep_audit_sync.php && rm deep_audit_sync.php")
print(f"OUT:\n{out}")

client.close()
