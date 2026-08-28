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
use Illuminate\Http\Request;
use Webkul\Shop\Http\Controllers\API\OnepageController;

$cart = CartModel::with('items', 'shipping_address', 'billing_address')->latest('id')->first();
echo "Using latest Cart ID: {$cart->id} (is_guest: {$cart->is_guest}, items: {$cart->items->count()}, total: {$cart->grand_total})\n";

Cart::setCart($cart);

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
    echo "\n---> Testing storePaymentMethod for: '{$name}' <---\n";
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
            echo "Cart grand_total: " . ($response['cart']['grand_total'] ?? 'N/A') . "\n";
            echo "Cart payment_method: " . ($response['cart']['payment_method'] ?? 'N/A') . "\n";
        } else {
            echo "Response Type: " . get_class($response) . "\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "At: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_existing_cart.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_existing_cart.php && rm test_existing_cart.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
