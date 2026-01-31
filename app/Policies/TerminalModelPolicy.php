<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TerminalModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class TerminalModelPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any terminal models
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_models');
    }

    /**
     * Determine if user can view the terminal model
     */
    public function view(User $user, TerminalModel $terminalModel): bool
    {
        return $user->hasPermissionTo('view_models');
    }

    /**
     * Determine if user can create terminal models
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_models');
    }

    /**
     * Determine if user can update the terminal model
     */
    public function update(User $user, TerminalModel $terminalModel): bool
    {
        return $user->hasPermissionTo('edit_models');
    }

    /**
     * Determine if user can delete the terminal model
     */
    public function delete(User $user, TerminalModel $terminalModel): bool
    {
        return $user->hasPermissionTo('delete_models');
    }

    /**
     * Determine if user can restore the terminal model
     */
    public function restore(User $user, TerminalModel $terminalModel): bool
    {
        return $user->hasPermissionTo('delete_models');
    }

    /**
     * Determine if user can permanently delete the terminal model
     */
    public function forceDelete(User $user, TerminalModel $terminalModel): bool
    {
        return $user->hasPermissionTo('delete_models');
    }
}
