import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\OfflinePayments\Models\OfflinePaymentAccount;
use Webkul\OfflinePayments\Models\OfflinePaymentDestination;

$accounts = OfflinePaymentAccount::with('destinations')->get();
echo "Offline Payment Accounts (" . $accounts->count() . "):\n";
foreach ($accounts as $acc) {
    echo "Account ID: {$acc->id}, Code: {$acc->code}, Display: {$acc->display_name}, Provider: {$acc->provider_name}, Recipient: {$acc->recipient_name}\n";
    foreach ($acc->destinations as $dest) {
        echo "  -> Dest ID: {$dest->id}, Account Identifier: {$dest->account_identifier}, Status: {$dest->status}\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_offline_accounts.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_offline_accounts.php && rm inspect_offline_accounts.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
