<?php

namespace Webkul\Procurement\Security;

use DomainException;
use Webkul\User\Models\Admin;

class ProcurementAcl
{
    public const PERMISSION_ROOT = 'dropshipping.procurement_v2';

    public const PERMISSION_VIEW = 'dropshipping.procurement_v2.view';

    public const PERMISSION_BATCH_CREATE = 'dropshipping.procurement_v2.batch_create';

    public const PERMISSION_BATCH_APPROVE = 'dropshipping.procurement_v2.batch_approve';

    public const PERMISSION_SUBMIT = 'dropshipping.procurement_v2.submit';

    public const PERMISSION_PAYMENT_CONFIRM = 'dropshipping.procurement_v2.payment_confirm';

    public const PERMISSION_COST_VIEW = 'dropshipping.procurement_v2.cost_view';

    public const PERMISSION_VARIANCE_APPROVE = 'dropshipping.procurement_v2.variance_approve';

    public const PERMISSION_EXCEPTION_HANDLE = 'dropshipping.procurement_v2.exception_handle';

    public const PERMISSION_REPORTS_VIEW = 'dropshipping.procurement_v2.reports_view';

    /**
     * Check if currently authenticated admin user has the requested permission.
     */
    public static function check(string $permission): bool
    {
        if (function_exists('bouncer') && bouncer()->hasPermission($permission)) {
            return true;
        }

        $user = auth()->guard('admin')->user();
        if (! $user) {
            return false;
        }

        if ($user->role?->permission_type === 'all') {
            return true;
        }

        return (bool) $user->hasPermission($permission);
    }

    /**
     * Enforce permission check in HTTP controllers; throws 403 HTTP exception if unauthorized.
     */
    public static function authorize(string $permission): void
    {
        if (! self::check($permission)) {
            abort(403, "Unauthorized action: missing permission [{$permission}].");
        }
    }

    /**
     * Helper to check if current user can view financial/cost data.
     */
    public static function canViewCost(): bool
    {
        return self::check(self::PERMISSION_COST_VIEW);
    }

    /**
     * Enforce actor permission at domain service level.
     * Throws DomainException if actor is missing or unauthorized.
     *
     * @throws DomainException
     */
    public static function authorizeActor(int|Admin|null $actor, string $permission, bool $allowSystem = false): void
    {
        if ($actor === null || $actor === 0) {
            if ($allowSystem) {
                return;
            }

            throw new DomainException("Actor is required for sensitive procurement action [{$permission}].");
        }

        $admin = is_numeric($actor) ? Admin::find($actor) : $actor;
        if (! $admin) {
            throw new DomainException("Actor #{$actor} not found for sensitive procurement action [{$permission}].");
        }

        if ($admin->role?->permission_type === 'all') {
            return;
        }

        if (! $admin->hasPermission($permission)) {
            throw new DomainException("Actor #{$admin->id} does not have required permission [{$permission}].");
        }
    }
}
