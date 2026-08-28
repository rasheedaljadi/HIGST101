import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Sales\Models\Order;

$orders = Order::latest()->take(5)->get();
foreach ($orders as $order) {
    echo "========================================\n";
    echo "Order ID: {$order->id} | Increment: {$order->increment_id}\n";
    echo "Payment Method: " . ($order->payment->method ?? 'NONE') . "\n";
    echo "Payment Method Title: " . ($order->payment->method_title ?? 'NONE') . "\n";
    echo "Payment Additional:\n";
    print_r($order->payment->additional ?? []);
    echo "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_order_payments.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_order_payments.php && rm inspect_order_payments.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
