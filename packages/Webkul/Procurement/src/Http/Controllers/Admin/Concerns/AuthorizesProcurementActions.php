<?php

namespace Webkul\Procurement\Http\Controllers\Admin\Concerns;

use Webkul\Procurement\Security\ProcurementAcl;

trait AuthorizesProcurementActions
{
    /**
     * Authorize a procurement action against Bagisto admin ACL.
     * Throws 403 HTTP exception if unauthorized.
     */
    protected function authorizeProcurementAction(string $permission): void
    {
        ProcurementAcl::authorize($permission);
    }
}
