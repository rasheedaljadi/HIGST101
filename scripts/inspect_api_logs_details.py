import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

echo "=========================================================\\n";
echo "1. COLUMNS OF external_api_logs\\n";
echo "=========================================================\\n";
$cols = Schema::getColumnListing('external_api_logs');
print_r($cols);

echo "\\n=========================================================\\n";
echo "2. RECENT RECORDS IN external_api_logs\\n";
echo "=========================================================\\n";
$recent = DB::table('external_api_logs')->latest('id')->limit(5)->get();
foreach ($recent as $r) {
    echo "ID: {$r->id}, Provider: " . ($r->provider ?? '') . ", Method/Endpoint: " . ($r->endpoint ?? $r->method ?? $r->api_name ?? '') . ", Status: " . ($r->status_code ?? $r->status ?? '') . ", Created: {$r->created_at}\\n";
}

echo "\\n=========================================================\\n";
echo "3. TODAY'S TOTAL API CALLS IN external_api_logs\\n";
echo "=========================================================\\n";
$todayCalls = DB::table('external_api_logs')
    ->whereDate('created_at', now()->toDateString())
    ->count();
echo "Today's Total External API Calls: {$todayCalls}\\n";

$byProvider = DB::table('external_api_logs')
    ->whereDate('created_at', now()->toDateString())
    ->select('provider', DB::raw('count(*) as count'))
    ->groupBy('provider')
    ->get();
print_r($byProvider->toArray());

$byStatus = DB::table('external_api_logs')
    ->whereDate('created_at', now()->toDateString())
    ->select(DB::raw('status_code, count(*) as count'))
    ->groupBy('status_code')
    ->get();
print_r($byStatus->toArray());
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_api_logs_details.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_api_logs_details.php && rm inspect_api_logs_details.php")
print(f"OUT:\n{out}")

client.close()
