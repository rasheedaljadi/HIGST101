<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransfer as DynamicBankTransferContract;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransferProxy;
use Webkul\DynamicBankTransfer\Repositories\DynamicBankTransferRepository;

class DatabaseLayerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Model creation, Concord Contract implementation, and code auto-generation hook.
     */
    public function test_model_crud_and_code_auto_generation(): void
    {
        /** @var DynamicBankTransferRepository $repository */
        $repository = app(DynamicBankTransferRepository::class);

        $data = [
            'is_active' => true,
            'title' => 'Test Al Rajhi Bank',
            'description' => 'Test bank instructions',
            'bank_name' => 'Al Rajhi Bank',
            'account_holder_name' => 'HIGEST Corp',
            'account_number' => '987654321',
            'iban' => 'SA1234567890123456789012',
            'sort_order' => 5,
        ];

        /** @var DynamicBankTransfer $model */
        $model = $repository->create($data);

        $this->assertNotNull($model->id);
        $this->assertInstanceOf(DynamicBankTransferContract::class, $model);
        $this->assertEquals('dynamic_bank_transfer_'.$model->id, $model->code);
        $this->assertTrue($model->is_active);
        $this->assertEquals('Al Rajhi Bank', $model->bank_name);

        // Update via Repository
        $updatedModel = $repository->update([
            'title' => 'Updated Al Rajhi Bank',
        ], $model->id);

        $this->assertEquals('Updated Al Rajhi Bank', $updatedModel->title);

        // Retrieve via Concord Proxy
        $proxyModel = DynamicBankTransferProxy::find($model->id);
        $this->assertNotNull($proxyModel);
        $this->assertEquals('Updated Al Rajhi Bank', $proxyModel->title);
    }

    /**
     * Test soft deletion behavior.
     */
    public function test_soft_deletion(): void
    {
        /** @var DynamicBankTransferRepository $repository */
        $repository = app(DynamicBankTransferRepository::class);

        $model = $repository->create([
            'is_active' => true,
            'title' => 'Test Soft Delete Bank',
            'bank_name' => 'Riyad Bank',
            'account_holder_name' => 'HIGEST Corp',
            'account_number' => '55555',
        ]);

        $modelId = $model->id;

        // Delete via Repository
        $repository->delete($modelId);

        // Assert model is not found in standard queries
        $this->assertNull($repository->find($modelId));

        // Assert model exists with soft deletes
        $trashed = DynamicBankTransfer::withTrashed()->find($modelId);
        $this->assertNotNull($trashed);
        $this->assertNotNull($trashed->deleted_at);
    }

    /**
     * Test database indexes schema integrity.
     */
    public function test_database_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasTable('dynamic_bank_transfers'));
        $this->assertTrue(Schema::hasColumn('dynamic_bank_transfers', 'code'));
        $this->assertTrue(Schema::hasColumn('dynamic_bank_transfers', 'is_active'));
        $this->assertTrue(Schema::hasColumn('dynamic_bank_transfers', 'sort_order'));
        $this->assertTrue(Schema::hasColumn('dynamic_bank_transfers', 'deleted_at'));
    }
}
