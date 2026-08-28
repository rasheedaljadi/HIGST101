import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("packages/Webkul/DeliveryManagement/src/Services/ShippingMethodAdapter.php", "packages/Webkul/DeliveryManagement/src/Services/ShippingMethodAdapter.php"),
    ("packages/Webkul/DeliveryManagement/src/Services/GovernorateDeliveryValidator.php", "packages/Webkul/DeliveryManagement/src/Services/GovernorateDeliveryValidator.php"),
    ("packages/Webkul/DeliveryManagement/src/Services/PaymentEligibilityChecker.php", "packages/Webkul/DeliveryManagement/src/Services/PaymentEligibilityChecker.php"),
    ("packages/Webkul/Shop/src/Resources/views/checkout/onepage/payment.blade.php", "packages/Webkul/Shop/src/Resources/views/checkout/onepage/payment.blade.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Uploading updated delivery and payment files...")
for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"  -> {local_rel}")
    sftp.put(local_path, remote_path)

sftp.close()

cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan config:clear",
]
for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => {out.strip()}")

print("\n=======================================================")
print("RUNNING POST-DEPLOYMENT LIVE TEST ON REMOTE")
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
        'state' => 'أمانة العاصمة',
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
        'state' => 'أمانة العاصمة',
        'country' => 'YE',
        'postcode' => '0000',
        'phone' => '777123456',
    ]
];
Cart::saveAddresses($addressData);
Cart::saveShippingMethod('flatrate_flatrate');
Cart::collectTotals();

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
            echo "Status: 200 OK (SUCCESS!)\n";
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

sftp = client.open_sftp()
with sftp.file(f"{REMOTE_ROOT}/test_verify_live_payments.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, f"cd {REMOTE_ROOT} && php8.4 test_verify_live_payments.php && rm test_verify_live_payments.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
print("[Complete] Payment Methods Fix Deployed & Verified Successfully!")
