import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batch = DB::table('procurement_batches')->where('id', 71)->first();
echo "=== BATCH 71 ===\n";
print_r($batch);

$spos = DB::table('supplier_purchase_orders')->where('batch_id', 71)->get();
echo "\n=== SPOS FOR BATCH 71 ===\n";
print_r($spos);

foreach ($spos as $spo) {
    $items = DB::table('supplier_purchase_order_items')->where('supplier_purchase_order_id', $spo->id)->get();
    echo "\n=== ITEMS FOR SPO {$spo->id} ===\n";
    print_r($items);

    $pos = DB::table('external_platform_orders')->where('supplier_purchase_order_id', $spo->id)->get();
    echo "\n=== PLATFORM ORDERS FOR SPO {$spo->id} ===\n";
    print_r($pos);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_batch_71.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_batch_71.php && rm inspect_batch_71.php")
print(f"OUTPUT:\n{out}")

client.close()
