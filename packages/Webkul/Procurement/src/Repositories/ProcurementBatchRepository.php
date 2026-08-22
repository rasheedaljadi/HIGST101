<?php

namespace Webkul\Procurement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Procurement\Contracts\ProcurementBatch;

class ProcurementBatchRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return ProcurementBatch::class;
    }
}
