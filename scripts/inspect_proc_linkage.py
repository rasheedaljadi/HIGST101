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
echo "1. COLUMNS OF procurement_demands\\n";
echo "=========================================================\\n";
print_r(Schema::getColumnListing('procurement_demands'));

echo "\\n=========================================================\\n";
echo "2. SAMPLE OF procurement_demands\\n";
echo "=========================================================\\n";
$demands = DB::table('procurement_demands')->limit(5)->get();
foreach ($demands as $d) {
    echo "Demand #{$d->id}: Order #{$d->order_id}, Item #{$d->order_item_id}, Status: {$d->status}, Batch: {$d->procurement_batch_id}\\n";
}

echo "\\n=========================================================\\n";
echo "3. COLUMNS OF purchase_orders\\n";
echo "=========================================================\\n";
print_r(Schema::getColumnListing('purchase_orders'));

echo "\\n=========================================================\\n";
echo "4. SAMPLE OF purchase_orders\\n";
echo "=========================================================\\n";
$pos = DB::table('purchase_orders')->limit(5)->get();
foreach ($pos as $po) {
    echo "PO #{$po->id}: Order #{$po->order_id}, State: " . ($po->state ?? $po->status ?? 'N/A') . ", Tracking: " . ($po->tracking_number ?? 'N/A') . ", AE Order ID: " . ($po->external_order_id ?? $po->supplier_order_id ?? 'N/A') . "\\n";
}

echo "\\n=========================================================\\n";
echo "5. PRODUCT IMPORTS CHECK FOR ORDER ITEMS\\n";
echo "=========================================================\\n";
$itemProdIds = DB::table('order_items')->pluck('product_id')->unique();
$importedCount = DB::table('aliexpress_product_imports')->whereIn('product_id', $itemProdIds)->count();
echo "Total Distinct Product IDs in Order Items: " . $itemProdIds->count() . "\\n";
echo "Number of those that are AliExpress Imports: " . $importedCount . "\\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_proc_linkage.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_proc_linkage.php && rm inspect_proc_linkage.php")
print(f"OUT:\n{out}")

client.close()
