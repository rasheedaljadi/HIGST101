<?php

namespace Webkul\Wallet\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Wallet\Exceptions\AccountUnderAuditException;
use Webkul\Wallet\Exceptions\InsufficientWalletBalanceException;
use Webkul\Wallet\Exceptions\WalletSuspendedException;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletTransaction;

class WalletService
{
    /**
     * Credit amount to wallet.
     *
     * Used for: CREDIT_TOPUP, CREDIT_REFUND, CREDIT_CANCEL, RELEASE_PAYMENT,
     *           RELEASE_HOLD, SUSPENSION_RELEASE, ADJUSTMENT (credit direction)
     */
    public function credit(
        WalletAccount $wallet,
        float $amount,
        string $type,
        string $description = '',
        array $meta = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $referenceTransactionId = null,
        ?string $createdByType = null,
        ?int $createdById = null
    ): WalletTransaction {
        $this->guardActive($wallet);

        return DB::transaction(function () use (
            $wallet, $amount, $type, $description, $meta,
            $referenceType, $referenceId, $referenceTransactionId,
            $createdByType, $createdById
        ) {
            $wallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

            $this->guardActive($wallet);

            if (empty(trim($description))) {
                throw new \InvalidArgumentException('A valid non-empty description or reason is required for audit trail.');
            }

            $newBalance = $wallet->available_balance + $amount;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'direction' => 'credit',
                'amount' => $amount,
                'running_balance' => $newBalance,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_transaction_id' => $referenceTransactionId,
                'created_by_type' => $createdByType,
                'created_by_id' => $createdById,
                'meta' => $meta ?: null,
            ]);

            $wallet->increment('available_balance', $amount);
            $wallet->increment('total_balance', $amount);
            $wallet->increment('cash_balance', $amount);

            $this->assertWalletInvariant($wallet);

            return $transaction;
        });
    }

    /**
     * Credit promotional balance to wallet.
     *
     * Sole authorized mutation point for promotional credit transactions.
     * Strictly uses BCMath decimal arithmetic without floating point.
     */
    public function creditPromotion(
        WalletAccount $wallet,
        string $amountStr,
        string $description = '',
        array $meta = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $createdByType = 'system',
        ?int $createdById = null
    ): WalletTransaction {
        $this->guardActive($wallet);

        return DB::transaction(function () use (
            $wallet, $amountStr, $description, $meta,
            $referenceType, $referenceId, $createdByType, $createdById
        ) {
            $wallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

            $this->guardActive($wallet);

            if ($wallet->isUnderAudit()) {
                throw new AccountUnderAuditException(
                    "Wallet Account #{$wallet->id} is under audit review and cannot receive promotional credits."
                );
            }

            if (empty(trim($description))) {
                throw new \InvalidArgumentException('A valid non-empty description or reason is required for audit trail.');
            }

            if (bccomp($amountStr, '0.0000', 4) <= 0) {
                throw new \InvalidArgumentException('Promotional credit amount must be strictly positive.');
            }

            $newRunningBalance = bcadd((string) $wallet->available_balance, $amountStr, 4);

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransaction::TYPE_CREDIT_PROMOTION,
                'direction' => 'credit',
                'amount' => $amountStr,
                'running_balance' => $newRunningBalance,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by_type' => $createdByType,
                'created_by_id' => $createdById,
                'meta' => $meta ?: null,
            ]);

            // Update promo_balance, available_balance, and total_balance using BCMath
            $wallet->promo_balance = bcadd((string) $wallet->promo_balance, $amountStr, 4);
            $wallet->available_balance = bcadd((string) $wallet->available_balance, $amountStr, 4);
            $wallet->total_balance = bcadd((string) $wallet->total_balance, $amountStr, 4);
            $wallet->save();

            $this->assertWalletInvariant($wallet);

            return $transaction;
        });
    }

    /**
     * Debit amount from wallet.
     *
     * Used for: DEBIT_PAYMENT, DEBIT_WITHDRAWAL, ADJUSTMENT (debit direction)
     */
    public function debit(
        WalletAccount $wallet,
        float $amount,
        string $type,
        string $description = '',
        array $meta = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $referenceTransactionId = null,
        ?string $createdByType = null,
        ?int $createdById = null
    ): WalletTransaction {
        $this->guardActive($wallet);

        return DB::transaction(function () use (
            $wallet, $amount, $type, $description, $meta,
            $referenceType, $referenceId, $referenceTransactionId, $createdByType, $createdById
        ) {
            $wallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

            $this->guardActive($wallet);

            if (empty(trim($description))) {
                throw new \InvalidArgumentException('A valid non-empty description or reason is required for audit trail.');
            }

            if ($wallet->available_balance < $amount || $wallet->total_balance < $amount) {
                throw new InsufficientWalletBalanceException($amount, $wallet->available_balance);
            }

            $newBalance = $wallet->available_balance - $amount;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'direction' => 'debit',
                'amount' => $amount,
                'running_balance' => $newBalance,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_transaction_id' => $referenceTransactionId,
                'created_by_type' => $createdByType,
                'created_by_id' => $createdById,
                'meta' => $meta ?: null,
            ]);

            $wallet->decrement('available_balance', $amount);
            $wallet->decrement('total_balance', $amount);
            $wallet->decrement('cash_balance', $amount);

            $this->assertWalletInvariant($wallet);

            return $transaction;
        });
    }

    /**
     * Hold amount (move from available to held).
     *
     * Used for: HOLD_WITHDRAWAL, SUSPENSION_FREEZE
     */
    public function hold(
        WalletAccount $wallet,
        float $amount,
        string $type,
        string $description = '',
        array $meta = [],
        ?string $referenceType = null,
        ?int $referenceId = null
    ): WalletTransaction {
        $this->guardActive($wallet);

        return DB::transaction(function () use (
            $wallet, $amount, $type, $description, $meta,
            $referenceType, $referenceId
        ) {
            $wallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

            $this->guardActive($wallet);

            if ($wallet->available_balance < $amount) {
                throw new InsufficientWalletBalanceException($amount, $wallet->available_balance);
            }

            // Holding: available -= amount, held += amount, total unchanged
            $newAvailable = $wallet->available_balance - $amount;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'direction' => 'debit',
                'amount' => $amount,
                'running_balance' => $newAvailable,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'meta' => $meta ?: null,
            ]);

            $wallet->decrement('available_balance', $amount);
            $wallet->increment('held_balance', $amount);

            $this->assertWalletInvariant($wallet);

            return $transaction;
        });
    }

    /**
     * Release held amount back to available.
     *
     * Used for: RELEASE_HOLD, SUSPENSION_RELEASE
     */
    public function release(
        WalletAccount $wallet,
        float $amount,
        string $type,
        string $description = '',
        array $meta = [],
        ?string $referenceType = null,
        ?int $referenceId = null
    ): WalletTransaction {
        return DB::transaction(function () use (
            $wallet, $amount, $type, $description, $meta,
            $referenceType, $referenceId
        ) {
            $wallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

            if ($wallet->held_balance < $amount) {
                throw new \RuntimeException(
                    "Cannot release {$amount}. Held balance is only {$wallet->held_balance}."
                );
            }

            $newAvailable = $wallet->available_balance + $amount;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'direction' => 'credit',
                'amount' => $amount,
                'running_balance' => $newAvailable,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'meta' => $meta ?: null,
            ]);

            $wallet->decrement('held_balance', $amount);
            $wallet->increment('available_balance', $amount);

            $this->assertWalletInvariant($wallet);

            return $transaction;
        });
    }

    /**
     * Complete a withdrawal (deduct from held_balance and total_balance atomically).
     *
     * C-02 Fix: Creates a SINGLE DEBIT_WITHDRAWAL transaction record.
     */
    public function completeWithdrawal(
        WalletAccount $wallet,
        float $amount,
        string $description = '',
        array $meta = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $createdByType = null,
        ?int $createdById = null
    ): WalletTransaction {
        return DB::transaction(function () use (
            $wallet, $amount, $description, $meta,
            $referenceType, $referenceId, $createdByType, $createdById
        ) {
            $wallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

            if ($wallet->held_balance < $amount) {
                throw new \RuntimeException(
                    "Cannot complete withdrawal of {$amount}. Held balance is only {$wallet->held_balance}."
                );
            }

            $runningAvailable = $wallet->available_balance;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransaction::TYPE_DEBIT_WITHDRAWAL,
                'direction' => 'debit',
                'amount' => $amount,
                'running_balance' => $runningAvailable,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by_type' => $createdByType,
                'created_by_id' => $createdById,
                'meta' => $meta ?: null,
            ]);

            $wallet->decrement('held_balance', $amount);
            $wallet->decrement('total_balance', $amount);

            $this->assertWalletInvariant($wallet);

            return $transaction;
        });
    }

    /**
     * Assert financial balance invariant:
     * 1. total_balance == cash_balance + promo_balance + unclassified_balance
     * 2. available_balance == (cash_balance - held_balance) + promo_balance
     */
    public function assertWalletInvariant(WalletAccount $wallet): void
    {
        $fresh = $wallet->fresh();

        $expectedTotal = bcadd(
            bcadd((string) ($fresh->cash_balance ?? '0.0000'), (string) ($fresh->promo_balance ?? '0.0000'), 4),
            (string) ($fresh->unclassified_balance ?? '0.0000'),
            4
        );

        $actualTotal = (string) $fresh->total_balance;

        if (bccomp($actualTotal, $expectedTotal, 4) !== 0) {
            throw new \RuntimeException(
                "Wallet financial invariant violation on Wallet #{$wallet->id}: total_balance ({$actualTotal}) does not match cash ({$fresh->cash_balance}) + promo ({$fresh->promo_balance}) + unclassified ({$fresh->unclassified_balance}) = {$expectedTotal}."
            );
        }

        $expectedAvailable = bcadd(
            bcsub((string) ($fresh->cash_balance ?? '0.0000'), (string) ($fresh->held_balance ?? '0.0000'), 4),
            (string) ($fresh->promo_balance ?? '0.0000'),
            4
        );

        $actualAvailable = (string) $fresh->available_balance;

        if (bccomp($actualAvailable, $expectedAvailable, 4) !== 0) {
            throw new \RuntimeException(
                "Wallet financial invariant violation on Wallet #{$wallet->id}: available_balance ({$actualAvailable}) does not match (cash ({$fresh->cash_balance}) - held ({$fresh->held_balance})) + promo ({$fresh->promo_balance}) = {$expectedAvailable}."
            );
        }
    }

    /**
     * Admin manual adjustment.
     *
     * Used for: ADJUSTMENT (credit or debit)
     * This is the ONLY way to correct erroneous transactions.
     * Always links to original transaction via reference_transaction_id.
     */
    public function adjust(
        WalletAccount $wallet,
        float $amount,
        string $direction, // 'credit' or 'debit'
        string $reason,
        int $adminUserId,
        ?int $referenceTransactionId = null
    ): WalletTransaction {
        if (! in_array($direction, ['credit', 'debit'])) {
            throw new \InvalidArgumentException("Direction must be 'credit' or 'debit'.");
        }

        if ($direction === 'credit') {
            return $this->credit(
                $wallet, $amount,
                WalletTransaction::TYPE_ADJUSTMENT,
                $reason, [],
                null, null,
                $referenceTransactionId,
                'admin', $adminUserId
            );
        }

        return $this->debit(
            $wallet, $amount,
            WalletTransaction::TYPE_ADJUSTMENT,
            $reason, [],
            null, null,
            $referenceTransactionId,
            'admin', $adminUserId
        );
    }

    /**
     * Get wallet balance summary.
     */
    public function getBalance(WalletAccount $wallet): array
    {
        $fresh = $wallet->fresh();

        return [
            'total_balance' => $fresh->total_balance,
            'available_balance' => $fresh->available_balance,
            'held_balance' => $fresh->held_balance,
            'currency_code' => $fresh->currency_code,
            'status' => $fresh->status,
        ];
    }

    /**
     * Guard: wallet must be active for debit/hold/credit operations.
     */
    private function guardActive(WalletAccount $wallet): void
    {
        if (! $wallet->isActive()) {
            throw new WalletSuspendedException;
        }
    }
}
