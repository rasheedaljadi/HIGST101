<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\Cart as CartModel;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartPayment;
use Webkul\DeliveryManagement\Database\Seeders\DeliveryGovernorateRulesSeeder;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryGovernorateRule;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\DeliveryManagement\Services\GovernorateDeliveryValidator;
use Webkul\DeliveryManagement\Services\PaymentEligibilityChecker;
use Webkul\DeliveryManagement\Services\ShippingMethodAdapter;
use Webkul\Payment\Payment\CashOnDelivery;
use Webkul\Payment\Payment\MoneyTransfer;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Shop\Http\Controllers\API\OnepageController;

class Phase3CheckoutPaymentEligibilityTest extends TestCase
{
    protected ShippingMethodAdapter $shippingMethodAdapter;

    protected GovernorateDeliveryValidator $governorateDeliveryValidator;

    protected PaymentEligibilityChecker $paymentEligibilityChecker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DeliveryGovernorateRulesSeeder::class);

        DB::table('core_config')->updateOrInsert(
            ['code' => 'sales.payment_methods.cashondelivery.active'],
            [
                'value' => '1',
                'channel_code' => 'default',
                'locale_code' => 'ar',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('core_config')->updateOrInsert(
            ['code' => 'sales.payment_methods.moneytransfer.active'],
            [
                'value' => '1',
                'channel_code' => 'default',
                'locale_code' => 'ar',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->shippingMethodAdapter = app(ShippingMethodAdapter::class);
        $this->governorateDeliveryValidator = app(GovernorateDeliveryValidator::class);
        $this->paymentEligibilityChecker = app(PaymentEligibilityChecker::class);
    }

    protected function createTestCart(array $attributes = []): CartModel
    {
        $localeId = DB::table('locales')->where('code', 'ar')->value('id')
            ?? DB::table('locales')->insertGetId([
                'code' => 'ar',
                'name' => 'Arabic',
                'direction' => 'rtl',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $currencyId = DB::table('currencies')->where('code', 'YER')->value('id')
            ?? DB::table('currencies')->insertGetId([
                'code' => 'YER',
                'name' => 'Yemeni Rial',
                'symbol' => 'YER',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $channelId = DB::table('channels')->where('code', 'default')->value('id')
            ?? DB::table('channels')->insertGetId([
                'code' => 'default',
                'theme' => 'default',
                'hostname' => 'localhost',
                'default_locale_id' => $localeId,
                'base_currency_id' => $currencyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return CartModel::create(array_merge([
            'channel_id' => $channelId,
            'is_guest' => 1,
            'is_active' => 1,
            'items_count' => 1,
            'items_qty' => 1,
            'grand_total' => 5000,
            'base_grand_total' => 5000,
            'shipping_method' => 'deliverypoint_pickup',
        ], $attributes));
    }

    /**
     * 1. Canonical Shipping Adapter maps exclusive codes and rejects generic/unknown carrier codes.
     */
    public function test_canonical_shipping_adapter_maps_legacy_and_canonical_codes(): void
    {
        $this->assertEquals('home_delivery', $this->shippingMethodAdapter->canonicalize('homedelivery_standard'));
        $this->assertEquals('home_delivery', $this->shippingMethodAdapter->canonicalize('homedelivery'));
        $this->assertEquals('home_delivery', $this->shippingMethodAdapter->canonicalize('home_delivery'));

        // Flatrate and generic carrier methods are rejected and return null
        $this->assertNull($this->shippingMethodAdapter->canonicalize('flatrate_flatrate'));
        $this->assertNull($this->shippingMethodAdapter->canonicalize('flatrate'));
        $this->assertNull($this->shippingMethodAdapter->canonicalize('free_free'));
        $this->assertNull($this->shippingMethodAdapter->canonicalize(null));

        $this->assertEquals('delivery_point', $this->shippingMethodAdapter->canonicalize('deliverypoint_pickup'));
        $this->assertEquals('delivery_point', $this->shippingMethodAdapter->canonicalize('deliverypoint'));
        $this->assertEquals('delivery_point', $this->shippingMethodAdapter->canonicalize('delivery_point'));
        $this->assertEquals('delivery_point', $this->shippingMethodAdapter->canonicalize('pickup'));

        $this->assertTrue($this->shippingMethodAdapter->isHomeDelivery('homedelivery_standard'));
        $this->assertTrue($this->shippingMethodAdapter->isDeliveryPoint('deliverypoint_pickup'));
        $this->assertFalse($this->shippingMethodAdapter->isDeliveryPoint('homedelivery_standard'));
        $this->assertFalse($this->shippingMethodAdapter->isHomeDelivery('deliverypoint_pickup'));
        $this->assertFalse($this->shippingMethodAdapter->isHomeDelivery('flatrate_flatrate'));
    }

    /**
     * 1b. Flatrate_flatrate does NOT automatically grant COD or home_delivery privileges.
     */
    public function test_flatrate_flatrate_does_not_grant_cod_automatically(): void
    {
        $this->assertFalse($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            stateCode: 'SAN',
            deliveryType: 'flatrate_flatrate'
        ));

        $this->assertFalse($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            stateCode: 'SAN',
            deliveryType: 'flatrate'
        ));
    }

    /**
     * 2. Governorate rules: Home delivery enabled for Sanaa (SAN) but disabled for other governorates by default.
     */
    public function test_governorate_rules_dynamic_lookup(): void
    {
        // Sanaa (SAN)
        $this->assertTrue($this->governorateDeliveryValidator->isDeliveryTypeEnabled('SAN', 'home_delivery'));
        $this->assertTrue($this->governorateDeliveryValidator->isDeliveryTypeEnabled('SAN', 'homedelivery_standard'));
        $this->assertTrue($this->governorateDeliveryValidator->isDeliveryTypeEnabled('SAN', 'delivery_point'));

        // Aden (AD)
        $this->assertFalse($this->governorateDeliveryValidator->isDeliveryTypeEnabled('AD', 'home_delivery'));
        $this->assertFalse($this->governorateDeliveryValidator->isDeliveryTypeEnabled('AD', 'homedelivery_standard'));
        $this->assertTrue($this->governorateDeliveryValidator->isDeliveryTypeEnabled('AD', 'delivery_point'));

        // Taiz (TZ)
        $this->assertFalse($this->governorateDeliveryValidator->isDeliveryTypeEnabled('TZ', 'home_delivery'));
        $this->assertTrue($this->governorateDeliveryValidator->isDeliveryTypeEnabled('TZ', 'delivery_point'));
    }

    /**
     * 3. COD is allowed for SAN + home_delivery, but rejected for SAN + delivery_point and all other governorates.
     */
    public function test_cod_eligibility_matrix(): void
    {
        $sanPoint = DeliveryPoint::create([
            'code' => 'SAN-P1',
            'name' => 'Sanaa Hadda Point',
            'state_code' => 'SAN',
            'city' => 'Sanaa',
            'address' => 'Hadda Street',
            'is_active' => true,
        ]);

        $adPoint = DeliveryPoint::create([
            'code' => 'AD-P1',
            'name' => 'Aden Crater Point',
            'state_code' => 'AD',
            'city' => 'Aden',
            'address' => 'Crater',
            'is_active' => true,
        ]);

        // SAN + home_delivery => COD ALLOWED
        $this->assertTrue($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            stateCode: 'SAN',
            deliveryType: 'home_delivery'
        ));

        $this->assertTrue($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            stateCode: 'SAN',
            deliveryType: 'homedelivery_standard'
        ));

        // SAN + delivery_point => COD STRICTLY FORBIDDEN
        $this->assertFalse($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            stateCode: 'SAN',
            deliveryType: 'delivery_point',
            deliveryPointId: $sanPoint->id
        ));

        $this->assertFalse($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            stateCode: 'SAN',
            deliveryType: 'deliverypoint_pickup',
            deliveryPointId: $sanPoint->id
        ));

        // AD + home_delivery => COD FORBIDDEN (home delivery disabled)
        $this->assertFalse($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            stateCode: 'AD',
            deliveryType: 'home_delivery'
        ));

        // AD + delivery_point => COD STRICTLY FORBIDDEN
        $this->assertFalse($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'cashondelivery',
            stateCode: 'AD',
            deliveryType: 'delivery_point',
            deliveryPointId: $adPoint->id
        ));

        // Money transfer / electronic payment is allowed for delivery points in SAN and AD
        $this->assertTrue($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'moneytransfer',
            stateCode: 'SAN',
            deliveryType: 'delivery_point',
            deliveryPointId: $sanPoint->id
        ));

        $this->assertTrue($this->paymentEligibilityChecker->isEligible(
            paymentMethod: 'moneytransfer',
            stateCode: 'AD',
            deliveryType: 'delivery_point',
            deliveryPointId: $adPoint->id
        ));
    }

    /**
     * 4. Delivery point validation rejects non-existent, inactive, or governorate mismatch points.
     */
    public function test_delivery_point_validation_rules(): void
    {
        $sanActivePoint = DeliveryPoint::create([
            'code' => 'SAN-VALID',
            'name' => 'Sanaa Active Point',
            'name_ar' => 'نقطة صنعاء المفعلة',
            'state_code' => 'SAN',
            'city' => 'Sanaa',
            'address' => 'Al-Zubairi St',
            'contact_name' => 'Ali',
            'contact_phone' => '777000111',
            'is_active' => true,
        ]);

        // A. Valid point returns frozen snapshot
        $snapshot = $this->governorateDeliveryValidator->validateDeliveryPoint('SAN', $sanActivePoint->id);
        $this->assertIsArray($snapshot);
        $this->assertEquals($sanActivePoint->id, $snapshot['id']);
        $this->assertEquals('SAN-VALID', $snapshot['code']);
        $this->assertEquals('Sanaa Active Point', $snapshot['name']);
        $this->assertEquals('نقطة صنعاء المفعلة', $snapshot['name_ar']);
        $this->assertArrayHasKey('snapshot_created_at', $snapshot);

        // B. Non-existent point throws ValidationException
        $this->expectException(ValidationException::class);
        $this->governorateDeliveryValidator->validateDeliveryPoint('SAN', 99999);
    }

    /**
     * 4b. Inactive point throws ValidationException.
     */
    public function test_delivery_point_validation_rejects_inactive_point(): void
    {
        $sanInactivePoint = DeliveryPoint::create([
            'code' => 'SAN-INACTIVE-2',
            'name' => 'Sanaa Inactive Point',
            'state_code' => 'SAN',
            'city' => 'Sanaa',
            'address' => 'Airport Rd',
            'is_active' => false,
        ]);

        $this->expectException(ValidationException::class);
        $this->governorateDeliveryValidator->validateDeliveryPoint('SAN', $sanInactivePoint->id);
    }

    /**
     * 4c. Governorate mismatch throws ValidationException.
     */
    public function test_delivery_point_validation_rejects_governorate_mismatch(): void
    {
        $adPoint = DeliveryPoint::create([
            'code' => 'AD-MISMATCH',
            'name' => 'Aden Point',
            'state_code' => 'AD',
            'city' => 'Aden',
            'address' => 'Maalla',
            'is_active' => true,
        ]);

        // Trying to use Aden point with Sanaa state
        $this->expectException(ValidationException::class);
        $this->governorateDeliveryValidator->validateDeliveryPoint('SAN', $adPoint->id);
    }

    /**
     * 5. Server Point 1: CashOnDelivery::isAvailable() returns false for cart with delivery point or non-SAN governorate.
     */
    public function test_server_point_1_payment_method_filtering(): void
    {
        $cart = $this->createTestCart([
            'shipping_method' => 'deliverypoint_pickup',
        ]);

        $address = CartAddress::create([
            'cart_id' => $cart->id,
            'address_type' => 'cart_shipping',
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'email' => 'ahmed@example.com',
            'phone' => '777123456',
            'address' => 'Street 14',
            'city' => 'Sanaa',
            'state' => 'SAN',
            'country' => 'YE',
        ]);

        $cart->setRelation('shipping_address', $address);
        $cart->setRelation('items', collect());

        $cod = app(CashOnDelivery::class);
        $cod->setCart($cart);

        // COD is NOT available when deliverypoint_pickup is chosen in Sanaa
        $this->assertFalse($cod->isAvailable());

        // When switching shipping method to homedelivery_standard in Sanaa, COD becomes available
        $cart->shipping_method = 'homedelivery_standard';
        $this->assertTrue($cod->isAvailable());

        // When switching address state to Aden (AD), COD is NOT available even with home delivery
        $address->state = 'AD';
        $this->assertFalse($cod->isAvailable());
    }

    /**
     * 6. Server Point 2: OnepageController::storePaymentMethod() rejects COD with HTTP 422 if ineligible.
     */
    public function test_server_point_2_store_payment_method_rejects_ineligible_cod(): void
    {
        $cart = $this->createTestCart([
            'shipping_method' => 'deliverypoint_pickup',
        ]);

        $address = CartAddress::create([
            'cart_id' => $cart->id,
            'address_type' => 'cart_shipping',
            'first_name' => 'Test',
            'last_name' => 'User',
            'address' => 'Point St',
            'city' => 'Sanaa',
            'state' => 'SAN',
            'country' => 'YE',
            'phone' => '777000111',
        ]);

        $cart->setRelation('shipping_address', $address);
        $cart->setRelation('items', collect());
        Cart::setCart($cart);

        request()->merge(['payment' => ['method' => 'cashondelivery']]);

        $controller = app(OnepageController::class);
        $response = $controller->storePaymentMethod();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('payment', $data['errors']);
    }

    /**
     * 7. Server Point 3: OnepageController::validateOrder() rejects bypass attempts with HTTP 422 exception.
     */
    public function test_server_point_3_validate_order_rejects_bypass_attempts(): void
    {
        $cart = $this->createTestCart([
            'shipping_method' => 'deliverypoint_pickup',
        ]);

        $address = CartAddress::create([
            'cart_id' => $cart->id,
            'address_type' => 'cart_shipping',
            'first_name' => 'Test',
            'last_name' => 'Bypass',
            'address' => 'Crater',
            'city' => 'Aden',
            'state' => 'AD',
            'country' => 'YE',
            'phone' => '777000111',
        ]);

        $payment = new CartPayment;
        $payment->cart_id = $cart->id;
        $payment->method = 'cashondelivery';
        $payment->method_title = 'Cash On Delivery';
        $payment->save();

        $cart->setRelation('shipping_address', $address);
        $cart->setRelation('billing_address', $address);
        $cart->setRelation('payment', $payment);
        $cart->setRelation('items', collect());
        Cart::setCart($cart);

        $controller = app(OnepageController::class);

        $this->expectException(\Exception::class);
        $controller->validateOrder();
    }

    /**
     * 8. Order creation generates immutable frozen snapshot in DeliveryAssignment.
     */
    public function test_order_creation_preserves_immutable_delivery_snapshot(): void
    {
        $point = DeliveryPoint::create([
            'code' => 'SAN-SNAP-1',
            'name' => 'Sanaa Central Pickup Hub',
            'name_ar' => 'مركز توزيع صنعاء الرئيسي',
            'state_code' => 'SAN',
            'city' => 'Sanaa',
            'address' => 'Sixty Meter Road, near Sanaa Mall',
            'contact_name' => 'Mazen',
            'contact_phone' => '777555444',
            'working_hours' => ['from' => '09:00', 'to' => '21:00'],
            'is_active' => true,
        ]);

        $order = Order::create([
            'increment_id' => 'ORD-TEST-'.uniqid(),
            'status' => 'pending',
            'is_guest' => 1,
            'customer_email' => 'customer@example.com',
            'customer_first_name' => 'Yemen',
            'customer_last_name' => 'Customer',
            'shipping_method' => 'deliverypoint_pickup',
            'shipping_title' => 'Pickup from Point',
            'grand_total' => 15000,
            'base_grand_total' => 15000,
        ]);

        $shippingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_shipping',
            'first_name' => 'Yemen',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '777999888',
            'address' => 'Sanaa, Sixty Meter Road',
            'city' => 'Sanaa',
            'state' => 'SAN',
            'country' => 'YE',
            'additional' => [
                'delivery_point_id' => $point->id,
            ],
        ]);

        $order->setRelation('shipping_address', $shippingAddress);

        // Fire order created event
        event('sales.order.save.after', $order);

        $assignment = DeliveryAssignment::where('order_id', $order->id)->first();

        $this->assertNotNull($assignment);
        $this->assertEquals('delivery_point', $assignment->delivery_type);
        $this->assertEquals($point->id, $assignment->delivery_point_id);
        $this->assertEquals(DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT, $assignment->status);

        $snapshot = $assignment->delivery_point_snapshot;
        $this->assertIsArray($snapshot);
        $this->assertEquals('SAN-SNAP-1', $snapshot['code']);
        $this->assertEquals('Sanaa Central Pickup Hub', $snapshot['name']);
        $this->assertEquals('مركز توزيع صنعاء الرئيسي', $snapshot['name_ar']);

        // Now simulate delivery point row being changed or deactivated in DB later
        $point->update([
            'name' => 'MODIFIED NAME AFTER CLOSING',
            'name_ar' => 'تم تغيير الاسم وإغلاق النقطة',
            'is_active' => false,
            'address' => 'Old address demolished',
        ]);

        // Refresh assignment and verify frozen snapshot remained 100% intact
        $assignment->refresh();
        $this->assertEquals('Sanaa Central Pickup Hub', $assignment->delivery_point_snapshot['name']);
        $this->assertEquals('مركز توزيع صنعاء الرئيسي', $assignment->delivery_point_snapshot['name_ar']);
        $this->assertEquals('Sixty Meter Road, near Sanaa Mall', $assignment->delivery_point_snapshot['address']);
    }

    /**
     * 8b. Historical order without snapshot (e.g. legacy flatrate) is NOT mutated and does not create DeliveryAssignment.
     */
    public function test_historical_legacy_order_without_snapshot_is_not_mutated(): void
    {
        $order = Order::create([
            'increment_id' => 'ORD-LEGACY-'.uniqid(),
            'status' => 'completed',
            'is_guest' => 1,
            'customer_email' => 'legacy@example.com',
            'customer_first_name' => 'Old',
            'customer_last_name' => 'Customer',
            'shipping_method' => 'flatrate_flatrate',
            'shipping_title' => 'Flat Rate',
            'grand_total' => 8000,
            'base_grand_total' => 8000,
        ]);

        $shippingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_shipping',
            'first_name' => 'Old',
            'last_name' => 'Customer',
            'email' => 'legacy@example.com',
            'phone' => '777111222',
            'address' => 'Historical Address',
            'city' => 'Sanaa',
            'state' => 'SAN',
            'country' => 'YE',
        ]);

        $order->setRelation('shipping_address', $shippingAddress);

        event('sales.order.save.after', $order);

        $assignment = DeliveryAssignment::where('order_id', $order->id)->first();
        $this->assertNull($assignment);
    }

    /**
     * 8c. Changing governorate rules dynamically does NOT alter existing historical order snapshots.
     */
    public function test_changing_governorate_rules_does_not_alter_historical_order_snapshot(): void
    {
        $order = Order::create([
            'increment_id' => 'ORD-GOV-CHANGE-'.uniqid(),
            'status' => 'pending',
            'is_guest' => 1,
            'customer_email' => 'customer@example.com',
            'customer_first_name' => 'Historical',
            'customer_last_name' => 'Buyer',
            'shipping_method' => 'homedelivery_standard',
            'shipping_title' => 'Home Delivery',
            'grand_total' => 12000,
            'base_grand_total' => 12000,
        ]);

        $shippingAddress = OrderAddress::create([
            'order_id' => $order->id,
            'address_type' => 'order_shipping',
            'first_name' => 'Historical',
            'last_name' => 'Buyer',
            'email' => 'customer@example.com',
            'phone' => '777888999',
            'address' => 'Historical Hadda Street',
            'city' => 'Sanaa',
            'state' => 'SAN',
            'country' => 'YE',
        ]);

        $order->setRelation('shipping_address', $shippingAddress);

        event('sales.order.save.after', $order);

        $assignment = DeliveryAssignment::where('order_id', $order->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals('Historical Hadda Street', $assignment->customer_address_snapshot['address']);

        // Now modify or disable governorate rules
        DeliveryGovernorateRule::where('state_code', 'SAN')->update(['is_enabled' => false]);

        $assignment->refresh();
        $this->assertEquals('Historical Hadda Street', $assignment->customer_address_snapshot['address']);
        $this->assertEquals('home_delivery', $assignment->delivery_type);
    }

    /**
     * 8d. Payment::setCart does not break other payment methods like MoneyTransfer.
     */
    public function test_payment_set_cart_does_not_break_other_payment_methods(): void
    {
        $cart = $this->createTestCart([
            'shipping_method' => 'homedelivery_standard',
        ]);

        $moneyTransfer = app(MoneyTransfer::class);
        $moneyTransfer->setCart($cart);

        $this->assertTrue($moneyTransfer->isAvailable());
    }

    /**
     * 9. Stock safety: Payment and checkout eligibility tests NEVER alter product_inventories or hayest_central stock.
     */
    public function test_payment_and_checkout_tests_do_not_alter_inventory(): void
    {
        $centralSource = DB::table('inventory_sources')->where('code', 'hayest_central')->first();
        $this->assertNotNull($centralSource);

        $product = Product::create([
            'type' => 'simple',
            'sku' => 'TEST-STOCK-GUARD-'.uniqid(),
        ]);

        $initialStock = 25;
        DB::table('product_inventories')->insert([
            'product_id' => $product->id,
            'inventory_source_id' => $centralSource->id,
            'qty' => $initialStock,
        ]);

        // Run multiple eligibility checks
        $this->paymentEligibilityChecker->isEligible('cashondelivery', 'SAN', 'home_delivery');
        $this->paymentEligibilityChecker->isEligible('cashondelivery', 'AD', 'delivery_point');
        $this->paymentEligibilityChecker->isEligible('moneytransfer', 'SAN', 'delivery_point');

        // Verify stock is completely unchanged
        $stockAfter = DB::table('product_inventories')
            ->where('product_id', $product->id)
            ->where('inventory_source_id', $centralSource->id)
            ->value('qty');

        $this->assertEquals($initialStock, $stockAfter);
    }
}
