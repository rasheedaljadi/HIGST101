import remote_ssh_helper as r

client = r.get_ssh_client()

test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Services\\AliExpress\\AliExpressLiveStockValidator;
use Webkul\\Product\\Exceptions\\InsufficientProductInventoryException;
use Illuminate\\Support\\Facades\\Cache;

echo "=========================================================\\n";
echo "TESTING OUT-OF-STOCK LIVE BLOCKING SIMULATION\\n";
echo "=========================================================\\n";

$validator = app(AliExpressLiveStockValidator::class);

// Force cache 0 stock for product 9142 to test live blocker
$aeProductId = "1005006761304005";
$supplierSkuId = "12000038215099919";
$cacheKey = "ae_live_stock_{$aeProductId}_{$supplierSkuId}";

Cache::put($cacheKey, 0, now()->addMinutes(1));

try {
    $validator->validateLiveStock(9142, [
        'selected_configurable_option' => 9142,
        'quantity' => 1
    ]);
    echo "Result: FAILED TO BLOCK ❌\\n";
} catch (InsufficientProductInventoryException $e) {
    echo "Result: SUCCESSFULLY BLOCKED ✅\\n";
    echo "Message: " . $e->getMessage() . "\\n";
} finally {
    Cache::forget($cacheKey);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_oos_block.php", "w") as f:
    f.write(test_php)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_oos_block.php && rm test_oos_block.php")
print(f"OUT:\n{out}")

client.close()
