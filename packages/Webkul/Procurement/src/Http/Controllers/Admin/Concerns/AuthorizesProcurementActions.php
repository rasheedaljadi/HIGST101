<?php

namespace Webkul\Procurement\Http\Controllers\Admin\Concerns;

use Webkul\Procurement\Security\ProcurementAcl;
use Webkul\User\Models\Admin;

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

    /**
     * Resolve the current authenticated admin ID reliably.
     */
    protected function resolveAdminActorId(): int
    {
        return (int) (auth()->guard('admin')->id() ?: auth()->id()) ?: (Admin::first()?->id ?? 1);
    }
}
