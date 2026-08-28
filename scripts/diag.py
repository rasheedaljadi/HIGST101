import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\OfflinePayments\Models\OfflinePaymentDestination;
use Webkul\OfflinePayments\Models\OfflinePaymentAccount;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderPayment;

$order = Order::find(332);
echo "Order: " . ($order ? $order->id : 'null') . "\n";

$dest = OfflinePaymentDestination::first();
echo "Dest: " . ($dest ? $dest->id : 'null') . "\n";
if ($dest) {
    echo "Dest Account: " . ($dest->account ? $dest->account->display_name : 'null') . "\n";
    $account = $dest->account;
    $snapshot = [
        'snapshot_type' => 'offline_payment',
        'schema_version' => 2,
        'account' => [
            'id' => $account->id,
            'code' => $account->code,
            'display_name' => $account->display_name,
            'provider_name' => $account->provider_name,
            'recipient_name' => $account->recipient_name,
            'logo_path' => $account->logo_path,
        ],
        'destination' => [
            'id' => $dest->id,
            'account_identifier' => $dest->account_identifier,
            'swift_code' => $dest->swift_code,
            'transfer_instructions' => $dest->transfer_instructions,
        ],
        'currency' => [
            'id' => $dest->currency?->id,
            'code' => $dest->currency?->code,
            'name' => $dest->currency?->name,
        ],
    ];

    $payment = OrderPayment::where('order_id', 332)->first();
    if ($payment) {
        $additional = $payment->additional ?? [];
        $additional['offline_payment_snapshot'] = $snapshot;
        $additional['selected_offline_destination_id'] = $dest->id;
        $additional['selected_offline_account_id'] = $dest->id;
        $payment->additional = $additional;
        $payment->method_title = 'تحويل مالي - ' . $account->display_name;
        $payment->save();
        echo "Payment saved successfully! Method Title: " . $payment->method_title . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/diag.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 diag.php && rm diag.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
