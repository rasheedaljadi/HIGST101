import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("packages/Webkul/Shop/src/Resources/views/checkout/onepage/shipping.blade.php", "packages/Webkul/Shop/src/Resources/views/checkout/onepage/shipping.blade.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Uploading updated shipping.blade.php...")
for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"  -> {local_rel}")
    sftp.put(local_path, remote_path)
sftp.close()

cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan optimize",
]
for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => {out.strip()}")

print("\n=======================================================")
print("RUNNING INSTANT SWITCHING VERIFICATION ON REMOTE")
print("=======================================================")

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

echo "Step 1: Select Home Delivery ($5.00 Fee)\n";
$req1 = Request::create('/api/checkout/onepage/shipping-methods', 'POST', [
    'shipping_method' => 'homedelivery_standard',
]);
$req1->headers->set('Accept', 'application/json');
app()->instance('request', $req1);
$controller->storeShippingMethod();
$c1 = Cart::getCart();
echo "  -> Method: {$c1->shipping_method} | Shipping Fee: {$c1->shipping_amount} | Subtotal: {$c1->sub_total} | Grand Total: {$c1->grand_total}\n";

echo "\nStep 2: Switch to Delivery Point Pickup ($0.00 Fee, Point ID: 2)\n";
$req2 = Request::create('/api/checkout/onepage/shipping-methods', 'POST', [
    'shipping_method' => 'deliverypoint_pickup',
    'delivery_point_id' => 2,
]);
$req2->headers->set('Accept', 'application/json');
app()->instance('request', $req2);
$controller->storeShippingMethod();
$c2 = Cart::getCart();
echo "  -> Method: {$c2->shipping_method} | Shipping Fee: {$c2->shipping_amount} | Subtotal: {$c2->sub_total} | Grand Total: {$c2->grand_total}\n";

echo "\nStep 3: Switch back to Home Delivery ($5.00 Fee)\n";
$req3 = Request::create('/api/checkout/onepage/shipping-methods', 'POST', [
    'shipping_method' => 'homedelivery_standard',
]);
$req3->headers->set('Accept', 'application/json');
app()->instance('request', $req3);
$controller->storeShippingMethod();
$c3 = Cart::getCart();
echo "  -> Method: {$c3->shipping_method} | Shipping Fee: {$c3->shipping_amount} | Subtotal: {$c3->sub_total} | Grand Total: {$c3->grand_total}\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{REMOTE_ROOT}/test_instant_switching.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, f"cd {REMOTE_ROOT} && php8.4 test_instant_switching.php && rm test_instant_switching.php")
print(f"\nTEST RESULTS:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
