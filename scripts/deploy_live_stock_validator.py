import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("app/Services/AliExpress/AliExpressLiveStockValidator.php", "app/Services/AliExpress/AliExpressLiveStockValidator.php"),
    ("packages/Webkul/Procurement/src/Listeners/AliExpressLiveStockListener.php", "packages/Webkul/Procurement/src/Listeners/AliExpressLiveStockListener.php"),
    ("packages/Webkul/Procurement/src/Providers/EventServiceProvider.php", "packages/Webkul/Procurement/src/Providers/EventServiceProvider.php"),
]

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

for rel_local, rel_remote in files_to_sync:
    local_path = f"{local_base}/{rel_local}"
    remote_path = f"{remote_base}/{rel_remote}"
    print(f"Uploading {rel_local} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan cache:clear && php8.4 artisan config:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Test Live Stock Validation on Server
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Services\\AliExpress\\AliExpressLiveStockValidator;
use Webkul\\Product\\Exceptions\\InsufficientProductInventoryException;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Product\\Models\\Product;

echo "=========================================================\\n";
echo "1. TESTING LIVE STOCK VALIDATOR ON PRODUCTION\\n";
echo "=========================================================\\n";

$validator = app(AliExpressLiveStockValidator::class);

// Find an AliExpress demand product
$demand = ProcurementDemand::latest('id')->first();
if ($demand) {
    $pId = $demand->product_id;
    echo "Testing Demand #{$demand->id} - Product #{$pId}:\\n";
    try {
        $res = $validator->validateLiveStock($pId, ['quantity' => 1]);
        echo "Validation Result: " . ($res ? 'IN-STOCK / VALID ✅' : 'FAILED') . "\\n";
    } catch (InsufficientProductInventoryException $e) {
        echo "Validation Correctly Blocked Out-of-Stock Item 🛑: " . $e->getMessage() . "\\n";
    } catch (\\Throwable $e) {
        echo "Unexpected Exception: " . $e->getMessage() . "\\n";
    }
} else {
    echo "No demand found to test.\\n";
}

echo "\\n=========================================================\\n";
echo "2. VERIFYING REGISTERED LISTENERS IN EVENT DISPATCHER\\n";
echo "=========================================================\\n";
$events = app('events');
$listeners = $events->getListeners('checkout.cart.add.before');
echo "Listeners for 'checkout.cart.add.before': " . count($listeners) . " registered ✅\\n";
$listeners2 = $events->getListeners('checkout.order.save.before');
echo "Listeners for 'checkout.order.save.before': " . count($listeners2) . " registered ✅\\n";
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_live_validator.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_live_validator.php && rm test_live_validator.php")
print(f"\nVerification Output:\n{out}")

client.close()
