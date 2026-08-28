import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use Webkul\Procurement\Services\AliExpressAuthorizationResolver;

$client = app(AliExpressApiClient::class);
$authResolver = app(AliExpressAuthorizationResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission();
$token = $auth->accessToken;

echo "Calling product.get for 1005011942764190...\n";
$res = $client->call('aliexpress.ds.product.get', $token, [
    'product_id' => '1005011942764190',
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

echo "OK: " . ($res['ok'] ? 'yes' : 'no') . "\n";
if (!empty($res['body'])) {
    $variants = data_get($res['body'], 'aliexpress_ds_product_get_response.result.ae_item_sku_info_dtos.ae_item_sku_info_d_t_o', []);
    if (isset($variants['sku_id'])) $variants = [$variants];
    echo "Variants count from API: " . count($variants) . "\n";
    foreach ($variants as $v) {
        echo "SKU ID: " . ($v['sku_id'] ?? '') . ", sku_attr: " . ($v['sku_attr'] ?? '') . ", price: " . ($v['offer_sale_price'] ?? $v['sku_price'] ?? '') . "\n";
    }
}
if (!empty($res['message'])) {
    echo "Message: " . $res['message'] . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_get_sku_attr.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_get_sku_attr.php && rm test_get_sku_attr.php")
print(f"OUTPUT:\n{out}")

client.close()
