import remote_ssh_helper as r

client = r.get_ssh_client()

php_fix = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Sales\Models\Order;
use Webkul\OfflinePayments\Models\OfflinePaymentDestination;

$order = Order::find(332);
$dest = OfflinePaymentDestination::with('account', 'currency')->first();

if ($order && $dest && $dest->account) {
    $payment = $order->payment;
    $additional = is_array($payment->additional) ? $payment->additional : [];
    
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

    $additional['offline_payment_snapshot'] = $snapshot;
    $additional['selected_offline_destination_id'] = $dest->id;
    $additional['selected_offline_account_id'] = $dest->id;

    $payment->additional = $additional;
    $payment->method_title = 'تحويل مالي - ' . ($account->display_name ?: $account->provider_name);
    $payment->save();

    echo "Successfully updated Order 332 with account: {$payment->method_title}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/update_332.php", "w") as f:
    f.write(php_fix)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 update_332.php && rm update_332.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
