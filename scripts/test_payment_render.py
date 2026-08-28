import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Sales\Models\Order;

$order = Order::find(332);
if ($order) {
    echo "Testing Order #332:\n";
    echo "Payment Method: " . $order->payment->method . "\n";
    echo "Payment Method Title: " . $order->payment->method_title . "\n";
    echo "Payment Additional:\n";
    print_r($order->payment->additional);
    
    // Test rendering admin payment snapshot
    $snapshotView = view('offline_payments::admin.orders.payment-snapshot', ['order' => $order])->render();
    echo "Admin payment snapshot view length: " . strlen($snapshotView) . " bytes\n";
    
    // Test rendering customer order view payment details
    $shopView = view('offline_payments::shop.payment.details', ['order' => $order])->render();
    echo "Shop payment details view length: " . strlen($shopView) . " bytes\n";
} else {
    echo "Order 332 not found\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_payment_render.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_payment_render.php && rm test_payment_render.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
