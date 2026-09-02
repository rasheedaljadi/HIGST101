import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;

$oauth = app(AliExpressOAuthService::class);
$client = app(AliExpressApiClient::class);
$token = $oauth->latestToken();

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

echo "Live SKUs in API response:\\n";
foreach ($skuList as $sku) {
    echo "SKU ID: " . $sku['sku_id'] . " => ipm_sku_stock=" . ($sku['ipm_sku_stock'] ?? 'null') . ", sku_stock=" . ($sku['sku_stock'] ?? 'null') . ", stock=" . ($sku['stock'] ?? 'null') . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/debug_sku_keys.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 debug_sku_keys.php && rm debug_sku_keys.php")
print(f"OUT:\n{out}")

client.close()
