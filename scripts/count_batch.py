import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$calls = DB::table('external_api_logs')
    ->where('provider', 'aliexpress')
    ->where('created_at', '>=', '2026-08-30 02:30:00')
    ->get();

echo "Total Calls Since 02:30:00: " . $calls->count() . "\\n";
$first = $calls->first();
$last = $calls->last();
echo "First Call At: {$first->created_at}\\n";
echo "Last Call At: {$last->created_at}\\n";

$statusSummary = $calls->groupBy('status_code')->map->count();
echo "Status Summary: " . json_encode($statusSummary) . "\\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/count_batch.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 count_batch.php && rm count_batch.php")
print(f"OUT:\n{out}")

client.close()
