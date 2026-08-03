<?php

namespace Webkul\Wallet\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Repositories\RefundRepository;
use Webkul\Wallet\Models\WalletPendingCredit;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Services\WalletService;

class ProcessWalletCreditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * Exponential backoff delays in seconds (10s, 1m, 5m, 15m).
     *
     * @var array<int>
     */
    public array $backoff = [10, 60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $pendingCreditId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        WalletService $walletService,
        WalletAccountRepository $walletAccountRepository,
        RefundRepository $refundRepository
    ): void {
        $pendingRecord = WalletPendingCredit::find($this->pendingCreditId);

        if (! $pendingRecord || $pendingRecord->status === WalletPendingCredit::STATUS_COMPLETED) {
            return;
        }

        $pendingRecord->update([
            'status' => WalletPendingCredit::STATUS_PROCESSING,
            'attempts' => $pendingRecord->attempts + 1,
            'last_attempt_at' => now(),
        ]);

        try {
            $refund = $refundRepository->find($pendingRecord->refund_id);
            $wallet = $walletAccountRepository->find($pendingRecord->wallet_id);

            if (! $refund || ! $wallet) {
                throw new \RuntimeException("Refund #{$pendingRecord->refund_id} or Wallet #{$pendingRecord->wallet_id} not found.");
            }

            $order = $refund->order;

            $walletService->credit(
                wallet: $wallet,
                amount: (float) $pendingRecord->amount,
                type: WalletTransaction::TYPE_CREDIT_REFUND,
                description: 'Refund for Order #'.($order?->increment_id ?? 'N/A').' (Refund #'.$refund->id.')',
                referenceType: get_class($refund),
                referenceId: $refund->id,
                createdByType: 'system',
                createdById: null
            );

            $pendingRecord->update([
                'status' => WalletPendingCredit::STATUS_COMPLETED,
                'error_message' => null,
            ]);

            Log::info("Wallet: Successfully processed queued credit for Refund #{$refund->id} (Pending Record #{$pendingRecord->id}).");
        } catch (\Throwable $e) {
            $pendingRecord->update([
                'status' => WalletPendingCredit::STATUS_PENDING,
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Wallet: Job attempt {$this->attempts()} failed for Pending Credit #{$pendingRecord->id}: ".$e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $pendingRecord = WalletPendingCredit::find($this->pendingCreditId);

        if ($pendingRecord) {
            $pendingRecord->update([
                'status' => WalletPendingCredit::STATUS_FAILED,
                'error_message' => 'Exhausted all retries: '.$exception->getMessage(),
            ]);
        }

        Log::critical("Wallet: ProcessWalletCreditJob PERMANENTLY FAILED for Pending Record #{$this->pendingCreditId}: ".$exception->getMessage());
    }
}
