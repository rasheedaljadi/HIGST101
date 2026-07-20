<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Events\SyncRunStarted;
use Webkul\Fulfillment\Events\SyncPaused;
use Webkul\Fulfillment\Events\SyncResumed;
use Webkul\Fulfillment\Events\SyncCompleted;
use Webkul\Fulfillment\Events\SyncFailed;
use Webkul\Fulfillment\Events\SyncRunCancelled;

class SyncRun extends Model
{
    public const STATUS_CREATED = 'CREATED';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DRAINING = 'DRAINING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_COMPLETED_WITH_ERRORS = 'COMPLETED_WITH_ERRORS';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_RESUMING = 'RESUMING';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_INTERRUPTED = 'INTERRUPTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'sync_runs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'provider',
        'status',
        'lock_owner',
        'worker_id',
        'cursor',
        'metadata',
        'health_snapshot',
        'statistics',
        'started_at',
        'heartbeat_at',
        'completed_at',
    ];

    protected $casts = [
        'cursor'          => 'array',
        'metadata'        => 'array',
        'health_snapshot' => 'array',
        'statistics'      => 'array',
        'started_at'      => 'datetime',
        'heartbeat_at'    => 'datetime',
        'completed_at'    => 'datetime',
    ];

    public function start(string $lockOwner, string $workerId): void
    {
        $this->transitionTo(self::STATUS_RUNNING);
        $this->lock_owner = $lockOwner;
        $this->worker_id = $workerId;
        $this->started_at = now();
        $this->heartbeat_at = now();
        $this->save();

        event(new SyncRunStarted($this->id, $this->provider));
    }

    public function heartbeat(): void
    {
        $this->heartbeat_at = now();
        $this->save();
    }

    public function pause(): void
    {
        $this->transitionTo(self::STATUS_PAUSED);
        $this->save();

        event(new SyncPaused($this->id));
    }

    public function resume(): void
    {
        $this->transitionTo(self::STATUS_RESUMING);
        $this->save();

        event(new SyncResumed($this->id));

        $this->transitionTo(self::STATUS_RUNNING);
        $this->heartbeat_at = now();
        $this->save();
    }

    public function drain(): void
    {
        $this->transitionTo(self::STATUS_DRAINING);
        $this->save();
    }

    public function complete(array $healthSnapshot): void
    {
        $this->transitionTo(self::STATUS_COMPLETED);
        $this->health_snapshot = $healthSnapshot;
        $this->completed_at = now();
        $this->save();

        event(new SyncCompleted($this->id, $this->statistics ?? [], $healthSnapshot));
    }

    public function completeWithErrors(array $healthSnapshot): void
    {
        $this->transitionTo(self::STATUS_COMPLETED_WITH_ERRORS);
        $this->health_snapshot = $healthSnapshot;
        $this->completed_at = now();
        $this->save();

        event(new SyncCompleted($this->id, $this->statistics ?? [], $healthSnapshot));
    }

    public function fail(string $error): void
    {
        $this->transitionTo(self::STATUS_FAILED);
        
        $meta = $this->metadata ?? [];
        $meta['error_message'] = $error;
        $this->metadata = $meta;

        $this->completed_at = now();
        $this->save();

        event(new SyncFailed($this->id, $error));
    }

    public function interrupt(): void
    {
        $this->transitionTo(self::STATUS_INTERRUPTED);
        $this->completed_at = now();
        $this->save();

        event(new SyncFailed($this->id, 'Sync run interrupted'));
    }

    public function cancel(): void
    {
        $this->transitionTo(self::STATUS_CANCELLED);
        $this->completed_at = now();
        $this->save();

        event(new SyncRunCancelled($this->id));
    }

    public function isCancelled(): bool
    {
        return $this->fresh()?->status === self::STATUS_CANCELLED;
    }

    private function transitionTo(string $targetStatus): void
    {
        $transitions = [
            self::STATUS_CREATED => [self::STATUS_RUNNING, self::STATUS_CANCELLED],
            self::STATUS_RUNNING => [self::STATUS_DRAINING, self::STATUS_PAUSED, self::STATUS_FAILED, self::STATUS_INTERRUPTED, self::STATUS_CANCELLED],
            self::STATUS_DRAINING => [self::STATUS_COMPLETED, self::STATUS_COMPLETED_WITH_ERRORS, self::STATUS_FAILED, self::STATUS_INTERRUPTED, self::STATUS_CANCELLED],
            self::STATUS_PAUSED  => [self::STATUS_RESUMING, self::STATUS_CANCELLED],
            self::STATUS_RESUMING => [self::STATUS_RUNNING],
        ];

        $current = $this->status ?? self::STATUS_CREATED;

        if ($current === $targetStatus) {
            return;
        }

        if (!isset($transitions[$current]) || !in_array($targetStatus, $transitions[$current])) {
            throw new \DomainException("Invalid sync run status transition: [{$current}] -> [{$targetStatus}]");
        }

        $this->status = $targetStatus;
    }
}
