<?php

namespace Webkul\DeliveryManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DeliveryManagement\Contracts\DeliveryCashCollection;

class DeliveryCashCollectionRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return DeliveryCashCollection::class;
    }
}
