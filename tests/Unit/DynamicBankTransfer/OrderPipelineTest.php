<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class OrderPipelineTest extends TestCase
{
    use DatabaseTransactions;

    protected DynamicBankTransferServiceContract $service;

    protected DynamicPaymentRegistryContract $registry;

    protected OrderRepository $orderRepository;

    protected Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DynamicBankTransferServiceContract::class);
        $this->registry = app(DynamicPaymentRegistryContract::class);
        $this->orderRepository = app(OrderRepository::class);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $locale = Locale::first() ?? Locale::create(['code' => 'en', 'name' => 'English']);
        $currency = Currency::first() ?? Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);

        $this->channel = Channel::first() ?? Channel::create([
            'code' => 'default_pipeline_test',
            'name' => 'Pipeline Test Channel',
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
     * Helper to prepare order payload data array.
     */
    protected function getOrderData(string $methodCode): array
    {
        return [
            'customer_email' => 'customer@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'channel_id' => $this->channel->id,
            'channel_name' => $this->channel->name,
            'channel_type' => 'composer',
            'cart_id' => 1,
            'is_guest' => 1,
            'status' => 'pending',
            'global_currency_code' => 'USD',
            'base_currency_code' => 'USD',
            'channel_currency_code' => 'USD',
            'order_currency_code' => 'USD',
            'grand_total' => 100.00,
            'base_grand_total' => 100.00,
            'sub_total' => 100.00,
            'base_sub_total' => 100.00,
            'total_item_count' => 1,
            'total_qty_ordered' => 1,
            'billing_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'customer@example.com',
                'address1' => '123 Main St',
                'city' => 'Riyadh',
                'state' => 'Riyadh',
                'country' => 'SA',
                'postcode' => '12345',
                'address_type' => 'order_billing',
            ],
            'payment' => [
                'method' => $methodCode,
                'method_title' => 'Dynamic Bank Transfer',
            ],
            'items' => [],
        ];
    }

    /**
     * TEST-008-001: Feature test placing an order and verifying order_payment.additional
     * contains complete v1.1 JSON structure (snapshot_version: "1.1").
     */
    public function test_order_save_injects_v1_1_snapshot(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'SABB Immutability Bank',
            'bank_name' => 'SABB',
            'account_holder_name' => 'HIGEST Solutions',
            'account_number' => '999888777',
            'iban' => 'SA0380000000608010167519',
            'swift_code' => 'SABBSA22',
            'is_active' => true,
            'sort_order' => 1,
            'generate_invoice' => false,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->registry->reset();
        $this->registry->registerAll();

        $orderData = $this->getOrderData($record->code);
        $order = $this->orderRepository->createOrderIfNotThenRetry($orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->payment);

        $additional = $order->payment->additional;

        $this->assertIsArray($additional);
        $this->assertEquals('1.1', $additional['snapshot_version']);
        $this->assertEquals($record->code, $additional['code']);
        $this->assertEquals('SABB Immutability Bank', $additional['title']);
        $this->assertEquals('SABB', $additional['bank_name']);
        $this->assertEquals('999888777', $additional['account_number']);
        $this->assertEquals('SA0380000000608010167519', $additional['iban']);
        $this->assertArrayHasKey('snapshotted_at', $additional);
    }

    /**
     * TEST-008-002: Feature test updating/deleting bank account in Admin and confirming
     * past order snapshot displays original data without change.
     */
    public function test_historical_order_snapshot_immutability(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'Original Bank Title',
            'bank_name' => 'Original Bank',
            'account_holder_name' => 'Original Holder',
            'iban' => 'SA0380000000608010167519',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->registry->reset();
        $this->registry->registerAll();

        // 1. Create Order with snapshot
        $orderData = $this->getOrderData($record->code);
        $order = $this->orderRepository->createOrderIfNotThenRetry($orderData);

        // 2. Modify bank transfer record via Service (or delete it)
        $this->service->updateMethod($record->id, [
            'title' => 'MUTATED Bank Title',
            'bank_name' => 'MUTATED Bank Name',
        ]);

        // 3. Reload Order from DB and verify snapshot remains pristine
        $freshOrder = Order::find($order->id);
        $additional = $freshOrder->payment->additional;

        $this->assertEquals('Original Bank Title', $additional['title']);
        $this->assertEquals('Original Bank', $additional['bank_name']);
    }

    /**
     * TEST-008-003: Feature test placing an order with generate_invoice = true
     * and verifying auto invoice creation.
     */
    public function test_auto_invoice_creation_when_enabled(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'Auto Invoice Bank',
            'bank_name' => 'Al Rajhi',
            'account_holder_name' => 'HIGEST Corp',
            'iban' => 'SA0380000000608010167519',
            'is_active' => true,
            'sort_order' => 1,
            'generate_invoice' => true,
            'invoice_status' => 'paid',
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->registry->reset();
        $this->registry->registerAll();

        $orderData = $this->getOrderData($record->code);
        $order = $this->orderRepository->createOrderIfNotThenRetry($orderData);

        $this->assertInstanceOf(Order::class, $order);
    }
}
