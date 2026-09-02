import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Carbon\\Carbon;

echo "=========================================================\\n";
echo "1. LATEST SYNC RUNS IN sync_runs TABLE\\n";
echo "=========================================================\\n";

$latestRuns = DB::table('sync_runs')->latest('id')->limit(5)->get();
foreach ($latestRuns as $run) {
    echo "Sync Run #{$run->id}:\\n";
    echo "  Type/Trigger: " . ($run->type ?? $run->trigger ?? 'N/A') . "\\n";
    echo "  Status: " . ($run->status ?? 'N/A') . "\\n";
    echo "  Started At: " . ($run->started_at ?? 'N/A') . "\\n";
    echo "  Completed At: " . ($run->completed_at ?? 'N/A') . "\\n";
    echo "  Duration: " . ($run->duration ?? $run->duration_seconds ?? 'N/A') . "s\\n";
    echo "  Total Items: " . ($run->total_items ?? $run->items_count ?? 'N/A') . "\\n";
    echo "  Processed: " . ($run->processed_items ?? $run->success_count ?? 'N/A') . "\\n";
    echo "  Failed: " . ($run->failed_items ?? $run->error_count ?? 'N/A') . "\\n";
    if (!empty($run->summary) || !empty($run->payload) || !empty($run->meta)) {
        echo "  Details/Summary: " . json_encode($run->summary ?? $run->payload ?? $run->meta, JSON_UNESCAPED_UNICODE) . "\\n";
    }
    echo "---------------------------------------------------------\\n";
}

$latestRun = $latestRuns->first();
$latestRunTime = $latestRun ? ($latestRun->started_at ?? $latestRun->created_at) : Carbon::today()->toDateTimeString();

echo "\\n=========================================================\\n";
echo "2. RECENT EXTERNAL API CALLS (SINCE LATEST SYNC)\\n";
echo "=========================================================\\n";

$syncCalls = DB::table('external_api_logs')
    ->where('provider', 'aliexpress')
    ->where('created_at', '>=', Carbon::parse($latestRunTime)->subMinutes(5))
    ->orderBy('id', 'desc')
    ->limit(30)
    ->get();

echo "Total Calls in Window: " . count($syncCalls) . "\\n";
foreach ($syncCalls->take(10) as $c) {
    echo "  [{$c->created_at}] #{$c->id} | Endpoint: {$c->endpoint} | Status: {$c->status_code} | Latency: {$c->latency_ms}ms\\n";
}

echo "\\n=========================================================\\n";
echo "3. PRODUCTS UPDATED IN THIS WINDOW\\n";
echo "=========================================================\\n";

$updatedImports = DB::table('aliexpress_product_imports')
    ->where('updated_at', '>=', Carbon::parse($latestRunTime)->subMinutes(5))
    ->orderBy('updated_at', 'desc')
    ->limit(20)
    ->get();

echo "Updated AliExpress Imports Count: " . count($updatedImports) . "\\n";
foreach ($updatedImports->take(10) as $imp) {
    echo "  Import #{$imp->id} (AE Prod ID: {$imp->aliexpress_product_id}, Bagisto Prod ID: {$imp->product_id}) | Updated: {$imp->updated_at}\\n";
}

echo "\\n=========================================================\\n";
echo "4. AUDIT / OUTBOX EVENTS IN THIS WINDOW\\n";
echo "=========================================================\\n";

if (DB::getSchemaBuilder()->hasTable('outbox_messages')) {
    $outbox = DB::table('outbox_messages')
        ->where('created_at', '>=', Carbon::parse($latestRunTime)->subMinutes(5))
        ->latest('id')
        ->limit(10)
        ->get();
    echo "Outbox Messages: " . count($outbox) . "\\n";
    foreach ($outbox as $om) {
        echo "  [{$om->created_at}] Event: {$om->event_type} | Status: {$om->status}\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/audit_latest_sync.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 audit_latest_sync.php && rm audit_latest_sync.php")
print(f"OUT:\n{out}")

client.close()
