import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

echo "=========================================================\\n";
echo "1. ORDERS IN DB BY STATUS\\n";
echo "=========================================================\\n";

$ordersByStatus = DB::table('orders')->select('status', DB::raw('count(*) as count'))->groupBy('status')->get();
foreach ($ordersByStatus as $os) {
    echo "  Status: {$os->status} => {$os->count} orders\\n";
}

echo "\\n=========================================================\\n";
echo "2. INSPECTING ORDER ITEMS & ADDITIONAL DATA\\n";
echo "=========================================================\\n";

$items = DB::table('order_items')->limit(10)->get();
foreach ($items as $item) {
    $add = json_decode($item->additional ?? '{}', true);
    echo "Item #{$item->id} (Order #{$item->order_id}, SKU: {$item->sku}):\\n";
    echo "  Additional keys: " . implode(', ', array_keys($add)) . "\\n";
    echo "  Additional JSON: " . json_encode($add, JSON_UNESCAPED_UNICODE) . "\\n";
}

echo "\\n=========================================================\\n";
echo "3. PROCUREMENT & PO TABLES CHECK\\n";
echo "=========================================================\\n";

$tables = ['procurement_demands', 'procurement_batches', 'procurement_supplier_orders', 'purchase_orders', 'inbound_receipt_manifests', 'inventory_transfer_manifests', 'order_lifecycle_stage_views'];
foreach ($tables as $tbl) {
    if (Schema::hasTable($tbl)) {
        $cnt = DB::table($tbl)->count();
        echo "  Table: {$tbl} => {$cnt} rows\\n";
    } else {
        echo "  Table: {$tbl} => DOES NOT EXIST\\n";
    }
}

echo "\\n=========================================================\\n";
echo "4. CURRENT STAGES IN order_lifecycle_stage_views\\n";
echo "=========================================================\\n";

if (Schema::hasTable('order_lifecycle_stage_views')) {
    $stageCounts = DB::table('order_lifecycle_stage_views')
        ->select('bottleneck_stage_code', DB::raw('count(*) as count'))
        ->groupBy('bottleneck_stage_code')
        ->get();
    foreach ($stageCounts as $sc) {
        echo "  Stage '{$sc->bottleneck_stage_code}' => {$sc->count} orders\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_lifecycle_pipeline.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_lifecycle_pipeline.php && rm inspect_lifecycle_pipeline.php")
print(f"OUT:\n{out}")

client.close()
