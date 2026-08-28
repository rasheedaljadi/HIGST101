import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\Product\Models\Product;
use Webkul\Shop\Transformers\ProductPDPTransformer;
use App\Services\Pricing\PriceRecalculationService;
use App\Enums\PricingTrigger;
use Illuminate\Support\Facades\DB;

$product = Product::find(8763);
echo "Product 8763 SKU: {$product->sku}\\n";
echo "Product 8763 updated_at: {$product->updated_at}\\n";

$lastPricingUpdate = cache()->get('catalog_pricing_last_updated_at');
echo "cache catalog_pricing_last_updated_at: " . date('Y-m-d H:i:s', $lastPricingUpdate) . " ({$lastPricingUpdate})\\n";
echo "Comparison: product->updated_at->timestamp (" . $product->updated_at->timestamp . ") < lastPricingUpdate (" . $lastPricingUpdate . "): " . ($product->updated_at->timestamp < $lastPricingUpdate ? 'YES' : 'NO') . "\\n";

$transformer = app(ProductPDPTransformer::class);
$data = $transformer->transform($product);
echo "Transformed price: " . var_export($data['prices'] ?? 'N/A', true) . "\\n";

$flat = DB::table('product_flat')->where('product_id', 8763)->first();
echo "Flat price in DB after transform: $" . $flat->price . " / Special: $" . $flat->special_price . "\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_transform.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_transform.php && rm test_transform.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
