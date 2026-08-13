<?php

namespace Webkul\Wallet\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionOutbox;

class WalletPromotionOutboxWorker
{
    public function __construct(
        protected WalletPromotionOrchestrator $orchestrator
    ) {}

    /**
     * Claim pending or expired lease jobs atomically.
     */
    public function claimJobs(
        int $batchSize = 10,
        int $leaseSeconds = 300,
        string $workerId = 'worker-test-1'
    ): Collection {
        return DB::transaction(function () use ($batchSize, $leaseSeconds, $workerId) {
            $now = now();

            $jobIds = WalletPromotionOutbox::where(function ($q) use ($now) {
                $q->where('status', WalletPromotionOutbox::STATUS_PENDING)
                    ->orWhere(function ($q2) use ($now) {
                        $q2->where('status', WalletPromotionOutbox::STATUS_PROCESSING)
                            ->whereNotNull('lease_expires_at')
                            ->where('lease_expires_at', '<', $now);
                    });
            })
                ->orderBy('id', 'asc')
                ->limit($batchSize)
                ->lockForUpdate()
                ->pluck('id');

            if ($jobIds->isEmpty()) {
                return collect();
            }

            WalletPromotionOutbox::whereIn('id', $jobIds)->update([
                'status' => WalletPromotionOutbox::STATUS_PROCESSING,
                'locked_at' => $now,
                'locked_by' => $workerId,
                'lease_expires_at' => $now->copy()->addSeconds($leaseSeconds),
                'attempts' => DB::raw('attempts + 1'),
            ]);

            return WalletPromotionOutbox::whereIn('id', $jobIds)->orderBy('id', 'asc')->get();
        });
    }

    /**
     * Process a single claimed outbox job.
     */
    public function processJob(WalletPromotionOutbox $job, int $maxAttempts = 3): bool
    {
        try {
            $payload = $job->payload;
            $promotionId = $payload['promotion_id'] ?? null;
            $walletId = $payload['wallet_id'] ?? null;
            $eventKey = $job->event_key;
            $amountStr = (string) ($payload['eligible_amount'] ?? $payload['amount'] ?? '0.0000');
            $refType = $payload['reference_type'] ?? WalletPromotion::class;
            $refId = (int) ($payload['reference_id'] ?? $promotionId ?? 0);
            $currency = $payload['currency_code'] ?? 'SAR';

            if (! $promotionId || ! $walletId) {
                throw new \InvalidArgumentException('Invalid job payload: missing promotion_id or wallet_id');
            }

            $promotion = WalletPromotion::findOrFail($promotionId);

            // Execute promotion orchestration
            $res = $this->orchestrator->applyPromotionGrant(
                promotion: $promotion,
                walletId: $walletId,
                eventKey: $eventKey,
                eligibleAmountStr: $amountStr,
                referenceType: $refType,
                referenceId: $refId,
                currencyCode: $currency
            );

            // Mark job completed
            $job->status = WalletPromotionOutbox::STATUS_COMPLETED;
            $job->processed_at = now();
            $job->last_error = null;
            $job->save();

            return true;
        } catch (Throwable $e) {
            $job->last_error = substr($e->getMessage(), 0, 1000);
            if ($job->attempts >= $maxAttempts) {
                $job->status = WalletPromotionOutbox::STATUS_FAILED;
            } else {
                // Reset to pending for immediate/next retry
                $job->status = WalletPromotionOutbox::STATUS_PENDING;
            }
            $job->save();

            return false;
        }
    }

    /**
     * Run a single pass over claimed jobs.
     */
    public function runOnce(int $batchSize = 10, int $leaseSeconds = 300, string $workerId = 'worker-test-1'): int
    {
        $claimed = $this->claimJobs($batchSize, $leaseSeconds, $workerId);
        $processedCount = 0;

        foreach ($claimed as $job) {
            if ($this->processJob($job)) {
                $processedCount++;
            }
        }

        return $processedCount;
    }
}
