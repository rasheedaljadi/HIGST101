<?php

namespace Webkul\Wallet\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletPendingCredit;
use Webkul\Wallet\Models\WalletReconciliation;
use Webkul\Wallet\Models\WalletTopUp;
use Webkul\Wallet\Models\WalletTransaction;
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
    ];
}
