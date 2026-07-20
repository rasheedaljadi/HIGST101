<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class ProcurementDeadLetterRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Models\ProcurementDeadLetter';
    }
}
