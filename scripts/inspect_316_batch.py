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

use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Models\\ProcurementBatchDemand;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$batchDemand = ProcurementBatchDemand::where('procurement_demand_id', 39)->first();
if ($batchDemand) {
    echo "Batch Demand found: Batch ID #{$batchDemand->batch_id}\n";
    $batch = ProcurementBatch::find($batchDemand->batch_id);
    if ($batch) {
        echo "Batch ID: #{$batch->id} | State: {$batch->state} | Total: {$batch->total_cost} {$batch->currency_code}\n";
        
        $spos = SupplierPurchaseOrder::where('batch_id', $batch->id)->get();
        echo "SPOs Count: " . $spos->count() . "\n";
        foreach ($spos as $spo) {
            echo "\n=== SPO #{$spo->purchase_order_number} (ID: {$spo->id}) ===\n";
            echo "State: {$spo->state}\n";
            echo "Store: {$spo->supplier_store_name} ({$spo->supplier_store_id})\n";
            echo "Expected Total: {$spo->expected_total} USD\n";
            
            foreach ($spo->items as $item) {
                echo "  SPO Item ID: {$item->id} | SKU: {$item->supplier_sku_id} | Qty: {$item->qty_ordered} | Unit Cost: {$item->expected_unit_cost}\n";
            }
            
            $extOrders = ExternalPlatformOrder::where('supplier_purchase_order_id', $spo->id)->get();
            foreach ($extOrders as $extOrder) {
                echo "\n  --- EXTERNAL PLATFORM ORDER ---\n";
                echo "  External Order ID: {$extOrder->external_order_id}\n";
                echo "  Correlation Key: {$extOrder->correlation_key}\n";
                echo "  Status: {$extOrder->normalized_status} ({$extOrder->raw_status})\n";
                echo "  Payment Deadline: {$extOrder->payment_deadline_at}\n";
                
                if (!empty($extOrder->external_order_id)) {
                    echo "\n  === QUERYING ALIEXPRESS API FOR {$extOrder->external_order_id} ===\n";
                    try {
                        $authResolver = app(AliExpressAuthorizationContextResolver::class);
                        $auth = $authResolver->resolveForDropshipperSubmission(null);
                        $apiClient = app(AliExpressApiClient::class);
                        
                        $response = $apiClient->call('aliexpress.trade.ds.order.get', $auth->accessToken, [
                            'single_order_query' => json_encode(['order_id' => (string) $extOrder->external_order_id]),
                        ]);
                        
                        echo "  API OK: " . ($response['ok'] ? 'true' : 'false') . "\n";
                        echo "  API Response:\n" . json_encode($response['body'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                    } catch (\\Throwable $e) {
                        echo "  API Exception: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
} else {
    echo "No BatchDemand for demand 39. Checking latest SPOs...\n";
    $latestSpos = SupplierPurchaseOrder::latest('id')->take(3)->get();
    foreach ($latestSpos as $s) {
        echo "SPO #{$s->id} - {$s->purchase_order_number} - State: {$s->state}\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_316_batch.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_316_batch.php && rm -f inspect_316_batch.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
