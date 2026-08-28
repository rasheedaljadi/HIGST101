import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Webkul\Checkout\Models\Cart;

$cols = Schema::getColumnListing('carts');
echo "Carts columns:\n" . implode(', ', $cols) . "\n\n";

$lastCart = Cart::latest()->first();
if ($lastCart) {
    echo "Last Cart ID: {$lastCart->id}\n";
    echo "shipping_method: {$lastCart->shipping_method}\n";
    echo "shipping_amount: {$lastCart->shipping_amount}\n";
    echo "base_shipping_amount: {$lastCart->base_shipping_amount}\n";
    echo "sub_total: {$lastCart->sub_total}\n";
    echo "grand_total: {$lastCart->grand_total}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_cart_db.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_cart_db.php && rm check_cart_db.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
