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
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shipping\Facades\Shipping;

$product = Product::where('type', 'simple')->first() ?: Product::first();
$customer = Customer::first();
auth()->guard('customer')->setUser($customer);

echo "=== 1. CREATING CART WITH HOME DELIVERY ($5.00 FEE) ===\n";

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
Shipping::collectRates();
Cart::saveShippingMethod('homedelivery_standard');
Cart::savePaymentMethod(['method' => 'moneytransfer']);
Cart::collectTotals();

$cart = Cart::getCart();
echo "CART:\n";
echo "  - Subtotal: {$cart->sub_total}\n";
echo "  - Shipping Method: {$cart->shipping_method}\n";
echo "  - Shipping Amount: {$cart->shipping_amount}\n";
echo "  - Grand Total: {$cart->grand_total}\n";

echo "\n=== 2. CREATING ORDER ===\n";
$orderRepo = app(OrderRepository::class);
$orderData = (new OrderResource($cart))->jsonSerialize();
$order = $orderRepo->create($orderData);

echo "ORDER (ID: {$order->id}, Increment ID: {$order->increment_id}):\n";
echo "  - Subtotal: {$order->sub_total}\n";
echo "  - Shipping Method: {$order->shipping_method}\n";
echo "  - Shipping Title: {$order->shipping_title}\n";
echo "  - Shipping Description: {$order->shipping_description}\n";
echo "  - Shipping Amount: {$order->shipping_amount}\n";
echo "  - Grand Total: {$order->grand_total}\n";

echo "\n=== 3. CREATING INVOICE ===\n";
$invoiceRepo = app(InvoiceRepository::class);
$invoice = $invoiceRepo->create([
    'order_id' => $order->id,
    'invoice' => [
        'items' => [
            $order->items->first()->id => 1,
        ]
    ]
]);

echo "INVOICE (ID: {$invoice->id}, Increment ID: {$invoice->increment_id}):\n";
echo "  - Subtotal: {$invoice->sub_total}\n";
echo "  - Shipping Amount: {$invoice->shipping_amount}\n";
echo "  - Grand Total: {$invoice->grand_total}\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_fee_lifecycle.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_fee_lifecycle.php && rm test_fee_lifecycle.php")
print(f"OUTPUT:\n{out}")

client.close()
