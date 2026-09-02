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
use Illuminate\Support\Facades\DB;

$service = app(PriceRecalculationService::class);

echo "Recalculating variant 9136...\\n";
$newPrice = $service->recalculateOne(9136, PricingTrigger::MANUAL);
echo "New calculated selling price: $" . $newPrice . "\\n";

$h = DB::table('higest_calculated_price_histories')->where('variant_id', 9136)->orderByDesc('id')->first();
echo "New History Breakdown: " . json_encode(json_decode($h->calculation_breakdown, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";

$flat = DB::table('product_flat')->where('product_id', 9136)->where('locale', 'ar')->first();
echo "Flat DB Price after recalculation: $" . $flat->price . "\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_recalc_9136.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_recalc_9136.php && rm test_recalc_9136.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
