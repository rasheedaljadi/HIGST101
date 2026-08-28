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
use Webkul\Product\Models\ProductFlat;
use Illuminate\Support\Facades\DB;

$settings = AliExpressSetting::first();
echo "=========================================================\\n";
echo "1. CURRENT SETTINGS IN DB:\\n";
echo "=========================================================\\n";
echo "include_shipping_in_price: " . ($settings->include_shipping_in_price ? 'TRUE (Enabled)' : 'FALSE (Disabled)') . "\\n";
echo "exclude_choice_from_shipping_price: " . ($settings->exclude_choice_from_shipping_price ? 'TRUE' : 'FALSE') . "\\n";
echo "updated_at: " . $settings->updated_at . "\\n";

echo "\\n=========================================================\\n";
echo "2. PRODUCT 8763 (Feelworld) CURRENT DB STATE:\\n";
echo "=========================================================\\n";
$flat = ProductFlat::where('product_id', 8763)->first();
echo "Flat price in DB: $" . ($flat?->price ?? 'None') . "\\n";
echo "Flat special_price in DB: $" . ($flat?->special_price ?? 'None') . "\\n";

$priceAttrId = DB::table('attributes')->where('code', 'price')->value('id');
$specialPriceAttrId = DB::table('attributes')->where('code', 'special_price')->value('id');
$eavPrice = DB::table('product_attribute_values')->where('product_id', 8763)->where('attribute_id', $priceAttrId)->value('float_value');
$eavSpecial = DB::table('product_attribute_values')->where('product_id', 8763)->where('attribute_id', $specialPriceAttrId)->value('float_value');
echo "EAV Price in DB: $" . ($eavPrice ?? 'None') . "\\n";
echo "EAV Special Price in DB: $" . ($eavSpecial ?? 'None') . "\\n";

echo "\\n=========================================================\\n";
echo "3. QUEUE STATUS:\\n";
echo "=========================================================\\n";
$jobsCount = DB::table('jobs')->count();
echo "Total jobs in jobs table: " . $jobsCount . "\\n";

$recalcJobs = DB::table('jobs')->where('payload', 'like', '%RecalculateCatalogPricesJob%')->get();
echo "Recalculate jobs count in queue: " . $recalcJobs->count() . "\\n";
foreach ($recalcJobs as $j) {
    echo "  - Job ID: {$j->id}, Queue: {$j->queue}, Attempts: {$j->attempts}, Available At: " . date('Y-m-d H:i:s', $j->available_at) . "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/deep_diagnose.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 deep_diagnose.php && rm deep_diagnose.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
