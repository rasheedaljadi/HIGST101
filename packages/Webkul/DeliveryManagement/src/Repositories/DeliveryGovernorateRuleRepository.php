<?php

namespace Webkul\DeliveryManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DeliveryManagement\Contracts\DeliveryGovernorateRule;

class DeliveryGovernorateRuleRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return DeliveryGovernorateRule::class;
    }
}
