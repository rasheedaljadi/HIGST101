import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

$products = DB::table('products')
    ->where('additional', 'like', '%aliexpress_product_id%')
    ->take(5)
    ->get();

echo "Found " . $products->count() . " AliExpress products in DB:\n";
foreach ($products as $p) {
    $add = json_decode($p->additional, true) ?? [];
    echo "Product ID: {$p->id}, SKU: {$p->sku}, Type: {$p->type}\n";
    echo "  AE Product ID: " . ($add['aliexpress_product_id'] ?? 'NONE') . "\n";
    echo "  AE SKU ID: " . ($add['aliexpress_sku_id'] ?? $add['ae_sku_id'] ?? 'NONE') . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/find_ae_products.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 find_ae_products.php && rm find_ae_products.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
