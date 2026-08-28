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
use Illuminate\Support\Facades\DB;

$recalculator = app(PriceRecalculationService::class);

echo "=========================================================\\n";
echo "TEST RECALCULATE ONE (PRODUCT 316 - NON-CHOICE WITH $5 SHIPPING):\\n";
echo "=========================================================\\n";
$flatBefore = DB::table('product_flat')->where('product_id', 316)->where('channel', 'default')->where('locale', 'ar')->first();
echo "Before Recalculate - Price in Flat: $" . $flatBefore->price . "\\n";

$recalculatedPrice = $recalculator->recalculateOne(316, PricingTrigger::MANUAL);
$flatAfter = DB::table('product_flat')->where('product_id', 316)->where('channel', 'default')->where('locale', 'ar')->first();
echo "After Recalculate - Price returned: $" . $recalculatedPrice . " | Flat price in DB: $" . $flatAfter->price . "\\n";

echo "\\n=========================================================\\n";
echo "TEST RECALCULATE ONE (PRODUCT 1 - CHOICE PRODUCT WITH $5 SHIPPING SOURCE):\\n";
echo "=========================================================\\n";
$flatChoiceBefore = DB::table('product_flat')->where('product_id', 1)->where('channel', 'default')->where('locale', 'ar')->first();
echo "Before Recalculate - Price in Flat: $" . $flatChoiceBefore->price . "\\n";

$recalculatedChoicePrice = $recalculator->recalculateOne(1, PricingTrigger::MANUAL);
$flatChoiceAfter = DB::table('product_flat')->where('product_id', 1)->where('channel', 'default')->where('locale', 'ar')->first();
echo "After Recalculate - Price returned: $" . $recalculatedChoicePrice . " | Flat price in DB: $" . $flatChoiceAfter->price . "\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_pricing_single.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_pricing_single.php && rm test_pricing_single.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
