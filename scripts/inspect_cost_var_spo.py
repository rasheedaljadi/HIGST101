import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 1. Searching for SPO with number containing 9RCEKJ ===\n";
$spo = DB::table('supplier_purchase_orders')
    ->where('purchase_order_number', 'like', '%9RCEKJ%')
    ->first();

print_r($spo);

if ($spo) {
    echo "\n=== 2. Batch for this SPO ===\n";
    $batch = DB::table('procurement_batches')->where('id', $spo->batch_id)->first();
    print_r($batch);
    
    echo "\n=== 3. SPO Items ===\n";
    $items = DB::table('supplier_purchase_order_items')->where('supplier_purchase_order_id', $spo->id)->get();
    print_r($items);
}

echo "\n=== 4. All SPOs in cost_variance_review state ===\n";
$cvs = DB::table('supplier_purchase_orders')->where('state', 'cost_variance_review')->get();
echo "Found " . $cvs->count() . " SPOs in cost_variance_review:\n";
foreach ($cvs as $c) {
    echo "ID: {$c->id}, Number: {$c->purchase_order_number}, Batch ID: {$c->batch_id}, State: {$c->state}, Variance Amount: {$c->cost_variance_amount}, Expected: {$c->expected_total}, Created: {$c->created_at}\n";
}

echo "\n=== 5. All Batches in exception state ===\n";
$excBatches = DB::table('procurement_batches')->where('state', 'exception')->get();
echo "Found " . $excBatches->count() . " Batches in exception:\n";
foreach ($excBatches as $b) {
    echo "Batch ID: {$b->id}, Number: {$b->batch_number}, State: {$b->state}, Created: {$b->created_at}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_cost_var_spo.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php inspect_cost_var_spo.php && rm inspect_cost_var_spo.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
