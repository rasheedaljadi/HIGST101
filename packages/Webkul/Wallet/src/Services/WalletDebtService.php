<?php

namespace Webkul\Wallet\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Webkul\Wallet\Contracts\WalletPromoDebt as WalletPromoDebtContract;
use Webkul\Wallet\Contracts\WalletPromoDebtSettlement as WalletPromoDebtSettlementContract;
use Webkul\Wallet\Contracts\WalletPromotionGrant as WalletPromotionGrantContract;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPromoDebt;
use Webkul\Wallet\Models\WalletPromoDebtSettlement;
use Webkul\Wallet\Models\WalletPromotionGrant;

class WalletDebtService
{
    /**
     * Create a promotional debt record for a deficit.
     */
    public function createDebt(
        WalletAccount $wallet,
        int $orderId,
        string $eventKey,
        string $deficitAmountStr,
        string $reason = 'Refund promo deficit',
        ?int $sourceRefundId = null,
        string $currencyCode = 'SAR'
    ): WalletPromoDebtContract {
        $deficitAmountStr = number_format((float) $deficitAmountStr, 4, '.', '');

        if (bccomp($deficitAmountStr, '0.0000', 4) <= 0) {
            throw new InvalidArgumentException('Debt deficit amount must be positive.');
        }

        $debt = WalletPromoDebt::create([
            'wallet_id' => $wallet->id,
            'customer_id' => $wallet->customer_id,
            'order_id' => $orderId,
            'source_refund_id' => $sourceRefundId,
            'event_key' => $eventKey,
            'currency_code' => $currencyCode,
            'original_debt_amount' => $deficitAmountStr,
            'remaining_debt_amount' => $deficitAmountStr,
            'settled_amount' => '0.0000',
            'status' => WalletPromoDebt::STATUS_ACTIVE,
            'reason' => $reason,
        ]);

        $this->reconcileWalletDebt($wallet);

        return $debt;
    }

    /**
     * Get all active promo debts for a wallet ordered FIFO.
     */
    public function getActiveDebts(int $walletId): Collection
    {
        return WalletPromoDebt::where('wallet_id', $walletId)
            ->where('remaining_debt_amount', '>', 0)
            ->whereIn('status', [WalletPromoDebt::STATUS_ACTIVE, WalletPromoDebt::STATUS_PARTIALLY_SETTLED])
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Settle debt from a new promotional grant lot.
     */
    public function settleDebtFromGrant(
        WalletPromotionGrantContract $grant,
        WalletPromoDebtContract $debt,
        string $settleAmountStr,
        string $eventKey
    ): WalletPromoDebtSettlementContract {
        $settleAmountStr = number_format((float) $settleAmountStr, 4, '.', '');

        if (bccomp($settleAmountStr, '0.0000', 4) <= 0) {
            throw new InvalidArgumentException('Settlement amount must be positive.');
        }

        $debtRemaining = number_format((float) $debt->remaining_debt_amount, 4, '.', '');
        if (bccomp($settleAmountStr, $debtRemaining, 4) === 1) {
            throw new InvalidArgumentException("Settlement amount {$settleAmountStr} exceeds remaining debt {$debtRemaining}.");
        }

        $grantRemaining = number_format((float) $grant->remaining_amount, 4, '.', '');
        if (bccomp($settleAmountStr, $grantRemaining, 4) === 1) {
            throw new InvalidArgumentException("Settlement amount {$settleAmountStr} exceeds remaining grant {$grantRemaining}.");
        }

        // 1. Update Debt Record
        $newDebtRemaining = bcsub($debtRemaining, $settleAmountStr, 4);
        $newDebtSettled = bcadd((string) $debt->settled_amount, $settleAmountStr, 4);

        $debt->remaining_debt_amount = $newDebtRemaining;
        $debt->settled_amount = $newDebtSettled;
        if (bccomp($newDebtRemaining, '0.0000', 4) === 0) {
            $debt->status = WalletPromoDebt::STATUS_SETTLED;
            $debt->settled_at = now();
        } else {
            $debt->status = WalletPromoDebt::STATUS_PARTIALLY_SETTLED;
        }
        $debt->save();

        // 2. Update Grant Record
        $newGrantRemaining = bcsub($grantRemaining, $settleAmountStr, 4);
        $newGrantConsumed = bcadd((string) $grant->consumed_amount, $settleAmountStr, 4);

        $grant->remaining_amount = $newGrantRemaining;
        $grant->consumed_amount = $newGrantConsumed;
        if (bccomp($newGrantRemaining, '0.0000', 4) === 0) {
            $grant->status = WalletPromotionGrant::STATUS_FULLY_CONSUMED;
        } else {
            $grant->status = WalletPromotionGrant::STATUS_PARTIALLY_CONSUMED;
        }
        $grant->save();

        // 3. Create Settlement Record
        $settlement = WalletPromoDebtSettlement::create([
            'debt_id' => $debt->id,
            'wallet_id' => $grant->wallet_id,
            'customer_id' => $grant->customer_id,
            'grant_id' => $grant->id,
            'settlement_amount' => $settleAmountStr,
            'base_settlement_amount' => $settleAmountStr,
            'currency_code' => $grant->currency_code,
            'event_key' => $eventKey,
        ]);

        return $settlement;
    }

    /**
     * Settle active debts across multiple debt lots for a new grant amount.
     * Returns ['settlements' => [...], 'total_settled' => string, 'net_to_credit' => string].
     */
    public function settleActiveDebtsForGrantAmount(
        WalletAccount $wallet,
        string $grantAmountStr,
        string $eventPrefix
    ): array {
        $grantAmountStr = number_format((float) $grantAmountStr, 4, '.', '');
        $activeDebts = $this->getActiveDebts($wallet->id);

        $totalSettled = '0.0000';
        $remainingAvailableToSettle = $grantAmountStr;
        $settlementPlan = [];

        foreach ($activeDebts as $debt) {
            if (bccomp($remainingAvailableToSettle, '0.0000', 4) <= 0) {
                break;
            }

            $debtRemaining = (string) $debt->remaining_debt_amount;
            if (bccomp($remainingAvailableToSettle, $debtRemaining, 4) >= 0) {
                $settleForThisDebt = $debtRemaining;
            } else {
                $settleForThisDebt = $remainingAvailableToSettle;
            }

            $settlementPlan[] = [
                'debt' => $debt,
                'amount' => $settleForThisDebt,
                'key' => "{$eventPrefix}:debt:{$debt->id}:settle",
            ];

            $totalSettled = bcadd($totalSettled, $settleForThisDebt, 4);
            $remainingAvailableToSettle = bcsub($remainingAvailableToSettle, $settleForThisDebt, 4);
        }

        $netToCredit = bcsub($grantAmountStr, $totalSettled, 4);

        return [
            'plan' => $settlementPlan,
            'total_settled' => $totalSettled,
            'net_to_credit' => $netToCredit,
        ];
    }

    /**
     * Reconcile sum of remaining debts and sync with wallet.promo_debt.
     */
    public function reconcileWalletDebt(WalletAccount $wallet): string
    {
        $sum = WalletPromoDebt::where('wallet_id', $wallet->id)
            ->whereIn('status', [WalletPromoDebt::STATUS_ACTIVE, WalletPromoDebt::STATUS_PARTIALLY_SETTLED])
            ->sum('remaining_debt_amount');

        $totalDebtStr = number_format((float) $sum, 4, '.', '');
        $wallet->promo_debt = $totalDebtStr;
        $wallet->save();

        return $totalDebtStr;
    }
}
