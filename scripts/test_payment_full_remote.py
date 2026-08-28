import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Payment\Facades\Payment;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Models\Customer;
use Webkul\Product\Models\Product;
use Illuminate\Http\Request;
use Webkul\Shop\Http\Controllers\API\OnepageController;

$methods = Payment::getPaymentMethods();
echo "Configured available payment methods:\n";
foreach ($methods as $m) {
    echo "  - Code: {$m['method']} | Title: {$m['method_title']}\n";
}

// Check what offline payment destinations exist
$destinations = \DB::table('offline_payment_destinations')->where('status', 1)->get();
echo "\nOffline payment destinations in DB: " . $destinations->count() . "\n";
foreach ($destinations as $d) {
    echo "  - ID: {$d->id} | Account ID: {$d->account_id} | Identifier: {$d->account_identifier}\n";
}

// Test cart and customer
$customer = Customer::first();
if ($customer) {
    auth()->guard('customer')->setUser($customer);
    echo "\nLogged in as Customer ID: {$customer->id} ({$customer->email})\n";
}

// Ensure cart exists
$cart = Cart::getCart();
if (! $cart || ! $cart->items()->count()) {
    $product = Product::first();
    Cart::addProduct($product->id, ['product_id' => $product->id, 'quantity' => 1]);
    $cart = Cart::getCart();
}

$addressData = [
    'shipping' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'email' => 'customer@highest-ye.store',
        'address1' => ['Al-Zubairi St'],
        'city' => 'Sanaa',
        'state' => 'Sanaa',
        'country' => 'YE',
        'postcode' => '0000',
        'phone' => '777123456',
    ],
    'billing' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'email' => 'customer@highest-ye.store',
        'address1' => ['Al-Zubairi St'],
        'city' => 'Sanaa',
        'state' => 'Sanaa',
        'country' => 'YE',
        'postcode' => '0000',
        'phone' => '777123456',
        'use_for_shipping' => true,
    ]
];
Cart::saveAddresses($addressData);
Cart::saveShippingMethod('flatrate_flatrate');
Cart::collectTotals();

echo "Cart ID: {$cart->id}, Grand Total: {$cart->grand_total}, Shipping: {$cart->shipping_method}\n";

$controller = app(OnepageController::class);

foreach (['moneytransfer', 'wallet', 'offline_payments', 'cashondelivery'] as $methodCode) {
    echo "\n>>> Testing storePaymentMethod for: '{$methodCode}' <<<\n";
    $req = new Request();
    $req->replace([
        'payment' => [
            'method' => $methodCode,
            'selected_offline_destination_id' => 1,
            'selected_offline_account_id' => 1,
        ]
    ]);
    app()->instance('request', $req);

    try {
        $resp = $controller->storePaymentMethod();
        if ($resp instanceof \Illuminate\Http\JsonResponse) {
            echo "HTTP Status: " . $resp->getStatusCode() . "\n";
            echo "Response: " . json_encode($resp->getData(), JSON_UNESCAPED_UNICODE) . "\n";
        } elseif (is_array($resp)) {
            echo "HTTP Status: 200 OK (Array returned)\n";
            echo "Cart total in resp: " . ($resp['cart']['grand_total'] ?? 'N/A') . "\n";
        } else {
            echo "Response type: " . get_class($resp) . "\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "At: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_payment_full.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_payment_full.php && rm test_payment_full.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")
print(f"ERR:\n{err}")

client.close()
