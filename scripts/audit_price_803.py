import sys
import paramiko
import json

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

use App\\Models\\AliExpressProductImport;
use Webkul\\Product\\Models\\Product;

$import = AliExpressProductImport::where('product_id', 3639)->first();
$product = Product::find(3639);

echo "=== PRODUCT 3639 PRICING AUDIT ===\n";
echo "Cost in Product: " . $product->cost . "\n";
echo "Price in Product: " . $product->price . "\n";

if ($import) {
    echo "Import ID: " . $import->id . "\n";
    echo "Base Price in Import: " . $import->base_price . "\n";
    echo "Target Price in Import: " . $import->target_price . "\n";
    echo "Shipping Fee in Import: " . $import->shipping_fee . "\n";
    
    $payload = $import->payload_snapshot ?? [];
    echo "Payload base_price: " . ($payload['base_price'] ?? 'N/A') . "\n";
    echo "Payload variants:\n" . json_encode($payload['variants'] ?? [], JSON_PRETTY_PRINT) . "\n";
}

// Check if 730 * 1.10 = 803
echo "\nMath check:\n";
echo "730.00 * 1.10 = " . (730.00 * 1.10) . "\n";
echo "459.90 * 1.10 = " . (459.90 * 1.10) . "\n";
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/audit_price_803.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php audit_price_803.php && rm -f audit_price_803.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
