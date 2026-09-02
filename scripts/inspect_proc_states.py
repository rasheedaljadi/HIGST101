import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

echo "=========================================================\\n";
echo "DEMANDS IN procurement_demands\\n";
echo "=========================================================\\n";
$demands = DB::table('procurement_demands')->get();
echo "Total Demands: " . $demands->count() . "\\n";
$byState = $demands->groupBy('state')->map->count();
echo "Demands by State: " . json_encode($byState) . "\\n";

foreach ($demands->take(5) as $d) {
    echo "  Demand #{$d->id} | Order #{$d->order_id} | Item #{$d->order_item_id} | State: {$d->state} | external_qty: {$d->qty_required_external}\\n";
}

echo "\\n=========================================================\\n";
echo "PURCHASE ORDERS IN purchase_orders\\n";
echo "=========================================================\\n";
$pos = DB::table('purchase_orders')->get();
echo "Total Purchase Orders: " . $pos->count() . "\\n";
$poByState = $pos->groupBy('state')->map->count();
echo "POs by State: " . json_encode($poByState) . "\\n";

foreach ($pos->take(5) as $po) {
    echo "  PO #{$po->id} | Order #{$po->order_id} | State: {$po->state} | Tracking: " . ($po->tracking_number ?: 'NONE') . "\\n";
}

echo "\\n=========================================================\\n";
echo "BATCHES IN procurement_batches\\n";
echo "=========================================================\\n";
$batches = DB::table('procurement_batches')->get();
echo "Total Batches: " . $batches->count() . "\\n";
$batchByState = $batches->groupBy('state')->map->count();
echo "Batches by State: " . json_encode($batchByState) . "\\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_proc_states.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_proc_states.php && rm inspect_proc_states.php")
print(f"OUT:\n{out}")

client.close()
