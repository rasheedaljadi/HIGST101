<?php

namespace Webkul\CatalogRule\Repositories;

use Webkul\Core\Eloquent\Repository;

class CatalogRuleProductRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\CatalogRule\Contracts\CatalogRuleProduct';
    }

    /**
     * Disable repository cache for intermediate indexing table.
     *
     * @param  string  $method
     * @return bool
     */
    protected function allowedCache($method)
    {
        return false;
    }
}
