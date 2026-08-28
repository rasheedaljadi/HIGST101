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

use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);

$apiClient = app(AliExpressApiClient::class);
$response = $apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
    'product_id' => '1005011570307054',
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$skus = $response['body']['aliexpress_ds_product_get_response']['result']['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($skus['sku_id'])) $skus = [$skus];

echo "=== SKUS FOR PRODUCT 1005011570307054 ===\n";
foreach ($skus as $sku) {
    if ((string)($sku['sku_id'] ?? '') === '12000056362620445') {
        echo "MATCHED SKU 12000056362620445:\n";
        echo "SKU Attr: " . ($sku['sku_attr'] ?? '') . "\n";
        echo "Offer Price: " . ($sku['offer_sale_price'] ?? 'N/A') . "\n";
        echo "SKU Price: " . ($sku['sku_price'] ?? 'N/A') . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_sku_shoe.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_sku_shoe.php && rm -f inspect_sku_shoe.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
