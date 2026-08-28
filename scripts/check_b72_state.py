import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$b72 = DB::table('procurement_batches')->where('id', 72)->first();
echo "Batch 72 State: " . ($b72->state ?? 'N/A') . "\n";

$spos = DB::table('supplier_purchase_orders')->where('batch_id', 72)->get();
foreach ($spos as $spo) {
    echo "SPO ID: {$spo->id}, State: {$spo->state}, PO Number: {$spo->purchase_order_number}\n";
    $pos = DB::table('external_platform_orders')->where('supplier_purchase_order_id', $spo->id)->get();
    foreach ($pos as $po) {
        echo "  Platform Order ID: {$po->id}, Ext Order ID: {$po->external_order_id}, Status: {$po->normalized_status}, Failure Code: {$po->failure_code}, Msg: {$po->failure_message}\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_b72_state.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_b72_state.php && rm check_b72_state.php")
print(f"OUTPUT:\n{out}")

client.close()
