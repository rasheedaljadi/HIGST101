import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Services\Pricing\PriceRecalculationService;
use App\Enums\PricingTrigger;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Spatie\\ResponseCache\\Facades\\ResponseCache;

$service = app(PriceRecalculationService::class);

$product = Product::with(['variants'])->find(9135);
echo "Recalculating all variants for product 9135...\\n";
foreach ($product->variants as $v) {
    $price = $service->recalculateOne($v->id, PricingTrigger::MANUAL);
    $flat = DB::table('product_flat')->where('product_id', $v->id)->where('locale', 'ar')->first();
    echo "  - Variant {$v->id} (SKU: {$v->sku}): New Price = $" . $flat->price . "\\n";
}

// Clear caches
Artisan::call('cache:clear');
if (class_exists(ResponseCache::class)) {
    ResponseCache::clear();
}
echo "Catalog caches cleared successfully!\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/sync_9135_prices.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 sync_9135_prices.php && rm sync_9135_prices.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
