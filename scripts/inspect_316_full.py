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
use Webkul\\Procurement\\Models\\ProcurementDemandAllocation;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Product\\Models\\Product;
use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$order = Order::find(316);
if (!$order) {
    $order = Order::where('increment_id', '316')->orWhere('increment_id', 'like', '%316%')->first();
}

if ($order) {
    echo "=== CUSTOMER ORDER #{$order->id} (Increment: {$order->increment_id}) ===\n";
    echo "Order Status: " . $order->status . "\n";
    echo "Sub Total: " . $order->sub_total . " " . $order->order_currency_code . "\n";
    echo "Shipping Amount: " . $order->shipping_amount . " " . $order->order_currency_code . "\n";
    echo "Tax Amount: " . $order->tax_amount . " " . $order->order_currency_code . "\n";
    echo "Grand Total: " . $order->grand_total . " " . $order->order_currency_code . "\n";
    echo "Base Grand Total: " . $order->base_grand_total . "\n";
    
    // Invoices
    echo "\n--- INVOICES ---\n";
    foreach ($order->invoices as $inv) {
        echo "Invoice ID: #{$inv->id} | State: {$inv->state} | Total: {$inv->grand_total} | Subtotal: {$inv->sub_total} | Tax: {$inv->tax_amount} | Shipping: {$inv->shipping_amount}\n";
    }

    echo "\n--- ORDER ITEMS ---\n";
    foreach ($order->items as $item) {
        echo "Item ID: {$item->id}\n";
        echo "Product ID: {$item->product_id}\n";
        echo "Product Name: {$item->name}\n";
        echo "SKU: {$item->sku}\n";
        echo "Qty: {$item->qty_ordered}\n";
        echo "Item Price: {$item->price}\n";
        echo "Item Total: {$item->total}\n";
        echo "Item Additional:\n" . json_encode($item->additional, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        $selectedVariantId = $item->additional['selected_configurable_option'] ?? null;
        if ($selectedVariantId) {
            $variant = Product::find($selectedVariantId);
            echo "Selected Variant ID: {$selectedVariantId}\n";
            echo "Selected Variant SKU: " . ($variant?->sku) . "\n";
            echo "Selected Variant Cost in DB: " . ($variant?->cost) . "\n";
            echo "Selected Variant Price in DB: " . ($variant?->price) . "\n";
        }
    }
} else {
    echo "Order 316 not found!\n";
}

echo "\n=== PROCUREMENT DEMAND(S) FOR ORDER #316 ===\n";
$demands = ProcurementDemand::where('order_id', $order?->id ?? 316)->get();
foreach ($demands as $demand) {
    echo "\nDemand ID: #{$demand->id}\n";
    echo "Demand State: {$demand->state}\n";
    echo "Variant Product ID: {$demand->variant_product_id}\n";
    echo "Supplier Product ID: {$demand->supplier_product_id}\n";
    echo "Supplier SKU ID: {$demand->supplier_sku_id}\n";
    echo "Supplier Store: {$demand->supplier_store_name} ({$demand->supplier_store_id})\n";
    echo "Qty Requested: {$demand->qty_requested}\n";
    echo "Source Snapshot Unit Cost: " . ($demand->source_snapshot['unit_cost'] ?? 'N/A') . "\n";
    
    // Check allocations to SPO
    $allocations = ProcurementDemandAllocation::where('procurement_demand_id', $demand->id)->get();
    foreach ($allocations as $alloc) {
        $spoItem = $alloc->supplierPurchaseOrderItem;
        if ($spoItem) {
            $spo = $spoItem->supplierPurchaseOrder;
            echo "\n--- ALLOCATED TO SPO #{$spo->purchase_order_number} (ID: {$spo->id}) ---\n";
            echo "SPO State: {$spo->state}\n";
            echo "SPO Expected Items Total: {$spo->expected_items_total}\n";
            echo "SPO Expected Total: {$spo->expected_total} USD\n";
            echo "SPO Item Supplier SKU: {$spoItem->supplier_sku_id}\n";
            echo "SPO Item Qty: {$spoItem->qty_ordered}\n";
            echo "SPO Item Expected Unit Cost: {$spoItem->expected_unit_cost}\n";
            
            // Check ExternalPlatformOrder
            $extOrders = ExternalPlatformOrder::where('supplier_purchase_order_id', $spo->id)->get();
            foreach ($extOrders as $extOrder) {
                echo "\n--- EXTERNAL PLATFORM ORDER ---\n";
                echo "External Order ID: {$extOrder->external_order_id}\n";
                echo "Correlation Key: {$extOrder->correlation_key}\n";
                echo "Raw Status: {$extOrder->raw_status}\n";
                echo "Normalized Status: {$extOrder->normalized_status}\n";
                echo "Snapshots: " . json_encode($extOrder->snapshots, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                
                // If there's an external order ID, query live AliExpress API
                if (!empty($extOrder->external_order_id)) {
                    echo "\n=== QUERYING LIVE ALIEXPRESS API FOR ORDER {$extOrder->external_order_id} ===\n";
                    try {
                        $authResolver = app(AliExpressAuthorizationContextResolver::class);
                        $auth = $authResolver->resolveForDropshipperSubmission(null);
                        $apiClient = app(AliExpressApiClient::class);
                        
                        $response = $apiClient->call('aliexpress.trade.ds.order.get', $auth->accessToken, [
                            'single_order_query' => json_encode(['order_id' => (string) $extOrder->external_order_id]),
                        ]);
                        
                        echo "API Response OK: " . ($response['ok'] ? 'true' : 'false') . "\n";
                        echo json_encode($response['body'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                    } catch (\\Throwable $e) {
                        echo "API Exception: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_316_full.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_316_full.php && rm -f inspect_316_full.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
