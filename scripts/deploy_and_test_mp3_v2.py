import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

f = "app/Services/AliExpress/AliExpressLiveStockValidator.php"
sftp.put(f"{local_base}/{f}", f"{remote_base}/{f}")
sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Test Live Stock Validation for MP3 Variants
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Services\\AliExpress\\AliExpressLiveStockValidator;
use Webkul\\Product\\Exceptions\\InsufficientProductInventoryException;
use Illuminate\\Support\\Facades\\Cache;

$validator = app(AliExpressLiveStockValidator::class);

echo "=========================================================\\n";
echo "1. TESTING VARIANT 9149 ('Only MP3' -> LIVE STOCK: 0)\\n";
echo "=========================================================\\n";
// Clear cache first to force live check
Cache::flush();

try {
    $validator->validateLiveStock(9148, [
        'selected_configurable_option' => 9149,
        'quantity' => 1
    ]);
    echo "Variant 9149 Result: FAILED TO BLOCK ❌\\n";
} catch (InsufficientProductInventoryException $e) {
    echo "Variant 9149 Result: BLOCKED AS EXPECTED ✅ => " . $e->getMessage() . "\\n";
} catch (\\Throwable $e) {
    echo "Variant 9149 Error: " . $e->getMessage() . "\\n";
}

echo "\\n=========================================================\\n";
echo "2. TESTING VARIANT 9153 ('MP3 with 32GB Card' -> LIVE STOCK: 3)\\n";
echo "=========================================================\\n";
try {
    $res = $validator->validateLiveStock(9148, [
        'selected_configurable_option' => 9153,
        'quantity' => 1
    ]);
    echo "Variant 9153 Result: ALLOWED AS EXPECTED (IN STOCK: 3) ✅\\n";
} catch (InsufficientProductInventoryException $e) {
    echo "Variant 9153 Result: BLOCKED UNEXPECTEDLY ❌ => " . $e->getMessage() . "\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_mp3_variants_live.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_mp3_variants_live.php && rm test_mp3_variants_live.php")
print(f"\nVerification Output:\n{out}")

client.close()
