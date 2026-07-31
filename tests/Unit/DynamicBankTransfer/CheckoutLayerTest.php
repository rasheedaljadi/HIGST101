<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Webkul\Checkout\Facades\Cart;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;
use Webkul\Payment\Facades\Payment;

class CheckoutLayerTest extends TestCase
{
    use DatabaseTransactions;

    protected DynamicBankTransferServiceContract $service;

    protected DynamicPaymentRegistryContract $registry;

    protected ?Channel $channel = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DynamicBankTransferServiceContract::class);
        $this->registry = app(DynamicPaymentRegistryContract::class);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $locale = Locale::first() ?? Locale::create(['code' => 'en', 'name' => 'English']);
        $currency = Currency::first() ?? Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);

        $this->channel = Channel::first() ?? Channel::create([
            'code' => 'default_test',
            'name' => 'Default Test Channel',
            'hostname' => 'localhost',
            'default_locale_id' => $locale->id,
            'base_currency_id' => $currency->id,
            'home_page_content' => 'Test Content',
            'footer_content' => 'Test Footer',
            'is_active' => 1,
        ]);

        core()->setCurrentChannel($this->channel);
        app()->setLocale($locale->code);
    }

    /**
     * TEST-007-001: Feature test confirming Payment::getPaymentMethods() returns
     * dynamic bank methods properly formatted for Vue storefront rendering.
     */
    public function test_checkout_payment_discovery_for_storefront(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'Riyad Bank Storefront',
            'description' => 'Transfer money via Riyad Bank',
            'bank_name' => 'Riyad Bank',
            'account_holder_name' => 'HIGEST E-Com',
            'iban' => 'SA0380000000608010167519',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->registry->reset();
        $this->registry->registerAll();

        $methods = Payment::getPaymentMethods();
        $dynamicOption = collect($methods)->firstWhere('method', $record->code);

        $this->assertNotNull($dynamicOption);
        $this->assertEquals($record->code, $dynamicOption['method']);
        $this->assertEquals('Riyad Bank Storefront', $dynamicOption['method_title']);
        $this->assertEquals('Transfer money via Riyad Bank', $dynamicOption['description']);
        $this->assertArrayHasKey('image', $dynamicOption);
    }

    /**
     * TEST-007-002: Feature test confirming Cart::savePaymentMethod() stores
     * dynamic method code in cart_payment table.
     */
    public function test_cart_save_payment_method_persistence(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'Alinma Bank Cart',
            'bank_name' => 'Alinma',
            'account_holder_name' => 'HIGEST Store',
            'iban' => 'SA0380000000608010167519',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->registry->reset();
        $this->registry->registerAll();

        // Create a cart model directly in DB using valid channel
        $cartModel = \Webkul\Checkout\Models\Cart::create([
            'customer_email' => 'customer@example.com',
            'channel_id' => $this->channel->id,
            'is_active' => 1,
            'global_currency_code' => 'USD',
            'base_currency_code' => 'USD',
            'channel_currency_code' => 'USD',
            'cart_currency_code' => 'USD',
        ]);

        Cart::setCart($cartModel);

        // Save dynamic bank payment method to cart
        $cartPayment = Cart::savePaymentMethod([
            'method' => $record->code,
        ]);

        $this->assertNotFalse($cartPayment);
        $this->assertEquals($record->code, $cartPayment->method);
        $this->assertEquals($cartModel->id, $cartPayment->cart_id);

        $this->assertDatabaseHas('cart_payment', [
            'cart_id' => $cartModel->id,
            'method' => $record->code,
        ]);
    }

    /**
     * TEST-007-003: Feature test verifying unavailable methods (disabled or channel restricted)
     * are excluded from Storefront payment methods.
     */
    public function test_unavailable_methods_excluded_from_checkout(): void
    {
        $disabledRecord = DynamicBankTransfer::create([
            'title' => 'Disabled Bank Method',
            'bank_name' => 'Disabled Bank',
            'account_holder_name' => 'HIGEST Store',
            'iban' => 'SA0380000000608010167519',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->registry->reset();
        $this->registry->registerAll();

        $methods = Payment::getPaymentMethods();
        $disabledOption = collect($methods)->firstWhere('method', $disabledRecord->code);

        $this->assertNull($disabledOption);
    }
}
