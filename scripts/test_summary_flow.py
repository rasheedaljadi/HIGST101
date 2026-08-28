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
    'sub_total' => 52.36,
    'base_sub_total' => 52.36,
    'grand_total' => 52.36,
    'base_grand_total' => 52.36,
]);

CartItem::create([
    'quantity' => 1,
    'sku' => $product->sku,
    'type' => $product->type,
    'name' => $product->name,
    'price' => 52.36,
    'base_price' => 52.36,
    'total' => 52.36,
    'base_total' => 52.36,
    'cart_id' => $cart->id,
    'product_id' => $product->id,
]);

$cart = CartModel::with('items')->find($cart->id);
Cart::setCart($cart);

$addressData = [
    'billing' => [
        'first_name' => 'Rashid',
        'last_name' => 'Al-Jadi',
        'email' => $customer->email,
        'address' => ['Hadda St'],
        'city' => 'السبعين',
        'state' => 'أمانة العاصمة',
        'country' => 'YE',
        'postcode' => '00000',
        'phone' => '779500082',
        'use_for_shipping' => true,
    ],
    'shipping' => [
        'first_name' => 'Rashid',
        'last_name' => 'Al-Jadi',
        'email' => $customer->email,
        'address' => ['Hadda St'],
        'city' => 'السبعين',
        'state' => 'أمانة العاصمة',
        'country' => 'YE',
        'postcode' => '00000',
        'phone' => '779500082',
    ]
];

$controller = app(OnepageController::class);

echo "--- Step 1: POST Address ---\n";
$reqAddress = Request::create('/api/checkout/onepage/addresses', 'POST', $addressData);
$reqAddress->headers->set('Accept', 'application/json');
app()->instance('request', $reqAddress);
$controller->storeAddress(app(\Webkul\Shop\Http\Requests\CartAddressRequest::class));

echo "--- Step 2: POST Shipping Method (homedelivery_standard) ---\n";
$reqHome = Request::create('/api/checkout/onepage/shipping-methods', 'POST', [
    'shipping_method' => 'homedelivery_standard',
]);
$reqHome->headers->set('Accept', 'application/json');
app()->instance('request', $reqHome);
$respStore = $controller->storeShippingMethod();
echo "storeShippingMethod HTTP status: " . $respStore->status() . "\n";
echo "storeShippingMethod response: " . $respStore->content() . "\n";

echo "\n--- Step 3: GET Summary ---\n";
$reqSummary = Request::create('/api/checkout/onepage/summary', 'GET');
$reqSummary->headers->set('Accept', 'application/json');
app()->instance('request', $reqSummary);
$respSummary = $controller->summary();
$summaryData = $respSummary->response()->getData(true);
echo "summaryData['data']['shipping_method']: " . var_export($summaryData['data']['shipping_method'] ?? 'NULL', true) . "\n";
echo "summaryData['data']['shipping_amount']: " . var_export($summaryData['data']['shipping_amount'] ?? 'NULL', true) . "\n";
echo "summaryData['data']['formatted_shipping_amount']: " . var_export($summaryData['data']['formatted_shipping_amount'] ?? 'NULL', true) . "\n";
echo "summaryData['data']['sub_total']: " . var_export($summaryData['data']['sub_total'] ?? 'NULL', true) . "\n";
echo "summaryData['data']['grand_total']: " . var_export($summaryData['data']['grand_total'] ?? 'NULL', true) . "\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_summary_flow.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_summary_flow.php && rm test_summary_flow.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
