import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use App\Models\AliExpressToken;
use Webkul\Product\Models\Product;

$latestToken = AliExpressToken::latest()->first();
$apiClient = app(AliExpressApiClient::class);

echo "Token ID: {$latestToken->id}, User: {$latestToken->account}\n";
echo "Access Token: " . substr($latestToken->access_token, 0, 15) . "...\n";

// Let's find an imported product from AliExpress
$product = Product::whereNotNull('additional')
    ->where('additional->aliexpress_product_id', '!=', null)
    ->first();

if (! $product) {
    // Search in DB directly
    $row = \DB::table('products')->where('additional', 'like', '%aliexpress_product_id%')->first();
    $aeProdId = $row ? json_decode($row->additional, true)['aliexpress_product_id'] ?? '1005006903383533' : '1005006903383533';
} else {
    $aeProdId = $product->additional['aliexpress_product_id'] ?? '1005006903383533';
}

echo "Testing API call for AliExpress Product ID: {$aeProdId}...\n";

$res = $apiClient->call('aliexpress.ds.product.get', $latestToken->access_token, [
    'product_id' => (string) $aeProdId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

echo "API Call OK: " . ($res['ok'] ? 'YES' : 'NO') . "\n";
echo "Status: " . $res['status'] . "\n";
echo "Code: " . ($res['code'] ?? 'NONE') . "\n";
echo "Message: " . ($res['message'] ?? 'NONE') . "\n";
if (! empty($res['body']['aliexpress_ds_product_get_response']['result'])) {
    $title = $res['body']['aliexpress_ds_product_get_response']['result']['ae_item_base_info_dto']['subject'] ?? 'No Title';
    echo "Product Title: " . substr($title, 0, 60) . "...\n";
} else {
    echo "Body:\n";
    print_r($res['body']);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_live_ae_api.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_live_ae_api.php && rm test_live_ae_api.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
