import sys
sys.path.insert(0, 'scripts')
import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Services\\AliExpress\\AliExpressLiveStockValidator;
use Webkul\\Product\\Repositories\\ProductRepository;

$validator = app(AliExpressLiveStockValidator::class);
$productRepo = app(ProductRepository::class);

$parent = $productRepo->find(1567);
$child = $productRepo->find(1573);

echo "Parent SKU: " . $parent?->sku . "\\n";
echo "Child SKU: " . $child?->sku . "\\n";
echo "Child AliExpress SKU ID: " . $child?->ae_sku_id . "\\n";

$token = app(App\\Services\\AliExpress\\AliExpressOAuthService::class)->latestToken();
$api = app(App\\Services\\AliExpress\\AliExpressApiClient::class);

$aeProductId = $parent?->ae_product_id ?: (preg_match('/AE_(\\d+)/', $parent?->sku, $m) ? $m[1] : null);
echo "AliExpress Product ID: " . $aeProductId . "\\n";

$res = $api->call('aliexpress.ds.product.get', $token->access_token, [
    'product_id' => $aeProductId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$skuList = $res['aliexpress_ds_product_get_response']['result']['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
echo "Available SKUs count: " . count($skuList) . "\\n";
foreach ($skuList as $sku) {
    echo "  - SKU ID: " . ($sku['sku_id'] ?? '') . " | Stock: " . ($sku['s_k_u_available_stock'] ?? $sku['ipm_sku_stock'] ?? 'unknown') . " | Price: " . ($sku['sku_price'] ?? '') . " | Attr: " . ($sku['sku_attr'] ?? '') . "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_ae_stock_1567.php", "w") as f:
    f.write(php_code)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_ae_stock_1567.php && rm check_ae_stock_1567.php")
print(out)
client.close()
