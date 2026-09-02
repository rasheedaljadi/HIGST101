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

use Webkul\\Checkout\\Models\\Cart;
use Webkul\\Customer\\Models\\Customer;
use Illuminate\\Support\\Facades\\DB;

$customer = Customer::find(919) ?? Customer::latest()->first();
echo "Customer ID: " . $customer?->id . " | Email: " . $customer?->email . " | Name: " . $customer?->name . "\\n";

$cart = Cart::where('customer_id', $customer?->id)->where('is_active', 1)->latest()->first();
if (!$cart) {
    $cart = Cart::where('is_active', 1)->latest()->first();
}

echo "Cart ID: " . $cart?->id . " | Customer ID: " . $cart?->customer_id . " | Items count: " . $cart?->items?->count() . "\\n";
foreach ($cart?->items ?? [] as $item) {
    echo "  - Product ID: {$item->product_id} | Name: {$item->name} | Qty: {$item->quantity} | Price: \${$item->price}\\n";
    $inv = DB::table('product_inventories')->where('product_id', $item->product_id)->get();
    foreach ($inv as $i) {
        echo "      Inventory Source ID {$i->inventory_source_id}: Qty = {$i->qty}\\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_cart_919.php", "w") as f:
    f.write(php_code)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_cart_919.php && rm inspect_cart_919.php")
print(out)
client.close()
