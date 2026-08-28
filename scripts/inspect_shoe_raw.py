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

use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);
$apiClient = app(AliExpressApiClient::class);

$pId = '1005011570307054';

$res = $apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
    'product_id' => $pId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$result = $res['body']['aliexpress_ds_product_get_response']['result'] ?? [];
$skus = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($skus['sku_id'])) $skus = [$skus];

echo "Product ID: " . $pId . "\n";
echo "has_whole_sale: " . json_encode($result['has_whole_sale'] ?? 'N/A') . "\n";
echo "Base Info: " . json_encode($result['ae_item_base_info_dto'] ?? [], JSON_PRETTY_PRINT) . "\n";

foreach ($skus as $s) {
    if (($s['sku_id'] ?? '') === '12000056362620445') {
        echo "\nTarget SKU (12000056362620445) Full Data:\n";
        echo json_encode($s, JSON_PRETTY_PRINT) . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_shoe_raw.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_shoe_raw.php && rm -f inspect_shoe_raw.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
