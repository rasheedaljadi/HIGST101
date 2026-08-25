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

$token = App\\Models\\AliExpressToken::latest()->first();
$apiClient = app(App\\Services\\AliExpress\\AliExpressApiClient::class);

$result = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
    'product_id' => '1005011735920938',
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$body = $result['body'] ?? [];
$res = $body['aliexpress_ds_product_get_response']['result'] ?? [];

echo "=== TOP-LEVEL KEYS IN RESULT ===\\n";
echo json_encode(array_keys($res), JSON_PRETTY_PRINT) . "\\n";

echo "\\n=== SAMPLE SKU KEYS & VALUES ===\\n";
$skus = $res['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (!empty($skus[0])) {
    echo json_encode($skus[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";
}

echo "\\n=== SEARCH FOR TAX / VAT / DUTY / RATE IN ENTIRE RESPONSE ===\\n";
function findKeys($arr, $path = '') {
    $found = [];
    foreach ($arr as $k => $v) {
        $currentPath = $path ? "$path.$k" : $k;
        if (preg_match('/(tax|vat|duty|rate|fee|price|discount)/i', (string)$k)) {
            $found[$currentPath] = is_array($v) ? ('[Array count ' . count($v) . ']') : $v;
        }
        if (is_array($v)) {
            $sub = findKeys($v, $currentPath);
            $found = array_merge($found, $sub);
        }
    }
    return $found;
}

$allMatches = findKeys($body);
echo json_encode($allMatches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";
"""

with sftp.open(f"{APP_DIR}/inspect_tax_fields.php", 'w') as f:
    f.write(php_script)

sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_tax_fields.php && rm inspect_tax_fields.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.close()
