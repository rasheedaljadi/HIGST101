import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Checkout\Facades\Cart;
use Webkul\OfflinePayments\Services\OfflinePaymentAccountResolver;

echo "=== CURRENCIES ===\n";
$currencies = DB::table('currencies')->get();
foreach ($currencies as $c) {
    echo "  - ID: {$c->id} | Code: {$c->code} | Name: {$c->name}\n";
}

echo "\n=== CHANNELS ===\n";
$channels = DB::table('channels')->get();
foreach ($channels as $ch) {
    echo "  - ID: {$ch->id} | Code: {$ch->code} | Hostname: {$ch->hostname}\n";
}

echo "\n=== OFFLINE PAYMENT ACCOUNTS ===\n";
$accounts = DB::table('offline_payment_accounts')->get();
foreach ($accounts as $a) {
    echo "  - ID: {$a->id} | Name: {$a->display_name} | Provider: {$a->provider_name} | is_active: {$a->is_active} | channel_ids: {$a->channel_ids}\n";
}

echo "\n=== OFFLINE PAYMENT DESTINATIONS ===\n";
$destinations = DB::table('offline_payment_destinations')->get();
foreach ($destinations as $d) {
    echo "  - ID: {$d->id} | Account ID: {$d->account_id} | Currency ID: {$d->currency_id} | is_active: {$d->is_active} | Identifier: {$d->account_identifier}\n";
}

$cart = Cart::getCart();
if (! $cart) {
    $cart = \Webkul\Checkout\Models\Cart::latest('id')->first();
}

if ($cart) {
    echo "\n=== CART (ID {$cart->id}) ===\n";
    echo "Currency Code: {$cart->cart_currency_code}\n";
    echo "Channel ID: {$cart->channel_id}\n";
    echo "Grand Total: {$cart->grand_total}\n";

    $resolver = app(OfflinePaymentAccountResolver::class);
    $resolved = $resolver->getAccountsForCart($cart);
    echo "Resolved Accounts Count for Cart: " . $resolved->count() . "\n";
    foreach ($resolved as $r) {
        echo "  -> Found: ID {$r->id} | " . ($r->account ? $r->account->display_name : 'No account') . "\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_offline_details.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_offline_details.php && rm test_offline_details.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")
print(f"ERR:\n{err}")

client.close()
