import remote_ssh_helper as r

client = r.get_ssh_client()

test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\Event;
use Webkul\\Product\\Exceptions\\InsufficientProductInventoryException;

echo "=========================================================\\n";
echo "TESTING CART ADD EVENT DISPATCH FOR IN-STOCK PRODUCT #9142\\n";
echo "=========================================================\\n";

try {
    Event::dispatch('checkout.cart.add.before', 9142);
    echo "Product #9142 Cart Add Check: PASSED (IN-STOCK) ✅\\n";
} catch (InsufficientProductInventoryException $e) {
    echo "Product #9142 Cart Add Check: BLOCKED => " . $e->getMessage() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_event_instock.php", "w") as f:
    f.write(test_php)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_event_instock.php && rm test_event_instock.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
