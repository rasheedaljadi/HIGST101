import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Sales\Models\Order;
use Webkul\Customer\Models\Customer;

view()->share('errors', new \Illuminate\Support\ViewErrorBag);
$customer = Customer::first();
if ($customer) {
    auth()->guard('customer')->setUser($customer);
}

$order = Order::find(332);
$view = view('shop::customers.account.orders.view', compact('order'))->render();
echo "Customer Order #332 view rendered successfully! Length: " . strlen($view) . " bytes\n";
if (str_contains($view, 'محفظة جيب') && str_contains($view, '770 433 786')) {
    echo "SUCCESS: Payment details displayed in Customer Order view!\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_customer_order_view.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_customer_order_view.php && rm test_customer_order_view.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
