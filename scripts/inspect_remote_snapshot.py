import sys
import os
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
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$aeId = '1005011735920938';
$import = App\\Models\\AliExpressProductImport::where('aliexpress_product_id', $aeId)->first();
$snapshot = is_array($import->payload_snapshot) ? $import->payload_snapshot : json_decode($import->payload_snapshot, true);

echo "=== SNAPSHOT VARIANTS ===\\n";
foreach ($snapshot['variants'] as $idx => $v) {
    $sku = $v['sku_id'] ?? $v['skuId'] ?? '';
    $price = $v['price'] ?? 0;
    $opts = json_encode($v['options_by_axis'] ?? [], JSON_UNESCAPED_UNICODE);
    echo "Index {$idx} | SKU: {$sku} | Price: {$price} | Options: {$opts}\\n";
}

echo "\\n=== API LIVE FETCH MAPPING ===\\n";
$token = App\\Models\\AliExpressToken::latest()->first();
$apiClient = app(App\\Services\\AliExpress\\AliExpressApiClient::class);
$mapper = app(App\\Services\\AliExpress\\AliExpressProductMapper::class);

$result = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
    'product_id' => $aeId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$dto = $mapper->map($result['body'], $aeId);
echo "DTO Variants count: " . count($dto->variants) . "\\n";
foreach ($dto->variants as $idx => $v) {
    $opts = json_encode($v->optionsByAxis, JSON_UNESCAPED_UNICODE);
    echo "DTO [{$idx}] SKU: {$v->skuId} | Price: {$v->price} | Original: {$v->originalPrice} | Options: {$opts}\\n";
}
"""

with sftp.open(f"{APP_DIR}/inspect_snapshot_deep.php", 'w') as f:
    f.write(php_script)

sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_snapshot_deep.php && rm inspect_snapshot_deep.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.close()
