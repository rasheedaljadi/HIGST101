import remote_ssh_helper as r

client = r.get_ssh_client()

test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\Event;
use Webkul\\Product\\Exceptions\\InsufficientProductInventoryException;
use Illuminate\\Support\\Facades\\Cache;
use App\\Models\\ExternalVariantProjection;

echo "=========================================================\\n";
echo "1. TESTING CART ADD EVENT DISPATCH (SUCCESS PATH)\\n";
echo "=========================================================\\n";

$proj = ExternalVariantProjection::first();
if ($proj) {
    // Normal in-stock check
    try {
        Event::dispatch('checkout.cart.add.before', $proj->variant_product_id);
        echo "Event dispatched and passed successfully ✅\\n";
    } catch (InsufficientProductInventoryException $e) {
        echo "Event blocked unexpectedly: " . $e->getMessage() . "\\n";
    }

    echo "\\n=========================================================\\n";
    echo "2. TESTING CART ADD EVENT DISPATCH (OUT OF STOCK BLOCK)\\n";
    echo "=========================================================\\n";
    $cacheKey = "ae_live_stock_{$proj->external_product_id}_{$proj->external_sku_id}";
    Cache::put($cacheKey, 0, now()->addMinutes(1));

    try {
        Event::dispatch('checkout.cart.add.before', $proj->variant_product_id);
        echo "Blocked: FAILED ❌\\n";
    } catch (InsufficientProductInventoryException $e) {
        echo "Blocked: SUCCESS ✅ => " . $e->getMessage() . "\\n";
    } finally {
        Cache::forget($cacheKey);
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_event_e2e.php", "w") as f:
    f.write(test_php)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_event_e2e.php && rm test_event_e2e.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
