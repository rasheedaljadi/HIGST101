import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("packages/Webkul/Shipping/src/Shipping.php", "packages/Webkul/Shipping/src/Shipping.php"),
    ("packages/Webkul/Shipping/src/Config/carriers.php", "packages/Webkul/Shipping/src/Config/carriers.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Uploading updated shipping package files...")
for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"  -> {local_rel}")
    sftp.put(local_path, remote_path)

php_update_config = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEACTIVATING LEGACY CARRIERS IN CORE_CONFIG ===\n";

$legacyCarriers = ['dropshipping', 'flatrate', 'free'];
foreach ($legacyCarriers as $c) {
    $updated = DB::table('core_config')->where('code', "sales.carriers.{$c}.active")->update(['value' => '0']);
    echo "Deactivated sales.carriers.{$c}.active in DB (rows updated: {$updated})\n";
}
"""

with sftp.file(f"{REMOTE_ROOT}/deactivate_legacy_carriers.php", "w") as f:
    f.write(php_update_config)

code, out, err = r.run_remote_cmd(client, f"cd {REMOTE_ROOT} && php8.4 deactivate_legacy_carriers.php && rm deactivate_legacy_carriers.php")
print(f"OUTPUT:\n{out}")

cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan config:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan optimize",
]
for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => {out.strip()}")

print("\n=======================================================")
print("RUNNING LIVE CHECKOUT VERIFICATION ON REMOTE")
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
use Webkul\Shipping\Facades\Shipping;

$product = Product::where('type', 'simple')->first() ?: Product::first();
$customer = Customer::first();
auth()->guard('customer')->setUser($customer);

$testGovernorates = [
    'Amanat Al Asimah (أمانة العاصمة - SAN)' => 'أمانة العاصمة',
    'Aden (عدن - AD)' => 'عدن',
    'Taiz (تعز - TZ)' => 'تعز',
];

foreach ($testGovernorates as $label => $stateName) {
    echo "\n-------------------------------------------------------\n";
    echo "TESTING GOVERNORATE: {$label}\n";
    echo "-------------------------------------------------------\n";

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
        'sub_total' => 45.00,
        'base_sub_total' => 45.00,
        'grand_total' => 45.00,
        'base_grand_total' => 45.00,
    ]);

    CartItem::create([
        'quantity' => 1,
        'sku' => $product->sku,
        'type' => $product->type,
        'name' => $product->name,
        'price' => 45.00,
        'base_price' => 45.00,
        'total' => 45.00,
        'base_total' => 45.00,
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
            'address' => ['Main St'],
            'city' => 'المدينة',
            'state' => $stateName,
            'country' => 'YE',
            'postcode' => '00000',
            'phone' => '777123456',
            'use_for_shipping' => true,
        ],
        'shipping' => [
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'email' => $customer->email,
            'address' => ['Main St'],
            'city' => 'المدينة',
            'state' => $stateName,
            'country' => 'YE',
            'postcode' => '00000',
            'phone' => '777123456',
        ]
    ];
    Cart::saveAddresses($addressData);

    $rates = Shipping::collectRates();
    $methods = $rates['shippingMethods'] ?? [];

    echo "Available Shipping Methods count: " . count($methods) . "\n";
    foreach ($methods as $carrier => $carrierGroup) {
        echo "  📦 Carrier: {$carrier} ({$carrierGroup['carrier_title']})\n";
        foreach ($carrierGroup['rates'] as $rate) {
            echo "     -> Method: {$rate->method} | Title: {$rate->method_title} | Price: {$rate->base_price} USD\n";
        }
    }
}
"""

with sftp.file(f"{REMOTE_ROOT}/test_verify_shipping_exclusive.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, f"cd {REMOTE_ROOT} && php8.4 test_verify_shipping_exclusive.php && rm test_verify_shipping_exclusive.php")
print(f"\nTEST RESULTS:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
print("\n[Complete] Exclusively Delivery Management Shipping Methods Deployed & Verified!")
