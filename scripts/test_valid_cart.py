import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\Cart as CartModel;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Models\Customer;
use Webkul\Product\Models\Product;
use Illuminate\Http\Request;
use Webkul\Shop\Http\Controllers\API\OnepageController;

$product = Product::where('type', 'simple')->first();
if (! $product) {
    $product = Product::first();
}

$customer = Customer::first();
echo "Customer: {$customer->email} (ID {$customer->id})\n";
auth()->guard('customer')->setUser($customer);

// Check customer wallet
$wallet = \Webkul\Wallet\Models\WalletAccount::where('customer_id', $customer->id)->first();
echo "Customer Wallet: " . ($wallet ? "Active, Balance: {$wallet->available_balance}" : "NO WALLET ACCOUNT") . "\n";

$cart = CartModel::create([
    'customer_id' => $customer->id,
    'is_guest' => 0,
    'customer_email' => $customer->email,
    'customer_first_name' => $customer->first_name,
    'customer_last_name' => $customer->last_name,
    'channel_id' => 1,
    'cart_currency_code' => 'USD',
    'global_currency_code' => 'USD',
    'base_currency_code' => 'USD',
    'is_active' => 1,
    'items_count' => 1,
    'items_qty' => 1,
    'sub_total' => 50.00,
    'base_sub_total' => 50.00,
    'grand_total' => 55.00,
    'base_grand_total' => 55.00,
]);

CartItem::create([
    'quantity' => 1,
    'sku' => $product->sku,
    'type' => $product->type,
    'name' => $product->name,
    'price' => 50.00,
    'base_price' => 50.00,
    'total' => 50.00,
    'base_total' => 50.00,
    'cart_id' => $cart->id,
    'product_id' => $product->id,
]);

$cart = CartModel::with('items')->find($cart->id);
Cart::setCart($cart);

$addressData = [
    'billing' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'email' => $customer->email,
        'address' => ['Al-Zubairi St'],
        'city' => 'Sanaa',
        'state' => 'Sanaa',
        'country' => 'YE',
        'postcode' => '0000',
        'phone' => '777123456',
        'use_for_shipping' => true,
    ],
    'shipping' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'email' => $customer->email,
        'address' => ['Al-Zubairi St'],
        'city' => 'Sanaa',
        'state' => 'Sanaa',
        'country' => 'YE',
        'postcode' => '0000',
        'phone' => '777123456',
    ]
];
Cart::saveAddresses($addressData);
Cart::saveShippingMethod('flatrate_flatrate');
Cart::collectTotals();

$cart = Cart::getCart();
echo "Cart ID {$cart->id}: items = " . $cart->items->count() . ", Total = {$cart->grand_total}, hasError = " . (Cart::hasError() ? 'YES' : 'NO') . "\n";

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
    ],
    'cashondelivery' => [
        'method' => 'cashondelivery',
        'method_title' => 'Cash On Delivery',
        'description' => 'Cash On Delivery',
        'sort' => 4,
        'image' => '',
    ],
];

foreach ($testPayloads as $name => $payload) {
    echo "\n---------------- Testing: '{$name}' ----------------\n";
    $req = Request::create('/api/checkout/onepage/payment-methods', 'POST', [
        'payment' => $payload
    ]);
    $req->headers->set('Accept', 'application/json');
    app()->instance('request', $req);

    try {
        $resp = $controller->storePaymentMethod();
        if ($resp instanceof \Illuminate\Http\JsonResponse) {
            echo "Status: " . $resp->getStatusCode() . "\n";
            echo "Body: " . json_encode($resp->getData(), JSON_UNESCAPED_UNICODE) . "\n";
        } elseif (is_array($resp)) {
            echo "Status: 200 OK (Array)\n";
            echo "Cart grand_total in resp: " . ($resp['cart']['grand_total'] ?? 'N/A') . "\n";
            echo "Cart payment_method in resp: " . ($resp['cart']['payment_method'] ?? 'N/A') . "\n";
            echo "Cart Payment in DB: " . (Cart::getCart()->payment ? Cart::getCart()->payment->method : 'NONE') . "\n";
        } else {
            echo "Type: " . get_class($resp) . "\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "At: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_valid_cart.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_valid_cart.php && rm test_valid_cart.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
