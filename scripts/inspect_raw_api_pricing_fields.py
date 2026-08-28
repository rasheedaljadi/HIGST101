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

$productIds = ['1005011570307054', '1005011735920938', '1005010322232579'];

foreach ($productIds as $pId) {
    echo "====================================================\n";
    echo "=== FULL API INSPECTION FOR PRODUCT: {$pId} ===\n";
    echo "====================================================\n";
    
    $res = $apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
        'product_id' => $pId,
        'ship_to_country' => 'SA',
        'target_currency' => 'USD',
        'target_language' => 'en',
    ]);
    
    $result = $res['body']['aliexpress_ds_product_get_response']['result'] ?? [];
    
    echo "Top Level Keys: " . json_encode(array_keys($result)) . "\n\n";
    
    // Check general pricing / promotion fields
    if (isset($result['ae_item_base_info_dto'])) {
        echo "Base Info DTO:\n" . json_encode($result['ae_item_base_info_dto'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
    
    // Check SKU list and all their pricing/discount/activity fields
    $skus = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
    if (isset($skus['sku_id'])) $skus = [$skus];
    
    echo "Total SKUs: " . count($skus) . "\n";
    echo "SKU #1 Full Details:\n" . json_encode($skus[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_raw_api_pricing_fields.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_raw_api_pricing_fields.php && rm -f inspect_raw_api_pricing_fields.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
