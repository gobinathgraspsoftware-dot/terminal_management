<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * UserPolicy - Authorization for user management.
 * Uses permission-based authorization with Spatie Permission package
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any users
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_users') || $user->hasRole(['admin', 'supervisor']);
    }

    /**
     * Determine if the user can view a specific user
     */
    public function view(User $user, User $model): bool
    {
        // Check permission first
        if ($user->can('view_users')) {
            // Admin can view all
            if ($user->hasRole('admin')) return true;

            // Supervisor can view their team and self
            if ($user->hasRole('supervisor')) {
                return $model->supervisor_id === $user->id || $user->id === $model->id;
            }

            // Technician can only view self
            return $user->id === $model->id;
        }

        return false;
    }

    /**
     * Determine if the user can create users
     */
    public function create(User $user): bool
    {
        // Check for create_users permission
        return $user->can('create_users') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can update a specific user
     */
    public function update(User $user, User $model): bool
    {
        // Check permission first
        if ($user->can('edit_users')) {
            // Admin can edit all
            if ($user->hasRole('admin')) return true;

            // Supervisor can edit their team members
            if ($user->hasRole('supervisor')) {
                return $model->supervisor_id === $user->id;
            }

            // Users can edit themselves (for profile updates)
            return $user->id === $model->id;
        }

        return false;
    }

    /**
     * Determine if the user can delete a specific user
     */
    public function delete(User $user, User $model): bool
    {
        // Check permission and prevent self-deletion
        return $user->can('delete_users')
            && $user->id !== $model->id
            && ($user->hasRole('admin'));
    }

    /**
     * Determine if the user can restore a deleted user
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('delete_users') && $user->hasRole('admin');
    }

    /**
     * Determine if the user can assign roles
     */
    public function assignRole(User $user): bool
    {
        return $user->can('create_users') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can assign supervisors
     */
    public function assignSupervisor(User $user): bool
    {
        return $user->can('create_users') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can change passwords
     */
    public function changePassword(User $user, User $model): bool
    {
        // Admin can change anyone's password
        if ($user->hasRole('admin')) return true;

        // Users can change their own password
        return $user->id === $model->id;
    }

    /**
     * Perform pre-authorization checks
     * Super admin bypasses all checks
     */
    public function before(User $user, string $ability): ?bool
    {
        // Check if super admin exists in config
        if (config('app.super_admin_email') &&
            $user->email === config('app.super_admin_email') &&
            $user->hasRole('admin')) {
            return true;
        }

        return null; // Continue with normal authorization
    }
}
