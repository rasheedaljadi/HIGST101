<?php

namespace Webkul\DeliveryManagement\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\User\Models\Admin;

class DeliveryAgentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the assignment.
     */
    public function view(Admin $user, DeliveryAssignment $assignment): bool
    {
        // Admin or supervisor can view all
        if ($user->role?->permission_type === 'all' || $user->hasRole('admin') || $user->hasRole('supervisor')) {
            return true;
        }

        // Courier can view only assigned tasks
        if ($assignment->delivery_boy_id === $user->id) {
            return true;
        }

        // Delivery point agent can view only tasks for their delivery point
        if (isset($user->delivery_point_id) && $assignment->delivery_point_id === (int) $user->delivery_point_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the assignment status (start delivery, report failure, confirm delivery).
     */
    public function update(Admin $user, DeliveryAssignment $assignment): bool
    {
        // Admin / supervisor can update
        if ($user->role?->permission_type === 'all' || $user->hasRole('admin') || $user->hasRole('supervisor')) {
            return true;
        }

        // Courier can update their own tasks
        if ($assignment->delivery_boy_id === $user->id) {
            return true;
        }

        // Delivery point agent can update tasks for their point
        if (isset($user->delivery_point_id) && $assignment->delivery_point_id === (int) $user->delivery_point_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve a return to Hayest.
     */
    public function returnToHayest(Admin $user, DeliveryAssignment $assignment): bool
    {
        // Strictly restricted to supervisors / admins
        return $user->role?->permission_type === 'all' || $user->hasRole('admin') || $user->hasRole('supervisor');
    }
}
