<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Webkul\Fulfillment\Models\SyncRun;
use Webkul\Fulfillment\Models\ProviderSyncState;
use Webkul\Fulfillment\Services\Application\SyncEngine;
use Webkul\Fulfillment\Services\Application\SyncPipeline;
use Webkul\Fulfillment\Services\Application\SyncEventPublisher;
use Webkul\Fulfillment\Services\Application\ChangeDetector;
use Webkul\Fulfillment\Services\Application\ProviderRateLimiter;
use Webkul\Fulfillment\Services\Application\ProviderCircuitBreaker;
use Webkul\Fulfillment\Contracts\SyncProviderInterface;
use Webkul\Fulfillment\DataObjects\ProviderSyncCapabilities;
use Webkul\Fulfillment\DataObjects\NormalizedExternalProductBatch;
use Webkul\Fulfillment\DataObjects\SyncCursor;
use Webkul\Fulfillment\Exceptions\RateLimitExceededException;

class FulfillmentSyncEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('sync_runs')->delete();
        DB::table('provider_sync_states')->delete();
        DB::table('sync_benchmarks')->delete();
        DB::table('domain_outbox_events')->delete();
        Cache::flush();
        Cache::lock('sync:run:aliexpress')->forceRelease();

        // Configure test overrides for draining timeout and backpressure memory limits
        config([
            'sync.draining_timeout' => 2,
            'sync.backpressure.memory_limit' => 1024 * 1024 * 1024, // 1 GB
        ]);
    }

    public function test_sync_run_state_transitions(): void
    {
        $run = SyncRun::create([
            'id'       => 'test-run-id',
            'provider' => 'aliexpress',
            'status'   => SyncRun::STATUS_CREATED,
            'cursor'   => [],
        ]);

        $this->assertEquals(SyncRun::STATUS_CREATED, $run->status);

        $run->start('owner-1', 'worker-1');
        $this->assertEquals(SyncRun::STATUS_RUNNING, $run->status);
        $this->assertEquals('owner-1', $run->lock_owner);

        $run->pause();
        $this->assertEquals(SyncRun::STATUS_PAUSED, $run->status);

        $run->resume();
        $this->assertEquals(SyncRun::STATUS_RUNNING, $run->status);

        $run->drain();
        $this->assertEquals(SyncRun::STATUS_DRAINING, $run->status);

        $run->complete(['memory' => 100]);
        $this->assertEquals(SyncRun::STATUS_COMPLETED, $run->status);

        // Expect exception for invalid transition
        $this->expectException(\DomainException::class);
        $run->start('owner-2', 'worker-2');
    }

    public function test_sync_engine_uses_distributed_lock(): void
    {
        $engine = app(SyncEngine::class);

        $mockProvider = new class implements SyncProviderInterface {
            public function getCapabilities(): ProviderSyncCapabilities {
                return new ProviderSyncCapabilities(1, true, true, true, true, true);
            }
            public function fetchProductsBatch(SyncCursor $cursor, int $batchSize): NormalizedExternalProductBatch {
                return new NormalizedExternalProductBatch('aliexpress', [], null, false);
            }
        };

        // First execution acquires lock and completes successfully
        $run = $engine->execute('aliexpress', $mockProvider, 10);
        $this->assertEquals(SyncRun::STATUS_COMPLETED, $run->status);

        // Set lock manually in cache to simulate active execution
        $lock = Cache::lock('sync:run:aliexpress', 3600);
        $lock->get();

        try {
            $this->expectException(\RuntimeException::class);
            $engine->execute('aliexpress', $mockProvider, 10);
        } finally {
            $lock->release();
        }
    }

    public function test_sync_engine_resumes_from_json_cursor(): void
    {
        $engine = app(SyncEngine::class);

        $mockProvider = new class implements SyncProviderInterface {
            public function getCapabilities(): ProviderSyncCapabilities {
                return new ProviderSyncCapabilities(1, true, true, true, true, true);
            }
            public function fetchProductsBatch(SyncCursor $cursor, int $batchSize): NormalizedExternalProductBatch {
                // If cursor has page token 'page_1', return no more data. Otherwise return next page.
                if ($cursor->page_token === 'page_1') {
                    return new NormalizedExternalProductBatch('aliexpress', [], null, false);
                }
                return new NormalizedExternalProductBatch('aliexpress', [
                    [
                        'id' => 'sp_1',
                        'variants' => [['sku_id' => 'sku_1', 'price' => 10.0, 'stock' => 10, 'version' => 1]]
                    ]
                ], 'page_1', true);
            }
        };

        // First run: retrieves page_1 cursor
        $run1 = $engine->execute('aliexpress', $mockProvider, 1);
        $this->assertEquals(SyncRun::STATUS_COMPLETED, $run1->status);

        $state = ProviderSyncState::find('aliexpress');
        $this->assertNotNull($state);
        $this->assertEquals('page_1', $state->last_successful_cursor['page_token']);

        // Second run: resumes from stored cursor and completes
        $run2 = $engine->execute('aliexpress', $mockProvider, 1);
        $this->assertEquals(SyncRun::STATUS_COMPLETED, $run2->status);
    }

    public function test_rate_limiter_throws_requeuing_exception(): void
    {
        $this->expectException(RateLimitExceededException::class);

        // Hit rate limiter multiple times
        for ($i = 0; $i < 3; $i++) {
            ProviderRateLimiter::checkAndHit('aliexpress', 'fetch', 'batch', 2, 60);
        }
    }

    public function test_scoped_circuit_breaker_status(): void
    {
        $provider = 'aliexpress';
        $endpoint = 'fetch';
        $operation = 'batch';

        $this->assertFalse(ProviderCircuitBreaker::isBlocked($provider, $endpoint, $operation));

        // Trigger 5 failures to trip the circuit open
        for ($i = 0; $i < 5; $i++) {
            ProviderCircuitBreaker::recordFailure($provider, $endpoint, $operation);
        }

        $this->assertTrue(ProviderCircuitBreaker::isBlocked($provider, $endpoint, $operation));

        // Test recovery / success reset
        ProviderCircuitBreaker::recordSuccess($provider, $endpoint, $operation);
        $this->assertFalse(ProviderCircuitBreaker::isBlocked($provider, $endpoint, $operation));
    }
}
