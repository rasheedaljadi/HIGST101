import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Invoice;

$order = Order::find(332);
if ($order) {
    // Check or create mock invoice for testing pdf
    $invoice = $order->invoices->first();
    if (! $invoice) {
        $invoice = new Invoice();
        $invoice->id = 999;
        $invoice->increment_id = 'INV-TEST';
        $invoice->created_at = now();
        $invoice->order_id = $order->id;
        $invoice->setRelation('order', $order);
        $invoice->setRelation('items', collect([]));
    }
    
    $orderCurrencyCode = $order->order_currency_code;
    $pdfHtml = view('shop::customers.account.orders.pdf', compact('invoice', 'orderCurrencyCode'))->render();
    echo "PDF HTML rendered successfully! Length: " . strlen($pdfHtml) . " bytes\n";
    if (str_contains($pdfHtml, 'محفظة جيب') && str_contains($pdfHtml, '770 433 786')) {
        echo "SUCCESS: Payment account details found inside PDF HTML!\n";
    } else {
        echo "WARNING: Payment details not matched in HTML output\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_pdf.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_pdf.php && rm test_pdf.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
