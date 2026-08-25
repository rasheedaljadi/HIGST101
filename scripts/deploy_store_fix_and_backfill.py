import sys
import os
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'
LOCAL_ROOT = r'e:\HIGESTO NEW1\higest\higest101'

print(f"Connecting to {HOST}...")
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

files_to_sync = [
    ('app/Services/AliExpress/DTO/NormalizedProduct.php', 'app/Services/AliExpress/DTO/NormalizedProduct.php'),
    ('app/Services/AliExpress/AliExpressProductMapper.php', 'app/Services/AliExpress/AliExpressProductMapper.php'),
    ('app/Services/AliExpress/AliExpressProductImporter.php', 'app/Services/AliExpress/AliExpressProductImporter.php'),
    ('app/Services/AliExpress/AliExpressProductSyncer.php', 'app/Services/AliExpress/AliExpressProductSyncer.php'),
    ('packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php', 'packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php'),
]

for src, dst in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, src.replace('/', os.sep))
    remote_path = f"{APP_DIR}/{dst}"
    print(f"Uploading {src} -> {dst}")
    sftp.put(local_path, remote_path)

php_backfill = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressProductImport;
use App\\Models\\AliExpressToken;
use App\\Services\\AliExpress\\AliExpressApiClient;
use App\\Services\\AliExpress\\AliExpressProductMapper;
use Illuminate\\Support\\Facades\\DB;
use Spatie\\ResponseCache\\Facades\\ResponseCache;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Services\\ProcurementDemandService;
use Webkul\\Procurement\\Services\\ProcurementEligibilityService;
use Webkul\\Sales\\Models\\Order;

echo "=== 1. BACKFILLING STORE METADATA FOR ALL IMPORTED PRODUCTS ===\\n";
$imports = AliExpressProductImport::where('status', 'success')->get();
$token = AliExpressToken::latest()->first();
$apiClient = app(AliExpressApiClient::class);
$mapper = app(AliExpressProductMapper::class);

$backfilledCount = 0;
foreach ($imports as $import) {
    $snapshot = is_array($import->payload_snapshot) ? $import->payload_snapshot : json_decode($import->payload_snapshot, true);
    $storeId = $snapshot['store_info']['store_id'] ?? $snapshot['ae_store_info']['store_id'] ?? $snapshot['store_id'] ?? null;

    if (empty($storeId)) {
        try {
            $result = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
                'product_id' => (string)$import->aliexpress_product_id,
                'ship_to_country' => 'SA',
                'target_currency' => 'USD',
                'target_language' => 'en',
            ]);

            $dto = $mapper->map($result['body'], (string)$import->aliexpress_product_id);
            if ($dto->storeInfo && !empty($dto->storeInfo['store_id'])) {
                $snapshot['store_info'] = $dto->storeInfo;
                $snapshot['ae_store_info'] = $dto->storeInfo;
                $snapshot['store_id'] = $dto->storeInfo['store_id'];
                $snapshot['store_name'] = $dto->storeInfo['store_name'];

                $import->update([
                    'payload_snapshot' => $snapshot,
                ]);

                $backfilledCount++;
                echo "  ✓ Product ID: {$import->product_id} (AE: {$import->aliexpress_product_id}) -> Store: {$dto->storeInfo['store_name']} (#{$dto->storeInfo['store_id']})\\n";
            }
        } catch (Throwable $e) {
            echo "  ⚠ Failed to fetch store for AE ID: {$import->aliexpress_product_id}: {$e->getMessage()}\\n";
        }
    } else {
        echo "  - Product ID: {$import->product_id} already has store ID #{$storeId}\\n";
    }
}
echo "Total products backfilled with store metadata: {$backfilledCount}\\n\\n";

echo "=== 2. RECONCILING PENDING & EXCEPTION PROCUREMENT DEMANDS ===\\n";
$demands = ProcurementDemand::where('state', ProcurementDemand::STATE_SUPPLIER_EXCEPTION)->get();
$eligibilityService = app(ProcurementEligibilityService::class);

$healedDemands = 0;
foreach ($demands as $demand) {
    $orderItem = DB::table('order_items')->where('id', $demand->order_item_id)->first();
    if (!$orderItem) {
        continue;
    }

    $classification = $eligibilityService->classifyOrderItem(new \\Webkul\\Sales\\Models\\OrderItem((array)$orderItem));

    if ($classification['metadata_status'] === 'valid' && !empty($classification['supplier_store_id'])) {
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

        $healedDemands++;
        echo "  ✓ Demand #{$demand->id} (Order #{$demand->order_id}) -> HEALED to STATE_OPEN_FOR_BATCHING (Store: {$classification['supplier_store_name']} #{$classification['supplier_store_id']})\\n";
    } else {
        echo "  - Demand #{$demand->id} still has metadata status: {$classification['metadata_status']}\\n";
    }
}
echo "Total demands healed: {$healedDemands}\\n";

if (class_exists(ResponseCache::class)) {
    ResponseCache::clear();
    echo "\\nResponseCache cleared.\\n";
}
"""

with sftp.open(f"{APP_DIR}/run_backfill_store.php", 'w') as f:
    f.write(php_backfill)
sftp.close()

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out:
        print(out.strip())
    if err:
        print("STDERR:", err.strip())

run_cmd(f"cd {APP_DIR} && php run_backfill_store.php && rm run_backfill_store.php")
run_cmd(f"cd {APP_DIR} && php artisan optimize:clear")

client.close()
