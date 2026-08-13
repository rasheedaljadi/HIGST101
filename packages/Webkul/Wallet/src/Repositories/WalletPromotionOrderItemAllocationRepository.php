<?php

namespace Webkul\Wallet\Repositories;

use Webkul\Core\Eloquent\Repository;

class WalletPromotionOrderItemAllocationRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Wallet\Contracts\WalletPromotionOrderItemAllocation';
    }
}
