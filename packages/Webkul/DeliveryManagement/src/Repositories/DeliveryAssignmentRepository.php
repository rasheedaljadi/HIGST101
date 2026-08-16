<?php

namespace Webkul\DeliveryManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DeliveryManagement\Contracts\DeliveryAssignment;

class DeliveryAssignmentRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return DeliveryAssignment::class;
    }
}
