<?php

namespace Webkul\Wallet\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletBackfillDiscrepancy;
use Webkul\Wallet\Models\WalletPendingCredit;
use Webkul\Wallet\Models\WalletPromoDebt;
use Webkul\Wallet\Models\WalletPromoDebtSettlement;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionAudit;
use Webkul\Wallet\Models\WalletPromotionGrant;
use Webkul\Wallet\Models\WalletPromotionGrantConsumption;
use Webkul\Wallet\Models\WalletPromotionOrderItemAllocation;
use Webkul\Wallet\Models\WalletPromotionOutbox;
use Webkul\Wallet\Models\WalletPromotionUsage;
use Webkul\Wallet\Models\WalletReconciliation;
use Webkul\Wallet\Models\WalletTopUp;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Models\WalletWithdrawalMethod;
use Webkul\Wallet\Models\WalletWithdrawalRequest;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models to register with Concord.
     *
     * @var array
     */
    protected $models = [
        WalletAccount::class,
        WalletTransaction::class,
        WalletTopUp::class,
        WalletWithdrawalRequest::class,
        WalletPendingCredit::class,
        WalletReconciliation::class,
        WalletWithdrawalMethod::class,
        WalletPromotion::class,
        WalletPromotionUsage::class,
        WalletPromotionGrant::class,
        WalletPromotionGrantConsumption::class,
        WalletPromotionOrderItemAllocation::class,
        WalletPromoDebt::class,
        WalletPromoDebtSettlement::class,
        WalletPromotionOutbox::class,
        WalletBackfillDiscrepancy::class,
        WalletPromotionAudit::class,
    ];
}
