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

use App\\Services\\AliExpress\\AliExpressLiveStockValidator;
use Webkul\\Checkout\\Models\\Cart;

$cart = Cart::find(488);
echo "Cart 488 items: " . $cart->items->count() . "\\n";

$validator = app(AliExpressLiveStockValidator::class);

foreach ($cart->items as $item) {
    echo "\\nChecking Item: " . $item->name . " (Product ID: {$item->product_id})\\n";
    $childProductId = $item->child?->product_id ?? ($item->additional['selected_configurable_option'] ?? $item->product_id);
    echo "  childProductId: " . $childProductId . " | Qty: " . $item->quantity . "\\n";
    
    $cartData = [
        'selected_configurable_option' => $childProductId,
        'quantity' => (int) $item->quantity,
    ];
    
    try {
        $validator->validateLiveStock($item->product_id, $cartData);
        echo "  Result: PASS\\n";
    } catch (\\Exception $e) {
        echo "  Result: FAILED -> " . $e->getMessage() . "\\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/debug_cart_items_stock.php", "w") as f:
    f.write(php_code)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 debug_cart_items_stock.php && rm debug_cart_items_stock.php")
print(out)
client.close()
