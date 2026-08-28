import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("packages/Webkul/Shop/src/Resources/views/checkout/onepage/summary.blade.php", "packages/Webkul/Shop/src/Resources/views/checkout/onepage/summary.blade.php"),
    ("packages/Webkul/Shop/src/Http/Controllers/API/OnepageController.php", "packages/Webkul/Shop/src/Http/Controllers/API/OnepageController.php"),
    ("packages/Webkul/Shop/src/Http/Controllers/OnepageController.php", "packages/Webkul/Shop/src/Http/Controllers/OnepageController.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Uploading updated summary and onepage controller files...")
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
print("VERIFYING NO PRE-SELECTED SHIPPING FEE ON ADDRESS STEP")
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

echo "Step 1: Save Address (Customer is on Address step before choosing shipping method)\n";
$reqAddress = Request::create('/api/checkout/onepage/addresses', 'POST', $addressData);
$reqAddress->headers->set('Accept', 'application/json');
app()->instance('request', $reqAddress);
$controller->storeAddress(app(\Webkul\Shop\Http\Requests\CartAddressRequest::class));

$c = Cart::getCart();
echo "  -> Selected Shipping Method: " . ($c->shipping_method ?: 'NONE (Correct)') . "\n";
echo "  -> Shipping Amount: " . $c->shipping_amount . " (Should be 0)\n";
echo "  -> Subtotal: " . $c->sub_total . "\n";
echo "  -> Grand Total: " . $c->grand_total . " (Should equal Subtotal " . $c->sub_total . ")\n";

echo "\nStep 2: Customer selects Pickup Point ($0.00 Fee)\n";
$reqPickup = Request::create('/api/checkout/onepage/shipping-methods', 'POST', [
    'shipping_method' => 'deliverypoint_pickup',
    'delivery_point_id' => 2,
]);
$reqPickup->headers->set('Accept', 'application/json');
app()->instance('request', $reqPickup);
$controller->storeShippingMethod();
$cPickup = Cart::getCart();
echo "  -> Selected Shipping Method: " . $cPickup->shipping_method . "\n";
echo "  -> Shipping Amount: " . $cPickup->shipping_amount . "\n";
echo "  -> Grand Total: " . $cPickup->grand_total . "\n";

echo "\nStep 3: Customer switches to Home Delivery ($5.00 Fee)\n";
$reqHome = Request::create('/api/checkout/onepage/shipping-methods', 'POST', [
    'shipping_method' => 'homedelivery_standard',
]);
$reqHome->headers->set('Accept', 'application/json');
app()->instance('request', $reqHome);
$controller->storeShippingMethod();
$cHome = Cart::getCart();
echo "  -> Selected Shipping Method: " . $cHome->shipping_method . "\n";
echo "  -> Shipping Amount: " . $cHome->shipping_amount . "\n";
echo "  -> Grand Total: " . $cHome->grand_total . "\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{REMOTE_ROOT}/test_verify_no_early_fee.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, f"cd {REMOTE_ROOT} && php8.4 test_verify_no_early_fee.php && rm test_verify_no_early_fee.php")
print(f"\nTEST RESULTS:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
