<?php

namespace Webkul\DeliveryManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DeliveryManagement\Contracts\DeliverySettlement;

class DeliverySettlementRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return DeliverySettlement::class;
    }
}
