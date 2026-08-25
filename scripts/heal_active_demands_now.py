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
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressProductImport;
use App\\Models\\AliExpressToken;
use App\\Services\\AliExpress\\AliExpressApiClient;
use App\\Services\\AliExpress\\AliExpressProductMapper;
use Illuminate\\Support\\Facades\\DB;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Services\\ProcurementEligibilityService;

$token = AliExpressToken::latest()->first();
$apiClient = app(AliExpressApiClient::class);
$mapper = app(AliExpressProductMapper::class);
$eligibilityService = app(ProcurementEligibilityService::class);

// Find all demands with supplier_exception
$demands = ProcurementDemand::where('state', ProcurementDemand::STATE_SUPPLIER_EXCEPTION)->get();
echo "Found " . $demands->count() . " exception demands to heal.\\n";

foreach ($demands as $demand) {
    $parentProductId = $demand->product_id;
    $import = AliExpressProductImport::where('product_id', $parentProductId)->first();

    if ($import) {
        $result = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
            'product_id' => (string)$import->aliexpress_product_id,
            'ship_to_country' => 'SA',
            'target_currency' => 'USD',
            'target_language' => 'en',
        ]);

        $dto = $mapper->map($result['body'], (string)$import->aliexpress_product_id);
        if ($dto->storeInfo && !empty($dto->storeInfo['store_id'])) {
            $snapshot = is_array($import->payload_snapshot) ? $import->payload_snapshot : json_decode($import->payload_snapshot, true);
            $snapshot['store_info'] = $dto->storeInfo;
            $snapshot['ae_store_info'] = $dto->storeInfo;
            $snapshot['store_id'] = $dto->storeInfo['store_id'];
            $snapshot['store_name'] = $dto->storeInfo['store_name'];

            $import->update([
                'payload_snapshot' => $snapshot,
            ]);

            $orderItem = DB::table('order_items')->where('id', $demand->order_item_id)->first();
            $classification = $eligibilityService->classifyOrderItem(new \\Webkul\\Sales\\Models\\OrderItem((array)$orderItem));

            $demand->update([
                'supplier_store_id' => $classification['supplier_store_id'],
                'supplier_store_name' => $classification['supplier_store_name'],
                'state' => ProcurementDemand::STATE_OPEN_FOR_BATCHING,
                'source_snapshot' => $classification['source_snapshot'],
                'eligibility_snapshot' => array_merge(
                    is_array($demand->eligibility_snapshot) ? $demand->eligibility_snapshot : (json_decode($demand->eligibility_snapshot, true) ?? []),
                    [
                        'metadata_status' => 'valid',
                        'exception_reason' => null,
                        'healed_at' => now()->toIso8601String(),
                    ]
                ),
            ]);

            echo "✓ HEALED Demand #{$demand->id} (Order #{$demand->order_id}) -> Store: {$classification['supplier_store_name']} (#{$classification['supplier_store_id']}) -> State: open_for_batching\\n";
        }
    }
}
"""

with sftp.open(f"{APP_DIR}/heal_demands_instant.php", 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php heal_demands_instant.php && rm heal_demands_instant.php")
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
