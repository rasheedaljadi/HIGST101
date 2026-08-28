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

$pId = '1005006903383533';

$res = $apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
    'product_id' => $pId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'ar',
]);

$result = $res['body']['aliexpress_ds_product_get_response']['result'] ?? [];

echo "=== PRODUCT DETAILS ===\n";
echo "Product ID: " . $pId . "\n";
echo "Title: " . ($result['ae_item_base_info_dto']['subject'] ?? 'N/A') . "\n";
echo "Store: " . json_encode($result['ae_store_info'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
echo "has_whole_sale: " . json_encode($result['has_whole_sale'] ?? false) . "\n";

$skus = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($skus['sku_id'])) $skus = [$skus];

echo "\n=== ALL SKUS AND PRICING (Total: " . count($skus) . ") ===\n";
foreach ($skus as $idx => $s) {
    echo "--- SKU #" . ($idx + 1) . " (ID: {$s['sku_id']}) ---\n";
    echo "  Attributes: " . ($s['sku_attr'] ?? '') . "\n";
    echo "  sku_price (List Price): $" . ($s['sku_price'] ?? 'N/A') . "\n";
    echo "  offer_sale_price (Sale Price): $" . ($s['offer_sale_price'] ?? 'N/A') . "\n";
    echo "  offer_bulk_sale_price (Wholesale): $" . ($s['offer_bulk_sale_price'] ?? 'N/A') . "\n";
    echo "  sku_bulk_order (Min Qty): " . ($s['sku_bulk_order'] ?? 'N/A') . "\n";
    echo "  Stock: " . ($s['sku_available_stock'] ?? 'N/A') . "\n";
}

// Check freight options to Saudi Arabia
$freightRes = $apiClient->call('aliexpress.ds.freight.query', $auth->accessToken, [
    'query_delivery_req' => json_encode([
        'quantity' => 1,
        'ship_to_country' => 'SA',
        'product_id' => $pId,
        'currency' => 'USD',
        'locale' => 'en_US',
    ]),
]);

echo "\n=== FREIGHT / SHIPPING OPTIONS TO SAUDI ARABIA ===\n";
$freightList = $freightRes['body']['aliexpress_ds_freight_query_response']['result']['aeop_freight_calculate_result_for_buyer_d_t_o_list']['aeop_freight_calculate_result_for_buyer_dto'] ?? [];
if (isset($freightList['service_name'])) $freightList = [$freightList];

foreach ($freightList as $f) {
    echo "Service: " . ($f['service_name'] ?? '') . " | Company: " . ($f['company_name'] ?? '') . " | Amount: $" . ($f['freight']['amount'] ?? '0.00') . " | Delivery: " . ($f['estimated_delivery_time'] ?? '') . " days\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_product_1005006903383533.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_product_1005006903383533.php && rm -f inspect_product_1005006903383533.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
