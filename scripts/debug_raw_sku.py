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

echo "First raw SKU item:\\n";
print_r($skuList[0] ?? []);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/debug_raw_sku.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 debug_raw_sku.php && rm debug_raw_sku.php")
print(f"OUT:\n{out}")

client.close()
