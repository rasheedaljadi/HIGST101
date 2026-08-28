import remote_ssh_helper as r

client = r.get_ssh_client()

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

$product = Product::where('type', 'simple')->first() ?: Product::first();
$customer = Customer::first();
auth()->guard('customer')->setUser($customer);

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
    'sub_total' => 30.00,
    'base_sub_total' => 30.00,
    'grand_total' => 30.00,
    'base_grand_total' => 30.00,
]);

CartItem::create([
    'quantity' => 1,
    'sku' => $product->sku,
    'type' => $product->type,
    'name' => $product->name,
    'price' => 30.00,
    'base_price' => 30.00,
    'total' => 30.00,
    'base_total' => 30.00,
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
        'city' => 'معين',
        'state' => 'أمانة العاصمة',
        'country' => 'YE',
        'postcode' => '00000',
        'phone' => '777123456',
        'use_for_shipping' => true,
    ],
    'shipping' => [
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'email' => $customer->email,
        'address' => ['Al-Zubairi St'],
        'city' => 'معين',
        'state' => 'أمانة العاصمة',
        'country' => 'YE',
        'postcode' => '00000',
        'phone' => '777123456',
    ]
];
Cart::saveAddresses($addressData);
$controller = app(OnepageController::class);

echo "=== TEST 1: Select Pickup Point 2 (مكتب هايست الرئيسي حدة) ===\n";
$req = Request::create('/api/checkout/onepage/shipping-methods', 'POST', [
    'shipping_method' => 'deliverypoint_pickup',
    'delivery_point_id' => 2,
]);
$req->headers->set('Accept', 'application/json');
app()->instance('request', $req);
$resp = $controller->storeShippingMethod();
$cart = Cart::getCart();
$add = is_array($cart->shipping_address->additional) ? $cart->shipping_address->additional : json_decode($cart->shipping_address->additional ?? '[]', true);
echo "Result Method: {$cart->shipping_method} | Selected Point ID: " . ($add['delivery_point_id'] ?? 'NONE') . "\n";
echo "Snapshot: " . json_encode($add['delivery_point_snapshot'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== TEST 2: Select Pickup Point 3 (فرع هايست الاصبحي) ===\n";
$req2 = Request::create('/api/checkout/onepage/shipping-methods', 'POST', [
    'shipping_method' => 'deliverypoint_pickup',
    'delivery_point_id' => 3,
]);
$req2->headers->set('Accept', 'application/json');
app()->instance('request', $req2);
$resp2 = $controller->storeShippingMethod();
$cart = Cart::getCart();
$add2 = is_array($cart->shipping_address->additional) ? $cart->shipping_address->additional : json_decode($cart->shipping_address->additional ?? '[]', true);
echo "Result Method: {$cart->shipping_method} | Selected Point ID: " . ($add2['delivery_point_id'] ?? 'NONE') . "\n";
echo "Snapshot: " . json_encode($add2['delivery_point_snapshot'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_dp_selection.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_dp_selection.php && rm test_dp_selection.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
