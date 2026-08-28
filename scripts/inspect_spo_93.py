import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SPO 93 DETAILS ===\n";
$spo = DB::table('supplier_purchase_orders')->where('id', 93)->first();
print_r($spo);

echo "\n=== SPO 93 ITEMS ===\n";
$items = DB::table('supplier_purchase_order_items')->where('supplier_purchase_order_id', 93)->get();
print_r($items);

echo "\n=== SPO 93 PLATFORM ORDER & RAW RESPONSE ===\n";
$pos = DB::table('external_platform_orders')->where('supplier_purchase_order_id', 93)->get();
foreach ($pos as $po) {
    echo "ID: {$po->id}, Ext Order ID: {$po->external_order_id}, Status: {$po->normalized_status}\n";
    echo "Failure Code: {$po->failure_code}\n";
    echo "Failure Msg: {$po->failure_message}\n";
    echo "Snapshots:\n" . json_encode(json_decode($po->snapshots ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_spo_93.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_spo_93.php && rm inspect_spo_93.php")
print(f"OUTPUT:\n{out}")

client.close()
