import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$startTime = '2026-08-30 02:35:00';
$endTime   = '2026-08-30 02:41:35';

$calls = DB::table('external_api_logs')
    ->where('provider', 'aliexpress')
    ->whereBetween('created_at', [$startTime, $endTime])
    ->select('id', 'endpoint', 'correlation_id', 'status_code', 'latency_ms', 'created_at')
    ->get();

echo "Correlation IDs sample:\\n";
foreach ($calls->take(5) as $c) {
    echo "  Call #{$c->id} | Corr: {$c->correlation_id} | Created: {$c->created_at}\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_corr.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_corr.php && rm check_corr.php")
print(f"OUT:\n{out}")

client.close()
