import sys
sys.path.insert(0, 'scripts')
import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Checkout\\Models\\CartItem;
use Illuminate\\Support\\Facades\\DB;

$item = CartItem::where('cart_id', 488)->where('product_id', 1567)->first();
if ($item) {
    echo "Cart Item ID: " . $item->id . "\\n";
    echo "Qty: " . $item->quantity . "\\n";
    echo "Additional: " . json_encode($item->additional, JSON_UNESCAPED_UNICODE) . "\\n";
} else {
    echo "Item 1567 not found in cart 488\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_item_1567.php", "w") as f:
    f.write(php_code)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_item_1567.php && rm check_item_1567.php")
print(out)
client.close()
