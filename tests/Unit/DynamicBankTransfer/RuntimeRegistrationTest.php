<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;
use Webkul\DynamicBankTransfer\Services\DynamicPaymentRegistry;

class RuntimeRegistrationTest extends TestCase
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
     * TEST-004-001: Pest/PHPUnit test confirming config('payment_methods') and config('core')
     * contain dynamic bank method keys after registerAll().
     */
    public function test_runtime_registration_injects_configs(): void
    {
        // 1. Create a dynamic bank transfer record via Service (or clear cache after Eloquent create)
        $record = DynamicBankTransfer::create([
            'title' => 'SABB First Bank',
            'description' => 'Transfer to SABB account',
            'bank_name' => 'SABB',
            'account_holder_name' => 'HIGEST Solutions',
            'iban' => 'SA0345000000123456789012',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Clear cache so service picks up newly created Eloquent record
        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        // 2. Instantiate a fresh registry engine
        $freshRegistry = new DynamicPaymentRegistry($this->service);
        $this->assertFalse($freshRegistry->isRegistered());

        // 3. Execute registerAll()
        $freshRegistry->registerAll();

        $this->assertTrue($freshRegistry->isRegistered());

        // 4. Verify config('payment_methods') injection
        $paymentConfig = config("payment_methods.{$record->code}");
        $this->assertIsArray($paymentConfig);
        $this->assertEquals($record->code, $paymentConfig['code']);
        $this->assertEquals('SABB First Bank', $paymentConfig['title']);
        $this->assertEquals('Transfer to SABB account', $paymentConfig['description']);
        $this->assertEquals("payment_method.{$record->code}", $paymentConfig['class']);
        $this->assertTrue($paymentConfig['active']);
        $this->assertEquals(3, $paymentConfig['sort']);

        // 5. Verify config('core') injection
        $coreConfig = config("core.sales.payment_methods.{$record->code}");
        $this->assertIsArray($coreConfig);
        $this->assertEquals('SABB First Bank', $coreConfig['title']);

        // 6. Verify IoC container binding
        $this->assertTrue(app()->bound("payment_method.{$record->code}"));
    }

    /**
     * TEST-004-002: Pest/PHPUnit test simulating early access before boot() and confirming
     * ensureRegistered() triggers registration successfully.
     */
    public function test_lazy_guard_ensures_registration(): void
    {
        // Create dynamic bank record
        $record = DynamicBankTransfer::create([
            'title' => 'BSF Bank',
            'bank_name' => 'Banque Saudi Fransi',
            'account_holder_name' => 'HIGEST Corp',
            'iban' => 'SA0355000000123456789012',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $freshRegistry = new DynamicPaymentRegistry($this->service);
        $this->assertFalse($freshRegistry->isRegistered());

        // Simulate early request trigger via ensureRegistered()
        $freshRegistry->ensureRegistered();

        $this->assertTrue($freshRegistry->isRegistered());
        $this->assertNotNull(config("payment_methods.{$record->code}"));
    }

    /**
     * TEST-004-003: Pest/PHPUnit test calling registerAll() multiple times and confirming
     * config array entries are not duplicated or corrupted.
     */
    public function test_register_all_is_idempotent_and_prevents_duplicates(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'Arab National Bank',
            'bank_name' => 'ANB',
            'account_holder_name' => 'HIGEST Admin',
            'iban' => 'SA0365000000123456789012',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $freshRegistry = new DynamicPaymentRegistry($this->service);

        // First registration call
        $freshRegistry->registerAll();
        $firstConfig = config("payment_methods.{$record->code}");

        // Second registration call (idempotent guard)
        $freshRegistry->registerAll();
        $secondConfig = config("payment_methods.{$record->code}");

        $this->assertEquals($firstConfig, $secondConfig);
        $this->assertTrue($freshRegistry->isRegistered());
    }
}
