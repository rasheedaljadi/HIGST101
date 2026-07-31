<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;
use Webkul\Payment\Facades\Payment;

class PerformanceBenchmarkTest extends TestCase
{
    use DatabaseTransactions;

    protected DynamicBankTransferServiceContract $service;

    protected DynamicPaymentRegistryContract $registry;

    protected Channel $channel;

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
            'code' => 'default_benchmark_test',
            'name' => 'Benchmark Test Channel',
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
     * TEST-010-001 (Query Count Benchmark): Assert DB::getQueryLog() returns 0 queries
     * for payment method listing when cache is warm.
     */
    public function test_query_count_is_zero_on_warm_cache(): void
    {
        // Create 3 active bank transfer records
        for ($i = 1; $i <= 3; $i++) {
            DynamicBankTransfer::create([
                'title' => "Benchmark Bank {$i}",
                'bank_name' => "Bank {$i}",
                'account_holder_name' => 'HIGEST Benchmark',
                'iban' => 'SA0380000000608010167519',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        // Warm up cache
        $this->registry->reset();
        $this->registry->registerAll();
        $this->service->getActiveMethods();

        // Clear in-memory request cache to test cache hit layer
        DynamicBankTransferService::clearRequestCache();

        // Enable query log and fetch active methods
        DB::enableQueryLog();
        DB::flushQueryLog();

        $activeMethods = $this->service->getActiveMethods();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(3, $activeMethods);
        $this->assertCount(0, $queries, 'Expected 0 database queries on warm cache, but executed: '.count($queries));
    }

    /**
     * TEST-010-002 (Memory Footprint Benchmark): Assert memory usage delta is under 50KB
     * for 10 active dynamic bank methods.
     */
    public function test_memory_footprint_is_under_50kb_for_10_methods(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DynamicBankTransfer::create([
                'title' => "Memory Test Bank {$i}",
                'bank_name' => "Bank {$i}",
                'account_holder_name' => "Holder {$i}",
                'iban' => 'SA0380000000608010167519',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        Payment::getPaymentMethods();
        gc_collect_cycles();

        $startMemory = memory_get_usage();

        $this->registry->reset();
        $this->registry->registerAll();
        $methods = $this->service->getActiveMethods();

        $endMemory = memory_get_usage();
        $memoryDeltaKb = max(0, ($endMemory - $startMemory) / 1024);

        $this->assertLessThan(50, $memoryDeltaKb, "Expected DTO memory overhead < 50KB, actual: {$memoryDeltaKb} KB");
    }

    /**
     * TEST-010-003 (Boot Execution & Cache Hit Benchmark): Assert cache hit response time
     * is under 2ms and runtime registration overhead is under 5ms.
     */
    public function test_cache_hit_response_time_and_boot_overhead(): void
    {
        DynamicBankTransfer::create([
            'title' => 'Speed Bank',
            'bank_name' => 'Speed Bank',
            'account_holder_name' => 'HIGEST Speed',
            'iban' => 'SA0380000000608010167519',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        // Warm up cache
        $this->registry->reset();
        $this->registry->registerAll();

        // 1. Measure Runtime Registration Overhead
        $startBoot = microtime(true);
        $this->registry->reset();
        $this->registry->registerAll();
        $bootTimeMs = (microtime(true) - $startBoot) * 1000;

        // 2. Measure Cache Hit Response Time
        DynamicBankTransferService::clearRequestCache();
        $startCacheHit = microtime(true);
        $methods = $this->service->getActiveMethods();
        $cacheHitTimeMs = (microtime(true) - $startCacheHit) * 1000;

        $this->assertLessThan(5.0, $bootTimeMs, "Expected boot overhead < 5ms, actual: {$bootTimeMs} ms");
        $this->assertLessThan(2.0, $cacheHitTimeMs, "Expected cache hit response < 2ms, actual: {$cacheHitTimeMs} ms");
    }
}
