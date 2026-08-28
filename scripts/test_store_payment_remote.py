import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Models\Customer;
use Webkul\Product\Models\Product;
use Illuminate\Http\Request;
use Webkul\Shop\Http\Controllers\API\OnepageController;

$customer = Customer::first();

foreach (['AUTHENTICATED' => $customer, 'GUEST' => null] as $type => $cust) {
    echo "\n=======================================================\n";
    echo "TESTING CHECKOUT ON: {$type}\n";
    echo "=======================================================\n";

    if ($cust) {
        auth()->guard('customer')->setUser($cust);
    } else {
        auth()->guard('customer')->logout();
    }

    $cart = \Webkul\Checkout\Models\Cart::create([
        'customer_id' => $cust?->id,
        'is_guest' => $cust ? 0 : 1,
        'customer_email' => $cust ? $cust->email : 'guest@example.com',
        'customer_first_name' => $cust ? $cust->first_name : 'Guest',
        'customer_last_name' => $cust ? $cust->last_name : 'User',
        'channel_id' => 1,
        'cart_currency_code' => 'USD',
        'global_currency_code' => 'USD',
        'base_currency_code' => 'USD',
        'is_active' => 1,
    ]);

    $product = Product::first();
    Cart::setCart($cart);
    Cart::addProduct($product, ['product_id' => $product->id, 'quantity' => 1]);
    
    $addressData = [
        'shipping' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@highest-ye.store',
            'address1' => ['Al-Zubairi St'],
            'city' => 'Sanaa',
            'state' => 'Sanaa',
            'country' => 'YE',
            'postcode' => '0000',
            'phone' => '777123456',
        ],
        'billing' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@highest-ye.store',
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

    $cart = Cart::getCart();
    echo "Cart ID: {$cart->id} | Total: {$cart->grand_total} | Items: " . $cart->items->count() . "\n";

    $controller = app(OnepageController::class);

    $testPayloads = [
        'wallet' => [
            'method' => 'wallet',
            'method_title' => 'محفظة هايست الإلكترونية',
            'description' => 'الدفع المباشر عبر رصيد محفظة هايست المتاح',
            'sort' => 1,
            'image' => '',
        ],
        'offline_payments' => [
            'method' => 'offline_payments',
            'method_title' => 'تحويل مالي',
            'description' => 'التحويل المالي المباشر عبر الحسابات البنكية والمحافظ الإلكترونية',
            'sort' => 2,
            'image' => '',
            'selected_offline_destination_id' => 4,
            'selected_offline_account_id' => 2,
        ],
        'moneytransfer' => [
            'method' => 'moneytransfer',
            'method_title' => 'تحويل بنكي',
            'description' => 'Money Transfer',
            'sort' => 3,
            'image' => '',
        ]
    ];

    foreach ($testPayloads as $name => $payload) {
        echo "\n---> Testing storePaymentMethod for payload '{$name}' <---\n";
        $req = Request::create('/api/checkout/onepage/payment-methods', 'POST', [
            'payment' => $payload
        ]);
        $req->headers->set('Accept', 'application/json');
        app()->instance('request', $req);

        try {
            $response = $controller->storePaymentMethod();
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                echo "Response Code: " . $response->getStatusCode() . "\n";
                echo "Response Content: " . json_encode($response->getData(), JSON_UNESCAPED_UNICODE) . "\n";
            } elseif (is_array($response)) {
                echo "Response Code: 200 OK (Array)\n";
                echo "Response Keys: " . implode(', ', array_keys($response)) . "\n";
                echo "Cart in Response: " . (isset($response['cart']) ? 'YES' : 'NO') . "\n";
                if (isset($response['cart'])) {
                    echo "Cart grand_total: " . $response['cart']['grand_total'] . " | payment_method: " . ($response['cart']['payment_method'] ?? 'N/A') . "\n";
                }
            } else {
                echo "Response Type: " . get_class($response) . "\n";
            }
        } catch (\Throwable $e) {
            echo "EXCEPTION: " . $e->getMessage() . "\n";
            echo "At: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_store_payment_remote.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_store_payment_remote.php && rm test_store_payment_remote.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")
print(f"ERR:\n{err}")

client.close()
