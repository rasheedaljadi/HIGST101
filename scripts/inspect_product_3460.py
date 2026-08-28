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

$p3459 = Product::find(3459);
$p3460 = Product::find(3460);

echo "=== PRODUCT 3459 (Parent) ===\n";
echo "Type: " . $p3459->type . "\n";
echo "SKU: " . $p3459->sku . "\n";
echo "Cost: " . $p3459->cost . "\n";
echo "Price: " . $p3459->price . "\n";

echo "\n=== PRODUCT 3460 (Selected Variant: Steel silver / 16GB 256GB) ===\n";
echo "Type: " . $p3460->type . "\n";
echo "SKU: " . $p3460->sku . "\n";
echo "Cost: " . $p3460->cost . "\n";
echo "Price: " . $p3460->price . "\n";
echo "Parent ID: " . $p3460->parent_id . "\n";

echo "\n=== ALL VARIANTS OF 3459 ===\n";
$variants = Product::where('parent_id', 3459)->get();
foreach ($variants as $v) {
    $proj = ExternalVariantProjection::where('variant_product_id', $v->id)->first();
    echo "Variant ID: {$v->id} | SKU: {$v->sku} | Price: {$v->price} | Cost: {$v->cost} | Ext SKU: " . ($proj?->external_sku_id ?? 'NONE') . "\n";
}

echo "\n=== IMPORT RECORD FOR 3459 ===\n";
$import = AliExpressProductImport::where('product_id', 3459)->first();
if ($import) {
    echo "Import ID: " . $import->id . "\n";
    echo "Base Price: " . $import->base_price . "\n";
    echo "Target Price: " . $import->target_price . "\n";
    echo "Shipping Fee: " . $import->shipping_fee . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_3460_tmp.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_3460_tmp.php && rm -f inspect_3460_tmp.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
