import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressProductImport;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;

$oauth = app(AliExpressOAuthService::class);
$client = app(AliExpressApiClient::class);
$token = $oauth->latestToken();

$import = AliExpressProductImport::find(812);
echo "Import #812 Data:\\n";
echo "AliExpress Product ID: " . $import->aliexpress_product_id . "\\n";
echo "Import payload variants:\\n";
print_r($import->payload_snapshot['variants'] ?? []);

echo "\\n=========================================================\\n";
echo "LIVE ALIEXPRESS API RESPONSE FOR 1005010544368430\\n";
echo "=========================================================\\n";
$res = $client->call('aliexpress.ds.product.get', $token->access_token, [
    'product_id' => '1005010544368430',
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$body = $res['body']['aliexpress_ds_product_get_response'] ?? $res['body'];
$skuList = $body['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o']
    ?? $body['result']['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o']
    ?? [];

if (isset($skuList['sku_id'])) {
    $skuList = [$skuList];
}

echo "Live SKUs count from AliExpress: " . count($skuList) . "\\n";
foreach ($skuList as $sku) {
    echo "SKU ID: " . ($sku['sku_id'] ?? 'N/A') . ", Stock: " . ($sku['ipm_sku_stock'] ?? $sku['sku_stock'] ?? $sku['stock'] ?? '0') . ", Attrs: " . ($sku['ae_sku_property_dtos'] ?? $sku['sku_attr'] ?? 'N/A') . "\\n";
    if (isset($sku['ae_sku_property_dtos']['ae_sku_property_d_t_o'])) {
        $props = $sku['ae_sku_property_dtos']['ae_sku_property_d_t_o'];
        if (isset($props['sku_property_id'])) $props = [$props];
        foreach ($props as $p) {
            echo "   Prop: " . ($p['sku_property_name'] ?? '') . " => " . ($p['property_value_definition_name'] ?? $p['sku_property_value'] ?? '') . "\\n";
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_mp3_live_api.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_mp3_live_api.php && rm inspect_mp3_live_api.php")
print(f"OUT:\n{out}")

client.close()
