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

use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrderItem;
use Webkul\\Procurement\\Models\\ProcurementDemandAllocation;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Sales\\Models\\Order;
use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$externalOrderId = '1122474765781333';

echo "=== CHECKING EXTERNAL ORDER IN DATABASE ===\n";
$platformOrder = ExternalPlatformOrder::where('external_order_id', $externalOrderId)->first();

if (!$platformOrder) {
    echo "ExternalPlatformOrder not found directly with external_order_id: {$externalOrderId}\n";
    $platformOrder = ExternalPlatformOrder::where('correlation_key', 'like', "%{$externalOrderId}%")
        ->orWhere('snapshots', 'like', "%{$externalOrderId}%")
        ->first();
}

if ($platformOrder) {
    echo "ID: " . $platformOrder->id . "\n";
    echo "External Order ID: " . $platformOrder->external_order_id . "\n";
    echo "Correlation Key: " . $platformOrder->correlation_key . "\n";
    echo "Raw Status: " . $platformOrder->raw_status . "\n";
    echo "Normalized Status: " . $platformOrder->normalized_status . "\n";
    echo "SPO ID: " . $platformOrder->supplier_purchase_order_id . "\n";
    echo "Payment Deadline: " . $platformOrder->payment_deadline_at . "\n";
    echo "Snapshots: " . json_encode($platformOrder->snapshots, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    echo "\n=== PLATFORM ORDER ITEMS ===\n";
    foreach ($platformOrder->items as $item) {
        echo "Item ID: {$item->id} | External SKU ID: {$item->external_sku_id} | Qty: {$item->quantity} | Amount: {$item->actual_item_amount}\n";
    }
    
    $spo = $platformOrder->supplierPurchaseOrder;
    if ($spo) {
        echo "\n=== SPO #" . $spo->purchase_order_number . " (ID: {$spo->id}) ===\n";
        echo "Store: {$spo->supplier_store_name} ({$spo->supplier_store_id})\n";
        echo "Expected Total: {$spo->expected_total} USD\n";
        
        foreach ($spo->items as $spoItem) {
            echo "\n--- SPO ITEM ID: {$spoItem->id} ---\n";
            echo "Supplier Product ID: {$spoItem->supplier_product_id}\n";
            echo "Supplier SKU ID: {$spoItem->supplier_sku_id}\n";
            echo "Product ID: {$spoItem->product_id}\n";
            echo "Variant Product ID: {$spoItem->variant_product_id}\n";
            echo "Qty Ordered: {$spoItem->qty_ordered}\n";
            echo "Expected Unit Cost: {$spoItem->expected_unit_cost}\n";
            
            $allocations = ProcurementDemandAllocation::where('supplier_purchase_order_item_id', $spoItem->id)->get();
            foreach ($allocations as $alloc) {
                $demand = ProcurementDemand::find($alloc->procurement_demand_id);
                if ($demand) {
                    echo "  -> Allocated Demand ID: #{$demand->id} (Order #{$demand->order_id})\n";
                    echo "  -> Demand Variant ID: {$demand->variant_product_id}\n";
                    echo "  -> Demand Supplier SKU: {$demand->supplier_sku_id}\n";
                    
                    $order = Order::find($demand->order_id);
                    if ($order) {
                        echo "  -> Customer Order #{$order->increment_id} (Status: {$order->status}):\n";
                        foreach ($order->items as $ordItem) {
                            echo "     Item ID: {$ordItem->id} | Product ID: {$ordItem->product_id} | Name: {$ordItem->name}\n";
                            echo "     Selected Option (additional): " . json_encode($ordItem->additional, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                        }
                    }
                }
            }
        }
    }
}

echo "\n=== QUERYING ALIEXPRESS API FOR ORDER {$externalOrderId} ===\n";
try {
    $authResolver = app(AliExpressAuthorizationContextResolver::class);
    $auth = $authResolver->resolveForDropshipperSubmission(null);
    
    $apiClient = app(AliExpressApiClient::class);
    $response = $apiClient->call('aliexpress.trade.ds.order.get', $auth->accessToken, [
        'order_id' => $externalOrderId,
    ]);
    
    if (!$response['ok'] || !empty($response['body']['error_response'])) {
        $response = $apiClient->call('aliexpress.ds.order.get', $auth->accessToken, [
            'order_id' => $externalOrderId,
        ]);
    }
    
    echo "API Response OK: " . ($response['ok'] ? 'true' : 'false') . "\n";
    echo "API Response Body: " . json_encode($response['body'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (\\Throwable $e) {
    echo "API Query Exception: " . $e->getMessage() . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_ae_order_1122.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_ae_order_1122.php && rm -f inspect_ae_order_1122.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
