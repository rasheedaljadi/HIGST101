<?php

namespace Webkul\Procurement\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        ProcurementDemand::class,
        ProcurementBatch::class,
        SupplierPurchaseOrder::class,
        SupplierPurchaseOrderItem::class,
        ProcurementDemandAllocation::class,
    ];
}
