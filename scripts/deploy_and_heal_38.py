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

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

# Upload ProcurementEligibilityService
src = 'packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php'
local_path = os.path.join(LOCAL_ROOT, src.replace('/', os.sep))
remote_path = f"{APP_DIR}/{src}"
sftp.put(local_path, remote_path)
sftp.close()

# Rebuild projections and re-heal Demand #38
rebuild_and_heal_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\Artisan;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Sales\\Models\\OrderItem;
use Webkul\\Product\\Models\\Product;
use App\\Models\\ExternalVariantProjection;

echo "=== REBUILDING PROJECTIONS ===\n";
Artisan::call('aliexpress:rebuild-projections');
echo Artisan::output();

$demand = ProcurementDemand::find(38);
if ($demand) {
    echo "\n=== BEFORE HEALING DEMAND #38 ===\n";
    echo "SKU: " . $demand->supplier_sku_id . "\n";
    echo "Variant ID: " . $demand->variant_product_id . "\n";
    echo "Cost: " . ($demand->source_snapshot['unit_cost'] ?? 'N/A') . "\n";

    $orderItem = OrderItem::find($demand->order_item_id);
    $selectedVariantId = $orderItem->additional['selected_configurable_option'] ?? null;
    
    if ($selectedVariantId) {
        $variantProduct = Product::find($selectedVariantId);
        $projection = ExternalVariantProjection::where('variant_product_id', $selectedVariantId)->first();
        
        $newSkuId = $projection?->external_sku_id ?? '12000056251539426';
        $newCost = (float) ($variantProduct?->cost ?? 67.70);
        
        $snapshot = $demand->source_snapshot ?? [];
        $snapshot['supplier_sku_id'] = $newSkuId;
        $snapshot['external_sku_id'] = $newSkuId;
        $snapshot['unit_cost'] = $newCost;
        $snapshot['variant_product_id'] = (int) $selectedVariantId;
        
        $demand->update([
            'variant_product_id' => (int) $selectedVariantId,
            'supplier_sku_id' => $newSkuId,
            'source_snapshot' => $snapshot,
        ]);
        
        echo "\n=== AFTER HEALING DEMAND #38 ===\n";
        echo "SKU: " . $demand->supplier_sku_id . "\n";
        echo "Variant ID: " . $demand->variant_product_id . "\n";
        echo "Cost: " . $demand->source_snapshot['unit_cost'] . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/rebuild_and_heal_38_tmp.php', 'w') as f:
    f.write(rebuild_and_heal_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php artisan config:cache && php rebuild_and_heal_38_tmp.php && rm -f rebuild_and_heal_38_tmp.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
