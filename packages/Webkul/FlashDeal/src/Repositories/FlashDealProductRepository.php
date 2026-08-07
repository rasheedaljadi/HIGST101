<?php

namespace Webkul\FlashDeal\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\FlashDeal\Contracts\FlashDealProduct;

class FlashDealProductRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return FlashDealProduct::class;
    }
}
