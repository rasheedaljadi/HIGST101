<?php

namespace Webkul\CatalogRule\Repositories;

use Webkul\Core\Eloquent\Repository;

class CatalogRuleProductPriceRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return 'Webkul\CatalogRule\Contracts\CatalogRuleProductPrice';
    }

    /**
     * Disable repository cache for intermediate price index table.
     *
     * @param  string  $method
     * @return bool
     */
    protected function allowedCache($method)
    {
        return false;
    }
}
