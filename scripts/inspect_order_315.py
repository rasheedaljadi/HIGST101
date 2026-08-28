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
use Webkul\\Product\\Models\\Product;
use App\\Models\\ExternalVariantProjection;

$order = Order::find(315);
if (!$order) {
    $order = Order::where('increment_id', '315')->orWhere('increment_id', 'like', '%315%')->first();
}

if ($order) {
    echo "=== ORDER #" . $order->id . " (Increment: " . $order->increment_id . ") ===\n";
    echo "Grand Total: " . $order->grand_total . " " . $order->order_currency_code . "\n";
    echo "Base Grand Total: " . $order->base_grand_total . "\n";
    echo "Sub Total: " . $order->sub_total . "\n";
    echo "Shipping: " . $order->shipping_amount . "\n";
    echo "Tax: " . $order->tax_amount . "\n";
    echo "Status: " . $order->status . "\n";
    echo "Created At: " . $order->created_at . "\n";
    
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
        
        $selectedVariantId = $item->additional['selected_configurable_option'] ?? null;
        if ($selectedVariantId) {
            $variant = Product::find($selectedVariantId);
            echo "Selected Variant ID: {$selectedVariantId}\n";
            echo "Variant SKU: " . ($variant?->sku) . "\n";
            echo "Variant Cost: " . ($variant?->cost) . "\n";
            echo "Variant Price: " . ($variant?->price) . "\n";
            
            $proj = ExternalVariantProjection::where('variant_product_id', $selectedVariantId)->first();
            echo "Projection Ext SKU: " . ($proj?->external_sku_id ?? 'NONE') . "\n";
        }
    }
} else {
    echo "Order 315 not found!\n";
}

echo "\n=== DEMANDS FOR ORDER #315 ===\n";
$demands = ProcurementDemand::where('order_id', $order?->id ?? 315)->get();
foreach ($demands as $demand) {
    $unitCost = (float) ($demand->source_snapshot['unit_cost'] ?? 10.0);
    $deficit = $demand->remaining_unbatched_qty;
    $lineCost = $deficit * $unitCost;

    echo "\nDemand ID: #" . $demand->id . "\n";
    echo "Order ID: #" . $demand->order_id . "\n";
    echo "Order Item ID: #" . $demand->order_item_id . "\n";
    echo "Product ID: " . $demand->product_id . "\n";
    echo "Variant Product ID: " . $demand->variant_product_id . "\n";
    echo "Supplier Product ID: " . $demand->supplier_product_id . "\n";
    echo "Supplier SKU ID: " . $demand->supplier_sku_id . "\n";
    echo "Supplier Store: " . $demand->supplier_store_name . " (" . $demand->supplier_store_id . ")\n";
    echo "Qty Requested: " . $demand->qty_requested . "\n";
    echo "Deficit Qty: " . $deficit . "\n";
    echo "Unit Cost (in batch stage): $" . number_format($unitCost, 2) . "\n";
    echo "Total Cost (in batch stage): $" . number_format($lineCost, 2) . "\n";
    echo "State: " . $demand->state . "\n";
    echo "Source Snapshot: " . json_encode($demand->source_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_315_tmp.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_315_tmp.php && rm -f inspect_315_tmp.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
