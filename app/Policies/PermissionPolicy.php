<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * PermissionPolicy - Authorization for permission management.
 * Only admins should be able to manage permissions.
 */
class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any permissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_permissions') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can view a specific permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->can('view_permissions') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can create permissions.
     * Only admins should create permissions.
     */
    public function create(User $user): bool
    {
        return $user->can('create_permissions') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can update a permission.
     * Only admins should update permissions.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->can('edit_permissions') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete a permission.
     * Only admins should delete permissions.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('delete_permissions') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can manage the permission matrix.
     */
    public function manageMatrix(User $user): bool
    {
        return $user->can('manage_permissions') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can export permissions.
     */
    public function export(User $user): bool
    {
        return $user->can('export_permissions') || $user->hasRole('admin');
    }
}
