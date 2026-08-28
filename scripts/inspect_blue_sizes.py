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
use App\\Models\\ExternalVariantProjection;
use Webkul\\Product\\Models\\Product;
use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$aeProdId = '1005008951667859';

$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);
$apiClient = app(AliExpressApiClient::class);

$prodRes = $apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
    'product_id' => $aeProdId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$result = $prodRes['body']['aliexpress_ds_product_get_response']['result'] ?? [];
$skus = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($skus['sku_id'])) $skus = [$skus];

echo "=== BLUE VARIANTS IN ALIEXPRESS ===\n";
foreach ($skus as $s) {
    $attr = $s['sku_attr'] ?? '';
    if (stripos($attr, 'blue') !== false) {
        $skuId = $s['sku_id'];
        $propList = $s['ae_sku_property_dtos']['ae_sku_property_d_t_o'] ?? [];
        if (isset($propList['sku_property_id'])) $propList = [$propList];
        
        $sizeName = 'N/A';
        $colorName = 'N/A';
        foreach ($propList as $p) {
            if ($p['sku_property_name'] === 'Size') {
                $sizeName = $p['property_value_definition_name'] ?? $p['sku_property_value'] ?? 'N/A';
            }
            if ($p['sku_property_name'] === 'Color') {
                $colorName = $p['property_value_definition_name'] ?? $p['sku_property_value'] ?? 'N/A';
            }
        }
        
        $proj = ExternalVariantProjection::where('external_sku_id', $skuId)->first();
        $v = $proj ? Product::find($proj->variant_product_id) : null;
        
        echo "\nSize: [{$sizeName}] | Color: [{$colorName}] | AE SKU: {$skuId}\n";
        echo "  AliExpress List Price (sku_price): $" . $s['sku_price'] . "\n";
        echo "  AliExpress Sale Price (offer_sale_price): $" . $s['offer_sale_price'] . "\n";
        echo "  AliExpress Wholesale Price (offer_bulk_sale_price): $" . $s['offer_bulk_sale_price'] . "\n";
        echo "  AliExpress Stock: " . $s['sku_available_stock'] . "\n";
        
        if ($v) {
            echo "  -> Hayest Variant ID: #{$v->id}\n";
            echo "  -> Hayest SKU: {$v->sku}\n";
            echo "  -> Hayest Cost: $" . $v->cost . "\n";
            echo "  -> Hayest Regular Price (Struck-through): $" . $v->price . "\n";
            echo "  -> Hayest Special Price (Actual Selling Price): $" . $v->special_price . "\n";
            echo "  -> Profit Amount: $" . round($v->special_price - $v->cost, 2) . " (" . round((($v->special_price - $v->cost)/$v->cost)*100, 1) . "%)\n";
        } else {
            echo "  -> No Hayest variant mapped directly for this SKU.\n";
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_blue_sizes.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_blue_sizes.php && rm -f inspect_blue_sizes.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
