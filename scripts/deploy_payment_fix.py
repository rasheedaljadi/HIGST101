import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("packages/Webkul/Payment/src/Payment.php", "packages/Webkul/Payment/src/Payment.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Uploading updated Payment.php...")
for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"  -> {local_rel}")
    sftp.put(local_path, remote_path)
sftp.close()

cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan optimize",
]
for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => {out.strip()}")

print("\n=======================================================")
print("VERIFYING ADMIN ORDER VIEW 331 ON REMOTE")
print("=======================================================")

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\User\Models\Admin;
use Webkul\Sales\Models\Order;
use Webkul\Admin\Http\Controllers\Sales\OrderController;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Http\Request;

$request = Request::create('/admin/sales/orders/view/331', 'GET');
app()->instance('request', $request);

$admin = Admin::first();
auth()->guard('admin')->setUser($admin);
view()->share('errors', new ViewErrorBag);

$order = Order::find(331);
echo "Order: ID={$order->id}, Increment ID={$order->increment_id}\n";
echo "Payment Method: {$order->payment->method}\n";
echo "Shipping Method: {$order->shipping_method}\n";
echo "Shipping Title: {$order->shipping_title}\n";
echo "Shipping Amount: {$order->shipping_amount}\n";
echo "Grand Total: {$order->grand_total}\n";

try {
    $controller = app(OrderController::class);
    $view = $controller->view(331);
    $html = $view->render();
    echo "\nSUCCESS: Admin Order 331 View rendered successfully without errors! (" . strlen($html) . " bytes)\n";
} catch (\Throwable $e) {
    echo "\n=== EXCEPTION CAUGHT ===\n";
    echo "Class: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{REMOTE_ROOT}/test_verify_order_331.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, f"cd {REMOTE_ROOT} && php8.4 test_verify_order_331.php && rm test_verify_order_331.php")
print(f"\nTEST RESULTS:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
