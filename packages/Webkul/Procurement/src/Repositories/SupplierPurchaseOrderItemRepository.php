<?php

namespace Webkul\Procurement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Procurement\Contracts\SupplierPurchaseOrderItem;

class SupplierPurchaseOrderItemRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return SupplierPurchaseOrderItem::class;
    }
}
