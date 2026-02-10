<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * RolePolicy - Authorization for role management.
 * Only admins should be able to manage roles.
 */
class RolePolicy
{
    use HandlesAuthorization;

    /**
     * System roles that cannot be modified or deleted.
     */
    protected array $systemRoles = ['admin', 'supervisor', 'technician'];

    /**
     * Determine if the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_roles') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can view a specific role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('view_roles') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can create roles.
     * Only admins should create roles.
     */
    public function create(User $user): bool
    {
        return $user->can('create_roles') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can update a role.
     * Only admins should update roles, and system roles cannot be modified.
     */
    public function update(User $user, Role $role): bool
    {
        // Prevent modification of system roles
        if (in_array($role->name, $this->systemRoles)) {
            return false;
        }

        return $user->can('edit_roles') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete a role.
     * Only admins should delete roles, and system roles cannot be deleted.
     */
    public function delete(User $user, Role $role): bool
    {
        // Prevent deletion of system roles
        if (in_array($role->name, $this->systemRoles)) {
            return false;
        }

        // Prevent deletion of roles that have users
        if ($role->users()->count() > 0) {
            return false;
        }

        return $user->can('delete_roles') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can manage role permissions.
     */
    public function managePermissions(User $user, Role $role): bool
    {
        // System roles permissions cannot be modified
        if (in_array($role->name, $this->systemRoles)) {
            return false;
        }

        return $user->can('manage_roles') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can assign roles to users.
     */
    public function assignRoles(User $user): bool
    {
        return $user->can('assign_roles') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can export roles.
     */
    public function export(User $user): bool
    {
        return $user->can('export_roles') || $user->hasRole('admin');
    }
}
