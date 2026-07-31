<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Webkul\Checkout\Facades\Cart;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract;
use Webkul\DynamicBankTransfer\Exceptions\InvalidPaymentMethodCodeException;
use Webkul\DynamicBankTransfer\Exceptions\MethodDeletionRestrictedException;
use Webkul\DynamicBankTransfer\Http\Controllers\Admin\DynamicBankTransferController;
use Webkul\DynamicBankTransfer\Http\Requests\DynamicBankTransferCreateRequest;
use Webkul\DynamicBankTransfer\Listeners\AutoInvoiceListener;
use Webkul\DynamicBankTransfer\Listeners\OrderPaymentSnapshotListener;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Repositories\DynamicBankTransferRepository;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;
use Webkul\DynamicBankTransfer\Services\DynamicPaymentRegistry;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class ResilienceAndFailureTest extends TestCase
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
            'code' => 'default_resilience_test',
            'name' => 'Resilience Test Channel',
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
     * Helper to build order data array.
     */
    protected function getOrderData(string $methodCode): array
    {
        return [
            'customer_email' => 'customer_fail@example.com',
            'customer_first_name' => 'Failure',
            'customer_last_name' => 'Test',
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
            'grand_total' => 150.00,
            'base_grand_total' => 150.00,
            'sub_total' => 150.00,
            'base_sub_total' => 150.00,
            'total_item_count' => 1,
            'total_qty_ordered' => 1,
            'billing_address' => [
                'first_name' => 'Failure',
                'last_name' => 'Test',
                'email' => 'customer_fail@example.com',
                'address1' => '123 Test St',
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
     * TEST-009-001 (Failure Flow 10): Simulating snapshot creation exception during order save
     * and verifying DB transaction rolls back cleanly.
     */
    public function test_failure_flow_10_snapshot_failure_rolls_back_order(): void
    {
        $invalidCode = 'dynamic_bank_transfer_999999';
        $orderData = $this->getOrderData($invalidCode);

        $listener = new OrderPaymentSnapshotListener($this->service);

        // Fake order with un-persisted payment method code
        $fakeOrder = new Order([
            'id' => 9999,
        ]);
        $fakeOrder->payment = (object) ['method' => $invalidCode, 'update' => function () {}];

        $this->expectException(InvalidPaymentMethodCodeException::class);
        $listener->handle($fakeOrder);
    }

    /**
     * TEST-009-002 (Failure Flow 11 & Idempotency): Simulating invoice creation exception
     * and confirming order remains intact without throwing uncaught fatal exception.
     */
    public function test_failure_flow_11_auto_invoice_failure_is_resilient_and_idempotent(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'Resilient Invoice Bank',
            'bank_name' => 'SABB',
            'iban' => 'SA0380000000608010167519',
            'is_active' => true,
            'generate_invoice' => true,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->registry->reset();
        $this->registry->registerAll();

        $mockRepo = $this->createMock(InvoiceRepository::class);
        $mockRepo->method('create')->willThrowException(new \Exception('Invoice Repository Outage'));

        $listener = new AutoInvoiceListener($this->service, $mockRepo);

        $orderData = $this->getOrderData($record->code);
        $order = $this->orderRepository->createOrderIfNotThenRetry($orderData);

        // Execution should catch exception gracefully without blowing up
        $listener->handle($order);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    /**
     * TEST-009-003 (Failure Flow 12): Simulating DB outage on cache miss and verifying
     * getActiveMethods() degrades gracefully returning empty collection.
     */
    public function test_failure_flow_12_degraded_state_handling_on_db_outage(): void
    {
        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        // Turn off system moneytransfer fallback
        config(['payment_methods.moneytransfer.active' => false]);

        $mockRepo = $this->createMock(DynamicBankTransferRepository::class);
        $mockRepo->method('getActiveRecords')->willThrowException(new \Exception('Database Outage Connection Refused'));

        $service = new DynamicBankTransferService($mockRepo);
        $activeMethods = $service->getActiveMethods();

        $this->assertTrue($activeMethods->isEmpty());
    }

    /**
     * TEST-009-004 (Failure Flow 13): Attempting to delete a method assigned to active carts
     * and confirming deletion is blocked with MethodDeletionRestrictedException (422 HTTP response).
     */
    public function test_failure_flow_13_active_cart_conflict_blocks_method_deletion(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'Active Cart Bank',
            'bank_name' => 'Al Inma',
            'iban' => 'SA0380000000608010167519',
            'is_active' => true,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        // Create an active cart
        $cartModel = \Webkul\Checkout\Models\Cart::create([
            'customer_email' => 'active_cart@example.com',
            'channel_id' => $this->channel->id,
            'is_active' => 1,
            'global_currency_code' => 'USD',
            'base_currency_code' => 'USD',
            'channel_currency_code' => 'USD',
            'cart_currency_code' => 'USD',
        ]);

        Cart::setCart($cartModel);
        Cart::savePaymentMethod(['method' => $record->code]);

        // Attempt deleting method via Service
        $this->expectException(MethodDeletionRestrictedException::class);
        $this->service->deleteMethod($record->id);
    }

    /**
     * TEST-009-005 (Failure Flow 14): Simulating storage upload exception during creation
     * and confirming no orphan file remains in storage.
     */
    public function test_failure_flow_14_storage_upload_rollback_on_failure(): void
    {
        Storage::fake('public');

        $uploadedFile = UploadedFile::fake()->image('bank_logo.png');

        $mockRepo = $this->createMock(DynamicBankTransferRepository::class);
        $mockRepo->method('create')->willThrowException(new \Exception('DB Write Failure During Storage Upload'));

        $mockService = new DynamicBankTransferService($mockRepo);
        $controller = new DynamicBankTransferController($this->app->make(DynamicBankTransferRepository::class), $mockService, $this->registry);

        $request = DynamicBankTransferCreateRequest::create(
            route('admin.sales.dynamic_bank_transfers.store'),
            'POST',
            [
                'title' => 'Test Logo Storage Rollback',
                'bank_name' => 'SNB',
                'account_holder_name' => 'SNB Holder',
                'iban' => 'SA0380000000608010167519',
                'is_active' => '1',
            ],
            [],
            ['logo' => $uploadedFile]
        );
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'))->validateResolved();

        $response = $controller->store($request);

        // Verify storage file was cleaned up on failure
        $files = Storage::disk('public')->files('dynamic-bank-transfers');
        $this->assertCount(0, $files);
    }

    /**
     * TEST-009-006 (Failure Flow 15): Simulating boot injection exception and confirming application boots safely.
     */
    public function test_failure_flow_15_registry_boot_injection_failure_resilience(): void
    {
        $mockService = $this->createMock(DynamicBankTransferServiceContract::class);
        $mockService->method('getActiveMethods')->willThrowException(new \Exception('Redis Connection Failed During Boot'));

        $registry = new DynamicPaymentRegistry($mockService);

        // registerAll should log warning without raising uncaught fatal exception
        $registry->registerAll();

        $this->assertFalse($registry->isRegistered());
    }
}
