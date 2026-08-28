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
use Webkul\\Product\\Models\\ProductFlat;
use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;

$aeProdId = '1005008951667859';
$urlKey = 'womens-gray-sports-high-waist-hanging-loose-slimming-straight-leg-wide-leg-pants-american-sweatpants';

echo "=== 1. SEARCHING PRODUCT IN HAYEST DATABASE ===\n";
$import = AliExpressProductImport::where('aliexpress_product_id', $aeProdId)->first();
$product = null;

if ($import) {
    echo "Found Import #{$import->id} | Product ID: {$import->product_id} | Created: {$import->created_at}\n";
    $product = Product::find($import->product_id);
}

if (!$product) {
    $flat = ProductFlat::where('url_key', $urlKey)->first();
    if ($flat) {
        $product = Product::find($flat->product_id);
        echo "Found Product via Flat url_key: Product ID #{$product->id}\n";
    }
}

if ($product) {
    echo "\n--- HAYEST PARENT PRODUCT (ID: {$product->id}) ---\n";
    echo "Type: {$product->type}\n";
    echo "SKU: {$product->sku}\n";
    echo "Cost: {$product->cost}\n";
    echo "Price: {$product->price}\n";
    echo "Special Price: " . ($product->special_price ?? 'None') . "\n";
    
    $variants = Product::where('parent_id', $product->id)->get();
    echo "\nTotal Variants in Hayest: " . $variants->count() . "\n";
    
    foreach ($variants as $v) {
        $proj = ExternalVariantProjection::where('variant_product_id', $v->id)->first();
        $flat = $v->product_flats()->where('locale', 'ar')->first() ?: $v->product_flats()->first();
        
        // Check attributes/options
        $options = [];
        foreach ($v->attribute_values as $val) {
            $attr = $val->attribute;
            if ($attr && in_array($attr->code, ['color', 'size', 'ae_color', 'ae_size'])) {
                $opt = $val->attribute_option;
                $optLabel = $opt ? $opt->admin_name : $val->text_value;
                $options[$attr->code] = $optLabel;
            }
        }
        
        echo "  [Variant #{$v->id}] SKU: {$v->sku} | Cost: {$v->cost} | Price: {$v->price} | Special Price: {$v->special_price} | Ext SKU: " . ($proj?->external_sku_id ?? 'NONE') . " | Opts: " . json_encode($options, JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "Product not found in Hayest DB.\n";
}

echo "\n=== 2. QUERYING LIVE ALIEXPRESS API (Product {$aeProdId}) ===\n";
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
echo "AliExpress Title: " . ($result['ae_item_base_info_dto']['subject'] ?? 'N/A') . "\n";
echo "Store: " . json_encode($result['ae_store_info'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
echo "has_whole_sale: " . json_encode($result['has_whole_sale'] ?? false) . "\n";

$skus = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($skus['sku_id'])) $skus = [$skus];

echo "\nTotal AliExpress SKUs: " . count($skus) . "\n";
echo "\n--- FILTERED SKUS FOR COLOR: BLUE (or all if BLUE in attr) ---\n";
foreach ($skus as $idx => $s) {
    $attr = $s['sku_attr'] ?? '';
    // Show all or highlight BLUE
    $isBlue = stripos($attr, 'blue') !== false || stripos($attr, 'أزرق') !== false;
    echo "SKU #" . ($idx + 1) . " (ID: {$s['sku_id']}) " . ($isBlue ? "[★ BLUE]" : "") . "\n";
    echo "  Attributes: " . $attr . "\n";
    echo "  sku_price (List Price): $" . ($s['sku_price'] ?? 'N/A') . "\n";
    echo "  offer_sale_price (Sale Price): $" . ($s['offer_sale_price'] ?? 'N/A') . "\n";
    echo "  offer_bulk_sale_price (Wholesale): $" . ($s['offer_bulk_sale_price'] ?? 'N/A') . "\n";
    echo "  Stock: " . ($s['sku_available_stock'] ?? 'N/A') . "\n";
}

// Freight
$freightReq = [
    'productId' => $aeProdId,
    'shipToCountry' => 'SA',
    'quantity' => 1,
    'currency' => 'USD',
    'language' => 'en_US',
    'locale' => 'en_US',
];

$freightRes = $apiClient->call('aliexpress.ds.freight.query', $auth->accessToken, [
    'queryDeliveryReq' => $freightReq,
]);

$deliveryOptions = $freightRes['body']['aliexpress_ds_freight_query_response']['result']['delivery_options']['delivery_option_d_t_o'] ?? [];
if (isset($deliveryOptions['code'])) $deliveryOptions = [$deliveryOptions];

echo "\n=== 3. FREIGHT OPTIONS TO SAUDI ARABIA ===\n";
foreach ($deliveryOptions as $d) {
    echo "Service: " . ($d['code'] ?? '') . " | Company: " . ($d['company'] ?? '') . " | Free Shipping: " . ($d['free_shipping'] ? 'YES' : 'NO') . " | Freight: $" . ($d['freight']['amount'] ?? '0.00') . " | Days: " . ($d['min_delivery_days'] ?? '') . "-" . ($d['max_delivery_days'] ?? '') . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_blue_pants.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_blue_pants.php && rm -f inspect_blue_pants.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
