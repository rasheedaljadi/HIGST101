<?php

namespace Webkul\OfflinePayments\Repositories;

use Webkul\Core\Eloquent\Repository;

class OfflinePaymentAccountRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\OfflinePayments\Contracts\OfflinePaymentAccount';
    }
}
