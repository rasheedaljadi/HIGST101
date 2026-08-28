import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Query supplier purchase orders
$orders = DB::table('supplier_purchase_orders')
    ->whereIn('id', [62, 63, 64, 65, 66])
    ->get();

foreach ($orders as $o) {
    echo "ID: {$o->id}, Code: {$o->order_number}, Status: {$o->status}, Created: {$o->created_at}\n";
    echo "Failure Reason / Notes: " . ($o->failure_reason ?? $o->notes ?? 'N/A') . "\n";
    echo "--------------------------------------------------\n";
}

// Also check procurement_order_submissions or audit logs if they exist
$tables = ['procurement_order_submissions', 'procurement_submissions', 'procurement_orders', 'supplier_purchase_order_logs'];
foreach ($tables as $t) {
    if (Illuminate\Support\Facades\Schema::hasTable($t)) {
        echo "=== Table: {$t} ===\n";
        $rows = DB::table($t)->orderBy('id', 'desc')->take(5)->get();
        print_r($rows);
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_failed_pos.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_failed_pos.php && rm inspect_failed_pos.php")
print(f"OUTPUT:\n{out}")

client.close()
