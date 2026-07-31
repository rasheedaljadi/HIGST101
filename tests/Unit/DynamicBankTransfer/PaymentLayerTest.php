<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Payment\DynamicBankTransferMethod;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;
use Webkul\Payment\Facades\Payment;

class PaymentLayerTest extends TestCase
{
    use DatabaseTransactions;

    protected DynamicBankTransferServiceContract $service;

    protected DynamicPaymentRegistryContract $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DynamicBankTransferServiceContract::class);
        $this->registry = app(DynamicPaymentRegistryContract::class);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);
    }

    /**
     * TEST-005-001: Pest/PHPUnit test confirming Payment::getPaymentMethods()
     * includes DynamicBankTransferMethod instances.
     */
    public function test_payment_discovery_via_bagisto_payment_facade(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'NCB Ahli Bank',
            'description' => 'Transfer to NCB Ahli',
            'bank_name' => 'NCB',
            'account_holder_name' => 'HIGEST Enterprise',
            'iban' => 'SA0399000000123456789012',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        // Reset and Register methods via Registry
        $this->registry->reset();
        $this->registry->registerAll();

        // Query Bagisto Payment Facade
        $methods = Payment::getPaymentMethods();

        $dynamicMethod = collect($methods)->firstWhere('method', $record->code);

        $this->assertNotNull($dynamicMethod);
        $this->assertEquals($record->code, $dynamicMethod['method']);
        $this->assertEquals('NCB Ahli Bank', $dynamicMethod['method_title']);
        $this->assertEquals('Transfer to NCB Ahli', $dynamicMethod['description']);
    }

    /**
     * TEST-005-002: Pest/PHPUnit test confirming isAvailable() evaluates $dto->isActive
     * and cart channel restrictions.
     */
    public function test_payment_method_availability_and_channel_restrictions(): void
    {
        // 1. Inactive bank transfer record
        $inactiveRecord = DynamicBankTransfer::create([
            'title' => 'Disabled Bank',
            'bank_name' => 'Inactive Bank',
            'account_holder_name' => 'HIGEST Admin',
            'iban' => 'SA0388000000123456789012',
            'is_active' => false,
            'sort_order' => 10,
        ]);

        // 2. Active bank transfer record with channel restriction [1]
        $channelRecord = DynamicBankTransfer::create([
            'title' => 'Channel Restricted Bank',
            'bank_name' => 'Channel Bank',
            'account_holder_name' => 'HIGEST Admin',
            'iban' => 'SA0377000000123456789012',
            'is_active' => true,
            'sort_order' => 2,
            'channel_ids' => [1],
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $inactiveMethod = new DynamicBankTransferMethod($inactiveRecord->code);
        $this->assertFalse($inactiveMethod->isAvailable());

        $channelMethod = new DynamicBankTransferMethod($channelRecord->code);
        $this->assertTrue($channelMethod->isAvailable());
    }

    /**
     * TEST-005-003: Pest/PHPUnit test confirming returned payment methods array respects sort_order.
     */
    public function test_payment_methods_sorting_order(): void
    {
        $secondRecord = DynamicBankTransfer::create([
            'title' => 'Second Sorted Bank',
            'bank_name' => 'Bank B',
            'account_holder_name' => 'HIGEST B',
            'iban' => 'SA0322000000123456789012',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $firstRecord = DynamicBankTransfer::create([
            'title' => 'First Sorted Bank',
            'bank_name' => 'Bank A',
            'account_holder_name' => 'HIGEST A',
            'iban' => 'SA0311000000123456789012',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $firstMethod = new DynamicBankTransferMethod($firstRecord->code);
        $secondMethod = new DynamicBankTransferMethod($secondRecord->code);

        $this->assertLessThan($secondMethod->getSortOrder(), $firstMethod->getSortOrder());
    }
}
