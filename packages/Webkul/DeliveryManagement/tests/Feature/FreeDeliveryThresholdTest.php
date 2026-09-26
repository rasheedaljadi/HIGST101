<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Tests\TestCase;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\Cart as CartModel;
use Webkul\Checkout\Models\CartAddress;
use Webkul\DeliveryManagement\Carriers\DeliveryPoint as DeliveryPointCarrier;
use Webkul\DeliveryManagement\Carriers\HomeDelivery as HomeDeliveryCarrier;
use Webkul\DeliveryManagement\Models\DeliveryGovernorateRule;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class FreeDeliveryThresholdTest extends TestCase
{
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['permission_type' => 'all']
        );

        $this->admin = Admin::firstOrCreate(
            ['email' => 'free_shipping_test_admin@example.com'],
            [
                'name' => 'Free Shipping Admin',
                'password' => bcrypt('secret123'),
                'role_id' => $adminRole->id,
                'status' => 1,
            ]
        );
    }

    public function test_admin_can_set_and_update_free_delivery_threshold(): void
    {
        $rule = DeliveryGovernorateRule::updateOrCreate(
            ['state_code' => 'SAN', 'delivery_type' => 'home_delivery'],
            [
                'is_enabled' => true,
                'allowed_payment_methods' => ['cashondelivery', 'moneytransfer'],
                'delivery_fee' => 1500.00,
                'free_delivery_threshold' => null,
                'min_order_amount' => 0.00,
            ]
        );

        // Update with threshold
        $response = $this->actingAs($this->admin, 'admin')->put(route('admin.delivery.rules.update', $rule->id), [
            'delivery_fee' => 1500.00,
            'free_delivery_threshold' => 25000.00,
            'min_order_amount' => 0.00,
            'is_enabled' => 1,
            'allowed_payment_methods' => ['cashondelivery', 'moneytransfer'],
        ]);

        $response->assertRedirect(route('admin.delivery.rules.index'));

        $rule->refresh();
        $this->assertEquals(25000.00, (float) $rule->free_delivery_threshold);

        // Update with null / empty threshold
        $responseClear = $this->actingAs($this->admin, 'admin')->put(route('admin.delivery.rules.update', $rule->id), [
            'delivery_fee' => 1500.00,
            'free_delivery_threshold' => '',
            'min_order_amount' => 0.00,
            'is_enabled' => 1,
            'allowed_payment_methods' => ['cashondelivery', 'moneytransfer'],
        ]);

        $responseClear->assertRedirect(route('admin.delivery.rules.index'));

        $rule->refresh();
        $this->assertNull($rule->free_delivery_threshold);
    }

    public function test_home_delivery_carrier_rate_is_free_when_cart_meets_threshold(): void
    {
        $rule = DeliveryGovernorateRule::updateOrCreate(
            ['state_code' => 'SAN', 'delivery_type' => 'home_delivery'],
            [
                'is_enabled' => true,
                'allowed_payment_methods' => ['cashondelivery', 'moneytransfer'],
                'delivery_fee' => 5.00,
                'free_delivery_threshold' => 50.00,
                'min_order_amount' => 0.00,
            ]
        );

        // 1. Below threshold (subtotal = 30 USD)
        $mockCartBelow = new CartModel;
        $mockCartBelow->base_sub_total = 30.00;
        $mockAddress = new CartAddress;
        $mockAddress->state = 'SAN';
        $mockAddress->country = 'YE';
        $mockCartBelow->setRelation('shipping_address', $mockAddress);

        Cart::shouldReceive('getCart')->andReturn($mockCartBelow);

        $carrier = new HomeDeliveryCarrier;
        $rateBelow = $carrier->calculate();

        $this->assertNotFalse($rateBelow);
        $this->assertEquals(5.00, (float) $rateBelow->base_price);
        $this->assertStringContainsString('توصيل مجاني للطلبات أكبر من', $rateBelow->method_description);

        // 2. Above threshold (subtotal = 75 USD)
        $mockCartAbove = new CartModel;
        $mockCartAbove->base_sub_total = 75.00;
        $mockCartAbove->setRelation('shipping_address', $mockAddress);

        Cart::shouldReceive('getCart')->andReturn($mockCartAbove);

        $rateAbove = $carrier->calculate();

        $this->assertNotFalse($rateAbove);
        $this->assertEquals(0.00, (float) $rateAbove->base_price);
        $this->assertStringContainsString('مجاني', $rateAbove->method_title);
    }

    public function test_delivery_point_carrier_rate_is_free_when_cart_meets_threshold(): void
    {
        $rule = DeliveryGovernorateRule::updateOrCreate(
            ['state_code' => 'SAN', 'delivery_type' => 'delivery_point'],
            [
                'is_enabled' => true,
                'allowed_payment_methods' => ['moneytransfer'],
                'delivery_fee' => 3.00,
                'free_delivery_threshold' => 40.00,
                'min_order_amount' => 0.00,
            ]
        );

        $mockAddress = new CartAddress;
        $mockAddress->state = 'SAN';
        $mockAddress->country = 'YE';

        // Above threshold
        $mockCart = new CartModel;
        $mockCart->base_sub_total = 45.00;
        $mockCart->setRelation('shipping_address', $mockAddress);

        Cart::shouldReceive('getCart')->andReturn($mockCart);

        $carrier = new DeliveryPointCarrier;
        $rate = $carrier->calculate();

        $this->assertNotFalse($rate);
        $this->assertEquals(0.00, (float) $rate->base_price);
        $this->assertStringContainsString('مجاني', $rate->method_title);
    }
}
