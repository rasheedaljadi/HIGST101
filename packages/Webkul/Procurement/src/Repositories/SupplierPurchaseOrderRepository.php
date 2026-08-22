<?php

namespace Webkul\Procurement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Procurement\Contracts\SupplierPurchaseOrder;

class SupplierPurchaseOrderRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return SupplierPurchaseOrder::class;
    }
}
