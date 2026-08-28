import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressProductImport;
use App\Models\AliExpressSetting;
use App\Models\HigestSourceOffer;
use App\Services\Pricing\PriceRecalculationService;
use App\Enums\PricingTrigger;
use Webkul\Product\Models\ProductFlat;
use Spatie\ResponseCache\Facades\ResponseCache;

$recalculator = app(PriceRecalculationService::class);
$productId = 8763;

echo "Recalculating product {$productId}...\\n";
$newPrice = $recalculator->recalculateOne($productId, PricingTrigger::MANUAL);
echo "Recalculate returned selling price: $" . $newPrice . "\\n";

$flat = ProductFlat::where('product_id', $productId)->first();
echo "New Price in Flat DB: $" . $flat->price . "\\n";
echo "New Special Price in Flat DB: $" . $flat->special_price . "\\n";

if (class_exists(ResponseCache::class)) {
    ResponseCache::clear();
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/recalc_8763.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 recalc_8763.php && rm recalc_8763.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
