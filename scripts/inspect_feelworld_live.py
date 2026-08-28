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
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductFlat;
use Illuminate\Support\Facades\DB;

$urlKey = 'feelworld-lut7-7-inch-2200nit-touchscreen-4k-hdmi-camera-field-monitor-with-3d-lut-waveform-automatic-light-sensor-1920x1200';
$flat = ProductFlat::where('url_key', $urlKey)->first();

if (!$flat) {
    echo "Product not found by exact url_key, searching like...\\n";
    $flat = ProductFlat::where('url_key', 'like', '%feelworld-lut7%')->first();
}

if (!$flat) {
    echo "Product not found!\\n";
    exit(1);
}

$productId = $flat->product_id;
$product = Product::with(['variants', 'attribute_values'])->find($productId);
$import = AliExpressProductImport::where('product_id', $productId)->first();
$offers = HigestSourceOffer::where('product_id', $productId)->get();
$settings = AliExpressSetting::first();

echo "=========================================================\\n";
echo "PRODUCT DETAILS:\\n";
echo "=========================================================\\n";
echo "Product ID: {$productId}\\n";
echo "SKU: {$product->sku}\\n";
echo "Name: {$flat->name}\\n";
echo "Type: {$product->type}\\n";
echo "Price in Flat: {$flat->price}\\n";
echo "Special Price in Flat: {$flat->special_price}\\n";
echo "Base Shipping Cost in Import: {$import?->base_shipping_cost} {$import?->shipping_currency} ({$import?->shipping_company})\\n";
echo "Is Choice: " . ($import?->isChoice() ? 'YES' : 'NO') . "\\n";

echo "\\nSettings in DB:\\n";
echo "  include_shipping_in_price: " . ($settings->include_shipping_in_price ? 'true' : 'false') . "\\n";
echo "  exclude_choice_from_shipping_price: " . ($settings->exclude_choice_from_shipping_price ? 'true' : 'false') . "\\n";
echo "  shipping_enabled: " . ($settings->shipping_enabled ? 'true' : 'false') . "\\n";

echo "\\nVariants & Offers:\\n";
foreach ($product->variants as $v) {
    $vFlat = ProductFlat::where('product_id', $v->id)->first();
    $vOffer = HigestSourceOffer::where('variant_id', $v->id)->first();
    echo "  Variant #{$v->id} | SKU: {$v->sku} | Flat Price: {$vFlat?->price} | Acq Cost: {$vOffer?->acquisition_cost} | Acq Orig Cost: {$vOffer?->acquisition_original_cost}\\n";
}

"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_feelworld.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_feelworld.php && rm inspect_feelworld.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
