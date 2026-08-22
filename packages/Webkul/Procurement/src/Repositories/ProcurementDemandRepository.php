<?php

namespace Webkul\Procurement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Procurement\Contracts\ProcurementDemand;

class ProcurementDemandRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return ProcurementDemand::class;
    }
}
