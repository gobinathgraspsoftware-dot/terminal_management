<?php

namespace App\Policies;

use App\Models\Depot;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepotPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any depots.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_depots');
    }

    /**
     * Determine whether the user can view the depot.
     */
    public function view(User $user, Depot $depot): bool
    {
        return $user->can('view_depots');
    }

    /**
     * Determine whether the user can create depots.
     */
    public function create(User $user): bool
    {
        return $user->can('create_depots');
    }

    /**
     * Determine whether the user can update the depot.
     */
    public function update(User $user, Depot $depot): bool
    {
        return $user->can('edit_depots');
    }

    /**
     * Determine whether the user can delete the depot.
     */
    public function delete(User $user, Depot $depot): bool
    {
        // Cannot delete default depot
        if ($depot->is_default) {
            return false;
        }

        // Check if depot has stock
        $hasStock = $depot->stockBalances()->where('quantity_on_hand', '>', 0)->exists();
        if ($hasStock) {
            return false;
        }

        return $user->can('delete_depots');
    }

    /**
     * Determine whether the user can restore the depot.
     */
    public function restore(User $user, Depot $depot): bool
    {
        return $user->can('delete_depots');
    }

    /**
     * Determine whether the user can permanently delete the depot.
     */
    public function forceDelete(User $user, Depot $depot): bool
    {
        return $user->can('delete_depots') && $user->hasRole('admin');
    }
}
