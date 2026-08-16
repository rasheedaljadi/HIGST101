<?php

namespace Webkul\Inventory\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Inventory\Contracts\InventoryMovement;

class InventoryMovementRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return InventoryMovement::class;
    }
}
