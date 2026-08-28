import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\Checkout\Facades\Cart;
use Webkul\Payment\Facades\Payment;
use Webkul\Customer\Models\Customer;
use Webkul\Product\Models\Product;
use Illuminate\Http\Request;
use Webkul\Shop\Http\Controllers\API\OnepageController;

// Create a cart session for testing
$customer = Customer::first();
if ($customer) {
    auth()->guard('customer')->setUser($customer);
}

// Check available payment methods
$supportedMethods = Payment::getSupportedPaymentMethods();
echo "Supported Payment Methods:\\n";
foreach ($supportedMethods['methods'] ?? [] as $m) {
    echo "  - Code: {$m['method']} | Title: {$m['method_title']}\\n";
}

// Let's check Wallet payment class
$walletPayment = app()->makeWith('Webkul\\Wallet\\Payment\\Wallet', []);
echo "\\nWallet isAvailable: " . ($walletPayment->isAvailable() ? 'YES' : 'NO') . "\\n";

// Let's check MoneyTransfer payment class
try {
    $moneyTransfer = app()->makeWith('Webkul\\Payment\\Payment\\MoneyTransfer', []);
    echo "MoneyTransfer isAvailable: " . ($moneyTransfer->isAvailable() ? 'YES' : 'NO') . "\\n";
} catch (\\Throwable $e) {
    echo "MoneyTransfer make error: " . $e->getMessage() . "\\n";
}

// Let's check OfflinePayments payment class
try {
    $offline = app()->makeWith('Webkul\\OfflinePayment\\Payment\\OfflinePayment', []);
    echo "OfflinePayment isAvailable: " . ($offline->isAvailable() ? 'YES' : 'NO') . "\\n";
} catch (\\Throwable $e) {
    echo "OfflinePayment error: " . $e->getMessage() . "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_payment_methods_debug.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_payment_methods_debug.php && rm test_payment_methods_debug.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
