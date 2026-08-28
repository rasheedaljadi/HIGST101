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

# Sync all modified files to remote server
files_to_sync = [
    ('packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php', 'packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php'),
    ('packages/Webkul/Procurement/src/Services/ProcurementDemandService.php', 'packages/Webkul/Procurement/src/Services/ProcurementDemandService.php'),
    ('packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php', 'packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php'),
    ('packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php', 'packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php'),
    ('packages/Webkul/Procurement/src/Config/procurement.php', 'packages/Webkul/Procurement/src/Config/procurement.php'),
    ('packages/Webkul/Procurement/src/Providers/ProcurementServiceProvider.php', 'packages/Webkul/Procurement/src/Providers/ProcurementServiceProvider.php'),
    ('packages/Webkul/Procurement/src/Console/Commands/BackfillDemandSkuIds.php', 'packages/Webkul/Procurement/src/Console/Commands/BackfillDemandSkuIds.php'),
]

for src, dst in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, src.replace('/', os.sep))
    remote_path = f"{APP_DIR}/{dst}"
    print(f"Uploading {src} -> {remote_path}...")
    sftp.put(local_path, remote_path)

sftp.close()

# Also let's run heal/recalculate for Demand #37 and check
heal_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Sales\\Models\\OrderItem;
use Webkul\\Product\\Models\\Product;
use App\\Models\\ExternalVariantProjection;

$demand = ProcurementDemand::find(37);
if ($demand) {
    echo "=== BEFORE HEALING DEMAND #37 ===\n";
    echo "SKU: " . $demand->supplier_sku_id . "\n";
    echo "Variant ID: " . $demand->variant_product_id . "\n";
    echo "Cost: " . ($demand->source_snapshot['unit_cost'] ?? 'N/A') . "\n";

    $orderItem = OrderItem::find($demand->order_item_id);
    $selectedVariantId = $orderItem->additional['selected_configurable_option'] ?? null;
    
    if ($selectedVariantId) {
        $variantProduct = Product::find($selectedVariantId);
        $projection = ExternalVariantProjection::where('variant_product_id', $selectedVariantId)->first();
        
        $newSkuId = $projection?->external_sku_id ?? $demand->supplier_sku_id;
        $newCost = (float) ($variantProduct?->cost ?? $demand->source_snapshot['unit_cost']);
        
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
        
        echo "\n=== AFTER HEALING DEMAND #37 ===\n";
        echo "SKU: " . $demand->supplier_sku_id . "\n";
        echo "Variant ID: " . $demand->variant_product_id . "\n";
        echo "Cost: " . $demand->source_snapshot['unit_cost'] . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/heal_37_tmp.php', 'w') as f:
    f.write(heal_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php artisan config:cache && php heal_37_tmp.php && rm -f heal_37_tmp.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
