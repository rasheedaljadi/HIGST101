<?php

namespace Webkul\OfflinePayments\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\OfflinePayments\Contracts\OfflinePaymentDestination;

class OfflinePaymentDestinationRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return OfflinePaymentDestination::class;
    }
}
