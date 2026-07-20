<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Webkul\Fulfillment\Services\Application\SyncEngine;
use Webkul\Fulfillment\Contracts\SyncProviderInterface;
use Webkul\Fulfillment\DataObjects\ProviderSyncCapabilities;
use Webkul\Fulfillment\DataObjects\NormalizedExternalProductBatch;
use Webkul\Fulfillment\DataObjects\SyncCursor;
use Webkul\Fulfillment\Models\SyncRun;
use Webkul\Fulfillment\Models\ProviderSyncState;
use Webkul\Fulfillment\Commands\CreatePurchaseOrderCommand;
use Webkul\Fulfillment\Handlers\CreatePurchaseOrderHandler;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FulfillmentChaosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('sync_runs')->delete();
        DB::table('provider_sync_states')->delete();
        DB::table('sync_benchmarks')->delete();
        DB::table('domain_outbox_events')->delete();
        DB::table('purchase_orders')->delete();
        DB::table('order_allocations')->delete();
        
        Cache::flush();
        Cache::lock('sync:run:aliexpress')->forceRelease();

        config([
            'sync.draining_timeout' => 2,
            'sync.backpressure.memory_limit' => 1024 * 1024 * 1024,
        ]);
    }

    public function test_sync_engine_aborts_on_lock_owner_loss(): void
    {
        $engine = app(SyncEngine::class);

        $mockProvider = new class implements SyncProviderInterface {
            public int $calls = 0;

            public function getCapabilities(): ProviderSyncCapabilities {
                return new ProviderSyncCapabilities(1, true, true, true, true, true);
            }

            public function fetchProductsBatch(SyncCursor $cursor, int $batchSize): NormalizedExternalProductBatch {
                $this->calls++;
                
                // Simulate lock hijacking in the array driver locks store
                $store = Cache::store()->getStore();
                $store->locks['sync:run:aliexpress'] = [
                    'owner'     => 'someone-else',
                    'expiresAt' => now()->addHour(),
                ];

                return new NormalizedExternalProductBatch('aliexpress', [
                    [
                        'id' => 'sp_soak_1',
                        'variants' => [['sku_id' => 'sku_soak_1', 'price' => 10.0, 'stock' => 10, 'version' => 1]]
                    ]
                ], 'page_1', true);
            }
        };

        // First execution will start, call fetchProductsBatch, lose lock, then fail on the next iteration
        $run = $engine->execute('aliexpress', $mockProvider, 1);
        
        $this->assertEquals(SyncRun::STATUS_FAILED, $run->status);
        $this->assertStringContainsString('Distributed lock lost', $run->metadata['error_message']);
        $this->assertEquals(1, $mockProvider->calls);
    }

    public function test_sync_engine_recovery_from_abrupt_worker_termination(): void
    {
        // Insert a hung sync run
        $run = SyncRun::create([
            'id'           => 'hung-run-uuid',
            'provider'     => 'aliexpress',
            'status'       => SyncRun::STATUS_RUNNING,
            'cursor'       => [],
            'worker_id'    => 'worker-999',
            'lock_owner'   => 'owner-999',
            'heartbeat_at' => now()->subMinutes(20),
            'started_at'   => now()->subMinutes(25),
        ]);

        // Manually hold distributed lock
        Cache::lock('sync:run:aliexpress')->get();

        // Run recovery command directly with BufferedOutput inside OutputStyle wrapper
        $command = app(\Webkul\Fulfillment\Console\Commands\RecoverSyncRunsCommand::class);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            $output
        ));
        $exitCode = $command->handle();
        $this->assertEquals(0, $exitCode);

        // Verify status changed to INTERRUPTED
        $this->assertEquals(SyncRun::STATUS_INTERRUPTED, $run->fresh()->status);

        // Verify lock is released and can be acquired now
        $lock = Cache::lock('sync:run:aliexpress');
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_projection_replay_idempotency_avoids_duplicate_po(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        // Seed dummy product to satisfy foreign key constraint on order_allocations
        DB::table('products')->where('id', 1)->delete();
        DB::table('products')->insert([
            'id'                  => 1,
            'type'                => 'simple',
            'attribute_family_id' => 1,
            'sku'                 => 'soak-test-prod',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Setup OrderAllocation
        DB::table('order_allocations')->insert([
            'id'                 => 12345,
            'order_id'           => 1,
            'order_item_id'      => 1,
            'product_id'         => 1,
            'allocation_type'    => 'purchase_order',
            'source_code'        => 'aliexpress',
            'supplier_snapshot'  => json_encode(['supplier_cost' => 12.50, 'supplier_currency' => 'USD']),
            'reserved_qty'       => 1,
            'fulfilled_qty'      => 0,
            'canceled_qty'       => 0,
            'state'              => 'reserved',
            'version'            => 1,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $command = new CreatePurchaseOrderCommand(
            1,     // orderId
            12345, // orderAllocationId
            'aliexpress',
            'corr-uuid',
            'caus-uuid'
        );

        $handler = app(CreatePurchaseOrderHandler::class);

        // First Execution
        $po1 = $handler->handle($command);
        $this->assertNotNull($po1);
        $this->assertEquals(1, PurchaseOrder::count());
        $this->assertEquals(1, DB::table('domain_outbox_events')->count());

        // Second Execution (Retry)
        $po2 = $handler->handle($command);
        $this->assertEquals($po1->id, $po2->id);
        
        // Assert no duplicate PurchaseOrder created
        $this->assertEquals(1, PurchaseOrder::count());
        
        // Assert no duplicate outbox event published
        $this->assertEquals(1, DB::table('domain_outbox_events')->count());
    }
}
