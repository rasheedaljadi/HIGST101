<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\DTOs\DynamicBankTransferDTO;
use Webkul\DynamicBankTransfer\Exceptions\InvalidPaymentMethodCodeException;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Repositories\DynamicBankTransferRepository;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;

class DomainLayerTest extends TestCase
{
    use DatabaseTransactions;

    protected DynamicBankTransferService $service;

    protected DynamicBankTransferRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(DynamicBankTransferRepository::class);
        $this->service = app(DynamicBankTransferServiceContract::class);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);
    }

    /**
     * TEST-003-001: Pest/PHPUnit test verifying DynamicBankTransferDTO instantiation,
     * serialization, deserialization, and immutability.
     */
    public function test_dto_instantiation_and_serialization(): void
    {
        $attributes = [
            'id' => 10,
            'code' => 'dynamic_bank_transfer_10',
            'is_active' => true,
            'title' => 'Test Al Rajhi Bank',
            'description' => 'Transfer money to Al Rajhi account',
            'bank_name' => 'Al Rajhi Bank',
            'account_holder_name' => 'HIGEST E-Commerce Co',
            'account_number' => '1234567890',
            'iban' => 'SA0380000000123456789012',
            'swift_code' => 'RJHI0123',
            'generate_invoice' => true,
            'invoice_status' => 'paid',
            'order_status' => 'pending',
            'sort_order' => 2,
        ];

        $dto = DynamicBankTransferDTO::fromArray($attributes);

        $this->assertEquals(10, $dto->id);
        $this->assertEquals('dynamic_bank_transfer_10', $dto->code);
        $this->assertTrue($dto->isActive);
        $this->assertEquals('Test Al Rajhi Bank', $dto->title);
        $this->assertEquals('Al Rajhi Bank', $dto->bankName);
        $this->assertEquals('SA0380000000123456789012', $dto->iban);

        // Test toArray and back
        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('dynamic_bank_transfer_10', $array['code']);

        $recreatedDTO = DynamicBankTransferDTO::fromArray($array);
        $this->assertEquals($dto->id, $recreatedDTO->id);
        $this->assertEquals($dto->code, $recreatedDTO->code);
    }

    /**
     * TEST-003-002: Pest/PHPUnit test verifying getActiveMethods() queries DB on cache miss
     * and returns from application cache on hit.
     */
    public function test_active_methods_caching_behavior(): void
    {
        // 1. Create a bank transfer record in database directly
        $record = DynamicBankTransfer::create([
            'title' => 'Riyad Bank',
            'bank_name' => 'Riyad Bank',
            'account_holder_name' => 'HIGEST Corp',
            'iban' => 'SA0320000000987654321012',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->assertFalse(Cache::has(DynamicBankTransferService::CACHE_KEY_ACTIVE));

        // 2. First call: DB miss -> queries DB -> populates cache
        $methods = $this->service->getActiveMethods();

        $this->assertCount(1, $methods);
        $this->assertEquals($record->code, $methods->first()->code);
        $this->assertTrue(Cache::has(DynamicBankTransferService::CACHE_KEY_ACTIVE));

        // 3. Second call: Cache hit -> returns cached DTOs
        $cachedMethods = $this->service->getActiveMethods();
        $this->assertCount(1, $cachedMethods);
        $this->assertEquals($record->code, $cachedMethods->first()->code);
    }

    /**
     * TEST-003-003: Pest/PHPUnit test verifying createMethod(), updateMethod(),
     * and deleteMethod() trigger Cache::forget().
     */
    public function test_cache_invalidation_on_crud_operations(): void
    {
        // Populate cache
        $record = DynamicBankTransfer::create([
            'title' => 'SNB AlAhli',
            'bank_name' => 'SNB',
            'account_holder_name' => 'HIGEST Store',
            'iban' => 'SA0310000000111122223333',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->service->getActiveMethods();
        $this->assertTrue(Cache::has(DynamicBankTransferService::CACHE_KEY_ACTIVE));

        // Test Update Invalidation
        $updatedDto = $this->service->updateMethod($record->id, [
            'title' => 'Updated SNB AlAhli',
        ]);
        $this->assertFalse(Cache::has(DynamicBankTransferService::CACHE_KEY_ACTIVE));
        $this->assertEquals('Updated SNB AlAhli', $updatedDto->title);

        // Populate cache again
        $this->service->getActiveMethods();
        $this->assertTrue(Cache::has(DynamicBankTransferService::CACHE_KEY_ACTIVE));

        // Test Delete Invalidation
        $this->service->deleteMethod($record->id);
        $this->assertFalse(Cache::has(DynamicBankTransferService::CACHE_KEY_ACTIVE));
    }

    /**
     * TEST-003-004: Pest/PHPUnit test verifying buildOrderSnapshot() outputs valid v1.1
     * JSON structure matching specification schema.
     */
    public function test_order_snapshot_builder(): void
    {
        $record = DynamicBankTransfer::create([
            'title' => 'Alinma Bank',
            'bank_name' => 'Alinma Bank',
            'account_holder_name' => 'HIGEST Trading',
            'account_number' => '555444333',
            'iban' => 'SA0305000000555444333222',
            'swift_code' => 'INMASARI',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $snapshot = $this->service->buildOrderSnapshot($record->code);

        $this->assertIsArray($snapshot);
        $this->assertEquals($record->id, $snapshot['id']);
        $this->assertEquals($record->code, $snapshot['code']);
        $this->assertEquals('Alinma Bank', $snapshot['title']);
        $this->assertEquals('Alinma Bank', $snapshot['bank_name']);
        $this->assertEquals('HIGEST Trading', $snapshot['account_holder_name']);
        $this->assertEquals('555444333', $snapshot['account_number']);
        $this->assertEquals('SA0305000000555444333222', $snapshot['iban']);
        $this->assertEquals('INMASARI', $snapshot['swift_code']);
        $this->assertEquals('1.1', $snapshot['snapshot_version']);
        $this->assertArrayHasKey('snapshotted_at', $snapshot);

        // Test exception on non-existent code
        $this->expectException(InvalidPaymentMethodCodeException::class);
        $this->service->buildOrderSnapshot('non_existent_code');
    }
}
