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

$projections = ExternalVariantProjection::latest('id')->limit(5)->get();
foreach ($projections as $proj) {
    echo "Proj ID: {$proj->id}, variant_product_id: {$proj->variant_product_id}, ext_prod_id: {$proj->external_product_id}, ext_sku: {$proj->external_sku_id}\\n";
}

$proj = $projections->first();
if ($proj) {
    $validator = app(AliExpressLiveStockValidator::class);
    $cacheKey = "ae_live_stock_{$proj->external_product_id}_{$proj->external_sku_id}";
    Cache::put($cacheKey, 0, now()->addMinutes(1));

    try {
        $validator->validateLiveStock($proj->variant_product_id, [
            'selected_configurable_option' => $proj->variant_product_id,
            'quantity' => 1
        ]);
        echo "Blocking test: FAILED ❌\\n";
    } catch (InsufficientProductInventoryException $e) {
        echo "Blocking test: BLOCKED SUCCESSFULLY ✅ => " . $e->getMessage() . "\\n";
    } finally {
        Cache::forget($cacheKey);
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_proj.php", "w") as f:
    f.write(test_php)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_proj.php && rm test_proj.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")
print(f"ERR:\n{err}")

client.close()
