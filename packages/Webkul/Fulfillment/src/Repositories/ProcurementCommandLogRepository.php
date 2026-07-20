<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class ProcurementCommandLogRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Models\ProcurementCommandLog';
    }
}
