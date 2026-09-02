import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$orders = DB::table('external_platform_orders')->latest('id')->limit(5)->get();
echo "=========================================================\\n";
echo "LATEST EXTERNAL PLATFORM ORDERS ON PRODUCTION:\\n";
echo "=========================================================\\n";
foreach ($orders as $o) {
    echo "ID: {$o->id} | Ext Order ID: {$o->external_order_id} | Created: {$o->created_at}\\n";
    $snap = json_decode($o->snapshots ?? '{}', true);
    if (!empty($snap['raw_response'])) {
        echo "  - Raw Response Keys: " . implode(', ', array_keys($snap['raw_response'])) . "\\n";
    }
}

$logs = DB::table('procurement_audit_logs')
    ->where('action', 'like', '%submit%')
    ->latest('id')
    ->limit(5)
    ->get();

echo "\\n=========================================================\\n";
echo "SUBMISSION AUDIT LOGS:\\n";
echo "=========================================================\\n";
foreach ($logs as $l) {
    echo "ID: {$l->id} | Action: {$l->action} | Time: {$l->created_at}\\n";
    echo "  Details: {$l->details}\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_history_inspection.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_history_inspection.php && rm test_history_inspection.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
