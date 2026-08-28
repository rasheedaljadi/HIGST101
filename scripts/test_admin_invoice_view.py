import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Invoice;
use Webkul\User\Models\Admin;

view()->share('errors', new \Illuminate\Support\ViewErrorBag);
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$invoice = Invoice::first();
if ($invoice) {
    $view = view('admin::sales.invoices.view', compact('invoice'))->render();
    echo "Admin Invoice #{$invoice->id} view rendered successfully! Length: " . strlen($view) . " bytes\n";
    if (str_contains($view, 'محفظة جيب') || str_contains($view, 'طريقة الدفع')) {
        echo "SUCCESS: Payment details displayed in Admin Invoice view!\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_admin_invoice_view.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_admin_invoice_view.php && rm test_admin_invoice_view.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
