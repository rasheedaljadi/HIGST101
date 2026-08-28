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

use Webkul\\Product\\Models\\Product;
use App\\Models\\ExternalVariantProjection;
use App\\Models\\AliExpressProductImport;

$p3625 = Product::find(3625);
echo "Parent 3625 SKU: " . $p3625->sku . ", Cost: " . $p3625->cost . ", Price: " . $p3625->price . "\n";

$variants = Product::where('parent_id', 3625)->get();
echo "Total Variants: " . $variants->count() . "\n";
foreach ($variants as $v) {
    $proj = ExternalVariantProjection::where('variant_product_id', $v->id)->first();
    echo "Variant ID: {$v->id} | SKU: {$v->sku} | Cost: {$v->cost} | Price: {$v->price} | Ext SKU: " . ($proj?->external_sku_id ?? 'NONE') . "\n";
}

$import = AliExpressProductImport::where('product_id', 3625)->first();
if ($import) {
    echo "\nImport #{$import->id} AliExpress Product ID: " . $import->aliexpress_product_id . "\n";
    $payload = $import->payload_snapshot ?? [];
    $skus = $payload['skus'] ?? $payload['sku_list'] ?? $payload['ae_item_sku_info_dtos'] ?? [];
    echo "Payload SKUs count / structure: " . json_encode(array_keys($payload)) . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_3625_variants_tmp.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_3625_variants_tmp.php && rm -f inspect_3625_variants_tmp.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
