<?php

namespace Webkul\Wallet\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Webkul\Wallet\Contracts\WalletPromotion as WalletPromotionContract;
use Webkul\Wallet\Exceptions\AccountUnderAuditException;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPromoDebt;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromotionUsage;

class WalletPromotionOrchestrator
{
    public function __construct(
        protected WalletService $walletService,
        protected PromotionGrantService $grantService,
        protected WalletDebtService $debtService
    ) {}

    /**
     * Process and apply a promotion grant with atomic debt settlement and idempotency.
     */
    public function applyPromotionGrant(
        WalletPromotionContract $promotion,
        int $walletId,
        string $eventKey,
        string $eligibleAmountStr,
        string $referenceType,
        int $referenceId,
        string $currencyCode = 'SAR'
    ): array {
        // 1. Feature Flag Guard: if mode is 'legacy_only', skip promotion orchestration
        $mode = config('sales.wallet_promotions.mode', 'legacy_only');
        if ($mode === 'legacy_only') {
            return [
                'applied' => false,
                'reason' => 'Feature flag sales.wallet_promotions.mode is legacy_only',
            ];
        }

        try {
            return DB::transaction(function () use (
                $promotion,
                $walletId,
                $eventKey,
                $eligibleAmountStr,
                $referenceType,
                $referenceId,
                $currencyCode
            ) {
                // 2. Lock Wallet Account
                $wallet = WalletAccount::lockForUpdate()->findOrFail($walletId);

                // 3. Audit Check
                if ($wallet->isUnderAudit()) {
                    throw new AccountUnderAuditException("Wallet Account #{$wallet->id} is under audit review.");
                }

                // 4. Pre-check Idempotency
                $existingUsage = WalletPromotionUsage::where('promotion_id', $promotion->id)
                    ->where('event_key', $eventKey)
                    ->first();

                if ($existingUsage) {
                    $existingGrant = WalletPromotionGrant::where('usage_id', $existingUsage->id)->firstOrFail();

                    return [
                        'applied' => true,
                        'is_idempotent' => true,
                        'usage' => $existingUsage,
                        'grant' => $existingGrant,
                        'net_credited' => (string) $existingUsage->net_credited_amount,
                    ];
                }

                // 5. Check Eligibility
                if (! $this->grantService->isEligible($promotion, $wallet->customer_id, $eligibleAmountStr)) {
                    return [
                        'applied' => false,
                        'reason' => 'Customer or event not eligible for this promotion',
                    ];
                }

                // 6. Calculate Reward
                $rewardAmountStr = $this->grantService->calculateReward($promotion, $eligibleAmountStr);
                if (bccomp($rewardAmountStr, '0.0000', 4) <= 0) {
                    return [
                        'applied' => false,
                        'reason' => 'Calculated reward amount is zero',
                    ];
                }

                // 7. Calculate Debt Settlement Plan
                $settlementData = $this->debtService->settleActiveDebtsForGrantAmount(
                    wallet: $wallet,
                    grantAmountStr: $rewardAmountStr,
                    eventPrefix: $eventKey
                );

                $netToCredit = $settlementData['net_to_credit'];
                $totalSettled = $settlementData['total_settled'];

                // 8. Create Usage & Grant records
                $grantBundle = $this->grantService->createGrant(
                    promotion: $promotion,
                    wallet: $wallet,
                    eventKey: $eventKey,
                    rewardAmountStr: $rewardAmountStr,
                    netCreditedAmountStr: $netToCredit,
                    referenceType: $referenceType,
                    referenceId: $referenceId,
                    currencyCode: $currencyCode,
                    decisionMeta: [
                        'eligible_amount' => $eligibleAmountStr,
                        'reward_amount' => $rewardAmountStr,
                        'total_settled' => $totalSettled,
                        'net_to_credit' => $netToCredit,
                    ]
                );

                $grant = $grantBundle['grant'];
                $usage = $grantBundle['usage'];

                // 9. Execute Planned Debt Settlements
                foreach ($settlementData['plan'] as $item) {
                    $lockedDebt = WalletPromoDebt::lockForUpdate()->find($item['debt']->id);
                    if ($lockedDebt && $lockedDebt->status !== WalletPromoDebt::STATUS_SETTLED) {
                        $this->debtService->settleDebtFromGrant(
                            grant: $grant,
                            debt: $lockedDebt,
                            settleAmountStr: $item['amount'],
                            eventKey: $item['key']
                        );
                    }
                }

                // 10. Sync Wallet Promo Debt
                $this->debtService->reconcileWalletDebt($wallet);

                // 11. Credit Net Amount to Wallet (via WalletService only!)
                $txn = null;
                if (bccomp($netToCredit, '0.0000', 4) === 1) {
                    $txn = $this->walletService->creditPromotion(
                        wallet: $wallet,
                        amountStr: $netToCredit,
                        description: "Promotion Reward: {$promotion->name} (Net credited: {$netToCredit}, Settled debt: {$totalSettled})",
                        referenceType: WalletPromotionGrant::class,
                        referenceId: $grant->id
                    );

                    $grant->wallet_transaction_id = $txn->id;
                    $grant->save();
                }

                return [
                    'applied' => true,
                    'is_idempotent' => false,
                    'usage' => $usage,
                    'grant' => $grant,
                    'transaction' => $txn,
                    'reward_amount' => $rewardAmountStr,
                    'total_settled' => $totalSettled,
                    'net_credited' => $netToCredit,
                ];
            });
        } catch (QueryException $e) {
            // Intercept concurrent duplicate key error
            if ($e->errorInfo[1] == 1062 || str_contains($e->getMessage(), 'Duplicate')) {
                $existingUsage = WalletPromotionUsage::where('promotion_id', $promotion->id)
                    ->where('event_key', $eventKey)
                    ->firstOrFail();

                $existingGrant = WalletPromotionGrant::where('usage_id', $existingUsage->id)->firstOrFail();

                return [
                    'applied' => true,
                    'is_idempotent' => true,
                    'usage' => $existingUsage,
                    'grant' => $existingGrant,
                    'net_credited' => (string) $existingUsage->net_credited_amount,
                ];
            }

            throw $e;
        }
    }
}
