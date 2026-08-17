<?php

namespace Webkul\DeliveryManagement\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAttemptLog;
use Webkul\DeliveryManagement\Models\DeliveryAuditLog;
use Webkul\DeliveryManagement\Models\DeliveryCashCollection;
use Webkul\DeliveryManagement\Models\DeliveryGovernorateRule;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\DeliveryManagement\Models\DeliverySettlement;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        DeliveryGovernorateRule::class,
        DeliveryPoint::class,
        DeliveryAssignment::class,
        DeliveryAttemptLog::class,
        DeliveryCashCollection::class,
        DeliverySettlement::class,
        DeliveryAuditLog::class,
    ];
}
