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

$customer = Customer::first();
if ($customer) {
    auth()->guard('customer')->setUser($customer);
    echo "Customer: {$customer->email} (ID: {$customer->id})\\n";
}

// Get or prepare a cart
$cart = Cart::getCart();
if (! $cart || ! $cart->items->count()) {
    $product = Product::first();
    echo "Adding product {$product->id} to cart...\\n";
    Cart::addProduct($product->id, ['product_id' => $product->id, 'quantity' => 1]);
    $cart = Cart::getCart();
}

echo "Cart ID: {$cart->id}, Total: {$cart->grand_total}\\n";

// Set shipping address & method
$addressData = [
    'shipping' => [
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@highest-ye.store',
        'address1' => ['Main Street'],
        'city' => 'Sanaa',
        'state' => 'Sana\'a City',
        'country' => 'YE',
        'postcode' => '0000',
        'phone' => '777000000',
    ],
    'billing' => [
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@highest-ye.store',
        'address1' => ['Main Street'],
        'city' => 'Sanaa',
        'state' => 'Sana\'a City',
        'country' => 'YE',
        'postcode' => '0000',
        'phone' => '777000000',
        'use_for_shipping' => true,
    ]
];
Cart::saveAddresses($addressData);
Cart::saveShippingMethod('flatrate_flatrate');
Cart::collectTotals();

$supportedMethods = Payment::getSupportedPaymentMethods();
echo "\\nSupported Payment Methods:\\n";
foreach ($supportedMethods['payment_methods'] ?? $supportedMethods['methods'] ?? $supportedMethods ?? [] as $m) {
    if (is_array($m)) {
        echo "  - Code: {$m['method']} | Title: {$m['method_title']}\\n";
    }
}

$controller = app(OnepageController::class);

$methodsToTest = ['moneytransfer', 'wallet', 'offline_payments', 'cashondelivery'];

foreach ($methodsToTest as $mCode) {
    echo "\\n================ Testing storePaymentMethod for: {$mCode} ================\\n";
    $req = new Request();
    $req->replace([
        'payment' => [
            'method' => $mCode,
            'selected_offline_destination_id' => 1,
            'selected_offline_account_id' => 1,
        ]
    ]);
    app()->instance('request', $req);

    try {
        $res = $controller->storePaymentMethod();
        if ($res instanceof \\Illuminate\\Http\\JsonResponse) {
            echo "Status: " . $res->getStatusCode() . "\\n";
            echo "Data: " . json_encode($res->getData(), JSON_UNESCAPED_UNICODE) . "\\n";
        } elseif (is_array($res)) {
            echo "Status: 200 (Array returned)\\n";
            echo "Keys: " . implode(', ', array_keys($res)) . "\\n";
            echo "Cart Payment Method in DB: " . Cart::getCart()->payment->method . "\\n";
        } else {
            echo "Result type: " . get_class($res) . "\\n";
        }
    } catch (\\Throwable $e) {
        echo "ERROR Exception: " . $e->getMessage() . "\\n";
        echo "Line: " . $e->getFile() . ":" . $e->getLine() . "\\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_payment_api.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_payment_api.php && rm test_payment_api.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
