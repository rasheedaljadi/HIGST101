<?php

namespace Webkul\DeliveryManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DeliveryManagement\Contracts\DeliveryAttemptLog;

class DeliveryAttemptLogRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return DeliveryAttemptLog::class;
    }
}
