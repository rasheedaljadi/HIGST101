import remote_ssh_helper as r

client = r.get_ssh_client()

php_fix = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Sales\Models\Order;
use Webkul\OfflinePayments\Models\OfflinePaymentDestination;

$orders = Order::whereHas('payment', function($q) {
    $q->whereIn('method', ['offline_payments', 'moneytransfer']);
})->get();

$defaultDest = OfflinePaymentDestination::whereHas('account')->with('account', 'currency')->first();

foreach ($orders as $order) {
    $payment = $order->payment;
    $additional = is_array($payment->additional) ? $payment->additional : [];

    if ($defaultDest && $defaultDest->account) {
        $account = $defaultDest->account;
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
                'id' => $defaultDest->id,
                'account_identifier' => $defaultDest->account_identifier,
                'swift_code' => $defaultDest->swift_code,
                'transfer_instructions' => $defaultDest->transfer_instructions,
            ],
            'currency' => [
                'id' => $defaultDest->currency?->id,
                'code' => $defaultDest->currency?->code,
                'name' => $defaultDest->currency?->name,
            ],
        ];

        $additional['offline_payment_snapshot'] = $snapshot;
        $additional['selected_offline_destination_id'] = $defaultDest->id;
        $additional['selected_offline_account_id'] = $defaultDest->id;

        $payment->additional = $additional;
        $payment->method_title = 'تحويل مالي - ' . ($account->display_name ?: $account->provider_name);
        $payment->save();

        echo "Updated Order #{$order->id} (Increment #{$order->increment_id}) with payment snapshot: {$payment->method_title}\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/backfill_order_snapshots.php", "w") as f:
    f.write(php_fix)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 backfill_order_snapshots.php && rm backfill_order_snapshots.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
