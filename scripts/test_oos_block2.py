import remote_ssh_helper as r

client = r.get_ssh_client()

test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\ExternalVariantProjection;
use App\\Services\\AliExpress\\AliExpressLiveStockValidator;
use Webkul\\Product\\Exceptions\\InsufficientProductInventoryException;
use Illuminate\\Support\\Facades\\Cache;

$projections = ExternalVariantProjection::whereIn('variant_product_id', [9142, 9143, 9144, 9145, 9146])->get();
foreach ($projections as $proj) {
    echo "Variant #{$proj->variant_product_id} => ext_prod_id={$proj->external_product_id}, ext_sku_id={$proj->external_sku_id}\\n";
}

$validator = app(AliExpressLiveStockValidator::class);

// Test with child variant 9143
$proj = ExternalVariantProjection::where('variant_product_id', 9143)->first();
if ($proj) {
    $cacheKey = "ae_live_stock_{$proj->external_product_id}_{$proj->external_sku_id}";
    Cache::put($cacheKey, 0, now()->addMinutes(1));

    try {
        $validator->validateLiveStock(9142, [
            'selected_configurable_option' => 9143,
            'quantity' => 1
        ]);
        echo "Child 9143 Result: FAILED TO BLOCK ❌\\n";
    } catch (InsufficientProductInventoryException $e) {
        echo "Child 9143 Result: SUCCESSFULLY BLOCKED ✅\\n";
        echo "Message: " . $e->getMessage() . "\\n";
    } finally {
        Cache::forget($cacheKey);
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_oos_block2.php", "w") as f:
    f.write(test_php)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_oos_block2.php && rm test_oos_block2.php")
print(f"OUT:\n{out}")

client.close()
