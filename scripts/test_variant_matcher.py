import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressProductImport;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Product\\Models\\Product;
use Illuminate\\Support\\Facades\\DB;

$import = AliExpressProductImport::find(812);
$parentProd = Product::find(9148);

echo "=========================================================\\n";
echo "MATCHING VARIANTS OF PRODUCT 9148 WITH IMPORT 812 SKUS\\n";
echo "=========================================================\\n";

foreach ($parentProd->variants as $v) {
    $attrValues = DB::table('product_attribute_values')
        ->join('attribute_options', 'product_attribute_values.integer_value', '=', 'attribute_options.id')
        ->join('attribute_option_translations', 'attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
        ->where('product_attribute_values.product_id', $v->id)
        ->pluck('attribute_option_translations.label')
        ->toArray();

    $matchedSku = null;
    $matchedStock = null;
    foreach ($import->payload_snapshot['variants'] as $aeV) {
        $opts = array_values($aeV['options_by_axis'] ?? []);
        foreach ($opts as $opt) {
            if (in_array($opt, $attrValues, true)) {
                $matchedSku = $aeV['sku_id'];
                $matchedStock = $aeV['stock'];
                break 2;
            }
        }
    }

    echo "Variant #{$v->id} (SKU: {$v->sku}):\\n";
    echo "  Options in DB: " . implode(', ', $attrValues) . "\\n";
    echo "  Matched AE SKU: " . ($matchedSku ?: 'NONE') . " (Stock: " . ($matchedStock ?? 'N/A') . ")\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_variant_matcher.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_variant_matcher.php && rm test_variant_matcher.php")
print(f"OUT:\n{out}")

client.close()
