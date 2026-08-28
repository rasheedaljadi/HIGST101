import sys
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Webkul\\Sales\\Models\\Order;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use App\\Models\\ExternalVariantProjection;

$order = Order::find(314);
if (!$order) {
    $order = Order::where('increment_id', '314')->orWhere('increment_id', 'like', '%314%')->first();
}

if ($order) {
    echo "=== ORDER #" . $order->id . " (Increment: " . $order->increment_id . ") ===\n";
    echo "Grand Total: " . $order->grand_total . " " . $order->order_currency_code . "\n";
    echo "Base Grand Total: " . $order->base_grand_total . "\n";
    echo "Sub Total: " . $order->sub_total . "\n";
    echo "Shipping: " . $order->shipping_amount . "\n";
    echo "Tax: " . $order->tax_amount . "\n";
    echo "Status: " . $order->status . "\n";
    
    foreach ($order->items as $item) {
        echo "\n--- Item ID: " . $item->id . " ---\n";
        echo "Product ID: " . $item->product_id . "\n";
        echo "SKU: " . $item->sku . "\n";
        echo "Name: " . $item->name . "\n";
        echo "Qty Ordered: " . $item->qty_ordered . "\n";
        echo "Price: " . $item->price . "\n";
        echo "Base Price: " . $item->base_price . "\n";
        echo "Total: " . $item->total . "\n";
        echo "Base Total: " . $item->base_total . "\n";
        echo "Additional: " . json_encode($item->additional, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "Order not found! Checking latest 5 orders:\n";
    foreach (Order::latest('id')->take(5)->get() as $o) {
        echo "ID: {$o->id}, Inc: {$o->increment_id}, Total: {$o->grand_total}, Status: {$o->status}\n";
    }
}

echo "\n=== DEMANDS FOR ORDER (or Demand 37) ===\n";
$demands = ProcurementDemand::where('order_id', 314)->get();
if ($demands->isEmpty()) {
    $d37 = ProcurementDemand::find(37);
    if ($d37) $demands = collect([$d37]);
}

foreach ($demands as $demand) {
    echo "\nDemand ID: " . $demand->id . "\n";
    echo "Order ID: " . $demand->order_id . "\n";
    echo "Order Item ID: " . $demand->order_item_id . "\n";
    echo "Product ID: " . $demand->product_id . "\n";
    echo "Variant Product ID: " . $demand->variant_product_id . "\n";
    echo "Supplier Product ID: " . $demand->supplier_product_id . "\n";
    echo "Supplier SKU ID: " . $demand->supplier_sku_id . "\n";
    echo "Supplier Store: " . $demand->supplier_store_name . " (" . $demand->supplier_store_id . ")\n";
    echo "Qty Requested: " . $demand->qty_requested . "\n";
    echo "Qty Required Ext: " . $demand->qty_required_external . "\n";
    echo "State: " . $demand->state . "\n";
    echo "Source Snapshot: " . json_encode($demand->source_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    // Check projection
    if ($demand->variant_product_id) {
        $proj = ExternalVariantProjection::where('variant_product_id', $demand->variant_product_id)->first();
        echo "Projection for variant_product_id " . $demand->variant_product_id . ": " . json_encode($proj, JSON_PRETTY_PRINT) . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_314_tmp.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_314_tmp.php && rm -f inspect_314_tmp.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
