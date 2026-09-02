import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 1. Batch #77 Details ===\n";
$batch = DB::table('procurement_batches')->where('id', 77)->first();
print_r($batch);

echo "\n=== 2. SPO #98 Details ===\n";
$spo = DB::table('supplier_purchase_orders')->where('id', 98)->first();
print_r($spo);

echo "\n=== 3. SPO Items for SPO #98 ===\n";
$items = DB::table('supplier_purchase_order_items')->where('supplier_purchase_order_id', 98)->get();
print_r($items);

echo "\n=== 4. External Platform Orders for SPO #98 ===\n";
$ext_orders = DB::table('external_platform_orders')->where('supplier_purchase_order_id', 98)->get();
print_r($ext_orders);

echo "\n=== 5. Procurement Audit Logs (Latest 10) ===\n";
$audit_logs = DB::table('procurement_audit_logs')->orderBy('id', 'desc')->take(10)->get();
foreach ($audit_logs as $log) {
    echo "ID: {$log->id} | Action: {$log->action} | Target: {$log->target_type} #{$log->target_id} | Created: {$log->created_at}\n";
    echo "Details: {$log->details}\n";
    echo "--------------------------------------------------\n";
}

echo "\n=== 6. AliExpress OAuth Tokens ===\n";
if (\Illuminate\Support\Facades\Schema::hasTable('aliexpress_tokens')) {
    $tokens = DB::table('aliexpress_tokens')->orderBy('id', 'desc')->take(5)->get();
    foreach ($tokens as $tok) {
        $now = time() * 1000;
        $exp = $tok->expire_time ?? 0;
        $is_expired = ($exp < $now) ? "EXPIRED" : "VALID";
        echo "ID: {$tok->id}, User/Seller: {$tok->user_nick}, Expire time: " . date('Y-m-d H:i:s', $exp / 1000) . " ($is_expired), Refresh expire: " . date('Y-m-d H:i:s', ($tok->refresh_token_valid_time ?? 0) / 1000) . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_spo_98.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php inspect_spo_98.php && rm inspect_spo_98.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
