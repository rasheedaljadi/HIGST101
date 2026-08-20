<?php

namespace Webkul\Procurement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Procurement\Contracts\ProcurementDemandAllocation;

class ProcurementDemandAllocationRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return ProcurementDemandAllocation::class;
    }
}
