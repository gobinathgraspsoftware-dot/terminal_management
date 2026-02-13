<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Grn;
use Illuminate\Auth\Access\HandlesAuthorization;

class GrnPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any GRNs
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view_grns');
    }

    /**
     * Determine if user can view the GRN
     */
    public function view(User $user, Grn $grn)
    {
        if (!$user->hasPermissionTo('view_grns')) {
            return false;
        }

        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisor can view team GRNs
        if ($user->hasRole('supervisor')) {
            return $grn->createdBy && $grn->createdBy->team_id === $user->team_id;
        }

        // Technician can view own GRNs
        if ($user->hasRole('technician')) {
            return $grn->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can create GRNs
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create_grns');
    }

    /**
     * Determine if user can update the GRN
     */
    public function update(User $user, Grn $grn)
    {
        if (!$user->hasPermissionTo('edit_grns')) {
            return false;
        }

        // Can only edit draft GRNs
        if ($grn->status !== Grn::STATUS_DRAFT) {
            return false;
        }

        // Admin can edit all draft GRNs
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisor can edit team draft GRNs
        if ($user->hasRole('supervisor')) {
            return $grn->createdBy && $grn->createdBy->team_id === $user->team_id;
        }

        // Technician can edit own draft GRNs
        if ($user->hasRole('technician')) {
            return $grn->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can post the GRN
     */
    public function post(User $user, Grn $grn)
    {
        if (!$user->hasPermissionTo('post_grns')) {
            return false;
        }

        // Can only post draft GRNs
        if ($grn->status !== Grn::STATUS_DRAFT) {
            return false;
        }

        // Admin can post all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisor can post team GRNs
        if ($user->hasRole('supervisor')) {
            return $grn->createdBy && $grn->createdBy->team_id === $user->team_id;
        }

        return false;
    }

    /**
     * Determine if user can cancel the GRN
     */
    public function cancel(User $user, Grn $grn)
    {
        if (!$user->hasPermissionTo('cancel_grns')) {
            return false;
        }

        // Can only cancel draft GRNs
        if ($grn->status !== Grn::STATUS_DRAFT) {
            return false;
        }

        // Only admin and supervisor can cancel
        return $user->hasRole(['admin', 'supervisor']);
    }

    /**
     * Determine if user can print the GRN
     */
    public function print(User $user, Grn $grn)
    {
        return $user->hasPermissionTo('print_grns') && $this->view($user, $grn);
    }
}
