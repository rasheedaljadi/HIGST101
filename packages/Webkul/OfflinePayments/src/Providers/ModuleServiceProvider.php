<?php

namespace Webkul\OfflinePayments\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\OfflinePayments\Models\OfflinePaymentAccount;
use Webkul\OfflinePayments\Models\OfflinePaymentDestination;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Models registered for Concord module proxy binding.
     *
     * @var array
     */
    protected $models = [
        OfflinePaymentAccount::class,
        OfflinePaymentDestination::class,
    ];
}
