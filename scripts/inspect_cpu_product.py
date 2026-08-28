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

$aeProdId = '1005010804755442';
$urlKey = 'amd-ryzen-7-9800x3d-8-core-16-thread-47ghz-r7-9800x3d-gaming-cpu-processor-for-b850-b650-motherboard-no-cooler-to-saudi-arabia';

echo "=== 1. SEARCHING PRODUCT IN HAYEST DATABASE ===\n";
$import = AliExpressProductImport::where('aliexpress_product_id', $aeProdId)->first();
$product = null;

if ($import) {
    echo "Found AliExpressProductImport #{$import->id} | Status: {$import->status} | Created: {$import->created_at}\n";
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
    echo "\n--- HAYEST PRODUCT DETAILS (ID: {$product->id}) ---\n";
    echo "Type: {$product->type}\n";
    echo "SKU: {$product->sku}\n";
    echo "Price: {$product->price} USD\n";
    echo "Cost: {$product->cost} USD\n";
    
    $flat = $product->product_flats()->where('locale', 'ar')->first() ?: $product->product_flats()->first();
    if ($flat) {
        echo "Name (AR): {$flat->name}\n";
        echo "URL Key: {$flat->url_key}\n";
        echo "Price in Flat: {$flat->price}\n";
    }
    
    $variants = Product::where('parent_id', $product->id)->get();
    echo "Variants Count: " . $variants->count() . "\n";
    foreach ($variants as $v) {
        $proj = ExternalVariantProjection::where('variant_product_id', $v->id)->first();
        $vFlat = $v->product_flats()->where('locale', 'ar')->first() ?: $v->product_flats()->first();
        echo "  -> Variant ID: {$v->id} | SKU: {$v->sku} | Price: {$v->price} | Cost: {$v->cost} | Ext SKU: " . ($proj?->external_sku_id ?? 'NONE') . " | Name: " . ($vFlat?->name ?? 'N/A') . "\n";
    }
} else {
    echo "Product not found in Hayest DB by AliExpress ID or URL Key.\n";
}

echo "\n=== 2. QUERYING LIVE ALIEXPRESS API (Product {$aeProdId}) ===\n";
$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);
$apiClient = app(AliExpressApiClient::class);

$prodRes = $apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
    'product_id' => $aeProdId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'ar',
]);

$result = $prodRes['body']['aliexpress_ds_product_get_response']['result'] ?? [];

echo "Product ID: " . $aeProdId . "\n";
echo "AliExpress Title: " . ($result['ae_item_base_info_dto']['subject'] ?? 'N/A') . "\n";
echo "Store: " . json_encode($result['ae_store_info'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
echo "has_whole_sale: " . json_encode($result['has_whole_sale'] ?? false) . "\n";

$skus = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($skus['sku_id'])) $skus = [$skus];

echo "\nTotal AliExpress SKUs: " . count($skus) . "\n";
foreach ($skus as $idx => $s) {
    echo "\n--- AE SKU #" . ($idx + 1) . " (ID: {$s['sku_id']}) ---\n";
    echo "  Attributes: " . ($s['sku_attr'] ?? '') . "\n";
    echo "  sku_price (List Price): $" . ($s['sku_price'] ?? 'N/A') . "\n";
    echo "  offer_sale_price (Sale Price): $" . ($s['offer_sale_price'] ?? 'N/A') . "\n";
    echo "  offer_bulk_sale_price (Wholesale): $" . ($s['offer_bulk_sale_price'] ?? 'N/A') . "\n";
    echo "  Stock: " . ($s['sku_available_stock'] ?? 'N/A') . "\n";
}

// Check freight options
$freightRes = $apiClient->call('aliexpress.ds.freight.query', $auth->accessToken, [
    'query_delivery_req' => json_encode([
        'quantity' => 1,
        'ship_to_country' => 'SA',
        'product_id' => $aeProdId,
        'currency' => 'USD',
        'locale' => 'en_US',
    ]),
]);

echo "\n=== 3. LIVE FREIGHT OPTIONS TO SAUDI ARABIA ===\n";
$freightList = $freightRes['body']['aliexpress_ds_freight_query_response']['result']['aeop_freight_calculate_result_for_buyer_d_t_o_list']['aeop_freight_calculate_result_for_buyer_dto'] ?? [];
if (isset($freightList['service_name'])) $freightList = [$freightList];

foreach ($freightList as $f) {
    echo "Service: " . ($f['service_name'] ?? '') . " | Company: " . ($f['company_name'] ?? '') . " | Amount: $" . ($f['freight']['amount'] ?? '0.00') . " | Delivery: " . ($f['estimated_delivery_time'] ?? '') . " days\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_cpu_product.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_cpu_product.php && rm -f inspect_cpu_product.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
