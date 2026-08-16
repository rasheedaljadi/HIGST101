<?php

namespace Webkul\DeliveryManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DeliveryManagement\Contracts\DeliveryPoint;

class DeliveryPointRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return DeliveryPoint::class;
    }
}
