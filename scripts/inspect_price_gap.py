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

use App\\Models\\AliExpressProductImport;
use Webkul\\Product\\Models\\Product;
use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$p3087 = Product::find(3087);
$import = AliExpressProductImport::where('product_id', 3087)->first();

echo "=== IMPORT RECORD FOR PRODUCT 3087 ===\n";
if ($import) {
    echo "Import ID: " . $import->id . "\n";
    echo "Created At: " . $import->created_at . "\n";
    echo "Updated At: " . $import->updated_at . "\n";
    echo "AliExpress Product ID: " . $import->aliexpress_product_id . "\n";
    echo "Base Price: " . $import->base_price . "\n";
    echo "Target Price: " . $import->target_price . "\n";
    echo "Shipping Fee: " . $import->shipping_fee . "\n";
    
    $payload = $import->payload_snapshot ?? [];
    echo "Payload currency: " . ($payload['currency'] ?? 'N/A') . "\n";
    
    foreach (($payload['variants'] ?? []) as $v) {
        if (($v['sku_id'] ?? '') === '12000056362620445') {
            echo "\nImport Payload for SKU 12000056362620445:\n";
            echo json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}

// Inspect Variant 3092 in Product table
$v3092 = Product::find(3092);
if ($v3092) {
    echo "\n=== LOCAL VARIANT 3092 ===\n";
    echo "Cost in DB: " . $v3092->cost . "\n";
    echo "Price in DB: " . $v3092->price . "\n";
}

// Query live AliExpress API product details
echo "\n=== QUERY LIVE ALIEXPRESS DS PRODUCT GET ===\n";
$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);
$apiClient = app(AliExpressApiClient::class);

$prodRes = $apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
    'product_id' => '1005011570307054',
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$resp = $prodRes['body']['aliexpress_ds_product_get_response']['result'] ?? [];
$aeSkus = $resp['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($aeSkus['sku_id'])) $aeSkus = [$aeSkus];

foreach ($aeSkus as $s) {
    if (($s['sku_id'] ?? '') === '12000056362620445') {
        echo "Live AliExpress SKU 12000056362620445:\n";
        echo "sku_price (original/retail): " . ($s['sku_price'] ?? 'N/A') . "\n";
        echo "offer_sale_price (promotion): " . ($s['offer_sale_price'] ?? 'N/A') . "\n";
        echo "sku_stock: " . ($s['sku_stock'] ?? 'N/A') . "\n";
        echo "discount: " . ($s['discount'] ?? 'N/A') . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_price_gap.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_price_gap.php && rm -f inspect_price_gap.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
