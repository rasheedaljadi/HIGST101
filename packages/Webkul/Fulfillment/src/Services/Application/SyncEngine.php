<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Contracts\SyncProviderInterface;
use Webkul\Fulfillment\DataObjects\SyncCursor;
use Webkul\Fulfillment\DataObjects\BackpressureDecision;
use Webkul\Fulfillment\Models\SyncRun;
use Webkul\Fulfillment\Models\ProviderSyncState;

class SyncEngine
{
    protected SyncPipeline $pipeline;
    protected SyncEventPublisher $publisher;

    public function __construct(SyncPipeline $pipeline, SyncEventPublisher $publisher)
    {
        $this->pipeline = $pipeline;
        $this->publisher = $publisher;
    }

    /**
     * Execute synchronization.
     */
    public function execute(string $providerName, SyncProviderInterface $provider, int $batchSize = 50): SyncRun
    {
        $lockKey = "sync:run:{$providerName}";
        $lockOwner = (string) Str::uuid();
        $lock = Cache::lock($lockKey, 3600, $lockOwner);

        if (!$lock->get()) {
            throw new \RuntimeException("Could not acquire sync lock for provider [{$providerName}]. Process already running.");
        }

        $workerId = getmypid() ?: 'unknown_worker';
        $runId = (string) Str::uuid();

        // 1. Resolve Cursor
        $syncState = ProviderSyncState::find($providerName);
        $capabilities = $provider->getCapabilities();

        $cursorData = $syncState ? $syncState->last_attempt_cursor : null;
        $cursor = $cursorData ? SyncCursor::fromArray($cursorData) : SyncCursor::createDefault($providerName);

        // 2. Create SyncRun Aggregate Model
        $run = SyncRun::create([
            'id'              => $runId,
            'provider'        => $providerName,
            'status'          => SyncRun::STATUS_CREATED,
            'cursor'          => $cursor->toArray(),
            'metadata'        => [
                'capabilities' => $capabilities->toArray(),
            ],
            'health_snapshot' => [
                'memory_start' => memory_get_usage(true),
            ],
            'statistics'      => [
                'scanned'             => 0,
                'changed'             => 0,
                'published'           => 0,
                'errors_count'        => 0,
                'warnings_count'      => 0,
                'chunks_processed'    => 0,
            ],
        ]);

        $run->start($lockOwner, $workerId);

        // 3. Upsert attempt details to ProviderSyncState
        ProviderSyncState::updateOrCreate(
            ['provider' => $providerName],
            [
                'last_attempt_cursor' => $cursor->toArray(),
                'last_attempt_at'     => now(),
                'schema_version'      => $capabilities->version,
            ]
        );

        $stats = $run->statistics;
        $startTime = microtime(true);
        $chunkNumber = 0;
        $hasErrors = false;

        try {
            while (true) {
                // Check cancellation token
                if ($run->isCancelled()) {
                    break;
                }

                // Verify lock ownership is still held to prevent split-brain execution
                if ($this->getLockOwner($lockKey) !== $lockOwner) {
                    $hasErrors = true;
                    $run->fail("Distributed lock lost or acquired by another worker process.");
                    break;
                }

                // Check Backpressure
                $backpressure = $this->getBackpressureDecision();
                if ($backpressure->shouldStop()) {
                    $hasErrors = true;
                    $run->fail("Throttled/Stopped due to backpressure: " . $backpressure->reason);
                    break;
                }

                if ($backpressure->shouldThrottle()) {
                    sleep($backpressure->delaySeconds);
                }

                $startingCursor = $cursor;
                $chunkNumber++;

                // Execute Pipeline chunk
                $result = $this->pipeline->process($provider, $cursor, $batchSize);

                // Publish events
                $published = 0;
                foreach ($result->changeSets as $changeSet) {
                    $published += $this->publisher->publish($changeSet, $runId);
                }

                // Update cursor & statistics
                $cursor = $result->newCursor;
                $stats['scanned'] += $result->processedCount;
                $stats['changed'] += $result->changedCount;
                $stats['published'] += $published;
                $stats['chunks_processed']++;

                if (!empty($result->errors)) {
                    $hasErrors = true;
                    $stats['errors_count'] += count($result->errors);
                }
                if (!empty($result->warnings)) {
                    $stats['warnings_count'] += count($result->warnings);
                }

                $run->statistics = $stats;
                $run->cursor = $cursor->toArray();
                $run->heartbeat();

                // Progressively update ProviderSyncState last attempt details
                ProviderSyncState::updateOrCreate(
                    ['provider' => $providerName],
                    [
                        'last_attempt_cursor' => $cursor->toArray(),
                        'last_attempt_at'     => now(),
                    ]
                );

                // Break loop if there is no more data to fetch
                if (empty($result->changeSets) && ($result->processedCount < $batchSize)) {
                    break;
                }

                // Prevent infinite loop if cursor doesn't progress and errors are encountered
                if (!empty($result->errors) && $startingCursor->toArray() === $cursor->toArray()) {
                    $run->fail("Pipeline execution failed with errors and cursor did not progress: " . implode('; ', $result->errors));
                    break;
                }
            }

            if ($run->status === SyncRun::STATUS_RUNNING) {
                // Transition to DRAINING state
                $run->drain();

                // Wait for all events to drain
                $drainingTimeout = (int) config('sync.draining_timeout', 900);
                $isDrained = $this->verifyDrained($runId, $drainingTimeout);

                $health = [
                    'memory_peak'  => memory_get_peak_usage(true),
                    'duration_sec' => round(microtime(true) - $startTime, 2),
                    'is_drained'   => $isDrained,
                ];

                if ($hasErrors) {
                    $run->completeWithErrors($health);
                } else {
                    $run->complete($health);

                    // Update successful checkpoints
                    ProviderSyncState::updateOrCreate(
                        ['provider' => $providerName],
                        [
                            'last_successful_cursor' => $cursor->toArray(),
                            'last_successful_at'     => now(),
                            'last_full_sync_at'      => now(),
                        ]
                    );
                }
            }

        } catch (\Throwable $e) {
            $run->fail($e->getMessage());
        } finally {
            $lock->release();
        }

        return $run;
    }

    protected function getBackpressureDecision(): BackpressureDecision
    {
        // 1. Check pending outbox size
        $outboxCount = DB::table('domain_outbox_events')->whereIn('status', ['pending', 'processing'])->count();
        $outboxLimit = (int) config('sync.backpressure.outbox_limit', 10000);
        if ($outboxCount >= $outboxLimit) {
            return BackpressureDecision::throttle(5, 'Outbox is overloaded', 'outbox_size', $outboxCount, $outboxLimit);
        }

        // 2. Check inbox size
        $inboxCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('domain_inbox_events')) {
            $inboxCount = DB::table('domain_inbox_events')->whereIn('status', ['pending', 'processing'])->count();
        }
        $inboxLimit = (int) config('sync.backpressure.inbox_limit', 5000);
        if ($inboxCount >= $inboxLimit) {
            return BackpressureDecision::throttle(5, 'Inbox is overloaded', 'inbox_size', $inboxCount, $inboxLimit);
        }

        // 3. Check memory limit
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = (int) config('sync.backpressure.memory_limit', 128 * 1024 * 1024); // 128 MB default
        if ($memoryPeak >= $memoryLimit) {
            return BackpressureDecision::stop('Memory peak usage exceeded limit', 'memory_usage', $memoryPeak, $memoryLimit);
        }

        return BackpressureDecision::proceed();
    }

    protected function verifyDrained(string $syncRunId, int $timeoutSeconds): bool
    {
        $start = time();
        while ((time() - $start) < $timeoutSeconds) {
            $outboxPending = DB::table('domain_outbox_events')
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            $inboxPending = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('domain_inbox_events')) {
                $inboxPending = DB::table('domain_inbox_events')
                    ->whereIn('status', ['pending', 'processing'])
                    ->count();
            }

            if ($outboxPending === 0 && $inboxPending === 0) {
                return true;
            }

            sleep(1);
        }

        return false;
    }

    protected function getLockOwner(string $lockKey): ?string
    {
        $store = Cache::store()->getStore();
        if (property_exists($store, 'locks')) {
            $lockData = $store->locks[$lockKey] ?? null;
            if (is_array($lockData)) {
                return $lockData['owner'] ?? ($lockData[0] ?? null);
            }
            if (is_object($lockData)) {
                return $lockData->owner ?? null;
            }
            return is_string($lockData) ? $lockData : null;
        }

        $val = Cache::get($lockKey);
        return is_string($val) ? $val : null;
    }
}
