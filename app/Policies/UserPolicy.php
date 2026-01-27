<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Must have permission
        if (!$user->can('users.view')) {
            return false;
        }

        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisor can view own profile and team members
        if ($user->hasRole('supervisor')) {
            return $model->id === $user->id 
                || $model->supervisor_id === $user->id;
        }

        // Technician can only view own profile
        if ($user->hasRole('technician')) {
            return $model->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Must have permission
        if (!$user->can('users.edit')) {
            return false;
        }

        // Admin can update all (except cannot demote themselves from admin)
        if ($user->hasRole('admin')) {
            // Prevent admin from changing their own role
            if ($model->id === $user->id && request()->has('role') && request()->role !== 'admin') {
                return false;
            }
            return true;
        }

        // Supervisor can update team members (but not change roles)
        if ($user->hasRole('supervisor')) {
            if ($model->supervisor_id === $user->id) {
                // Cannot change role
                if (request()->has('role')) {
                    return false;
                }
                return true;
            }
        }

        // Technician cannot update any user
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Must have permission
        if (!$user->can('users.delete')) {
            return false;
        }

        // Cannot delete self
        if ($model->id === $user->id) {
            return false;
        }

        // Admin can delete all users
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisors and technicians cannot delete
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Must have delete permission to restore
        if (!$user->can('users.delete')) {
            return false;
        }

        // Only admins can restore
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only super admins can force delete (if implemented)
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can assign roles.
     */
    public function assignRole(User $user, User $model): bool
    {
        // Only admins can assign roles
        return $user->hasRole('admin') && $user->can('users.edit');
    }

    /**
     * Determine whether the user can assign supervisors.
     */
    public function assignSupervisor(User $user, User $model): bool
    {
        // Admins can assign supervisors
        if ($user->hasRole('admin') && $user->can('users.edit')) {
            return true;
        }

        // Supervisors can assign themselves to technicians
        if ($user->hasRole('supervisor') && $user->can('users.edit')) {
            // Can only assign to technicians
            return $model->hasRole('technician');
        }

        return false;
    }

    /**
     * Determine whether the user can change password for the model.
     */
    public function changePassword(User $user, User $model): bool
    {
        // Must have edit permission
        if (!$user->can('users.edit')) {
            return false;
        }

        // Admin can change any password
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can change their own password
        if ($model->id === $user->id) {
            return true;
        }

        // Supervisor can change team member passwords
        if ($user->hasRole('supervisor') && $model->supervisor_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can toggle status.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        // Must have edit permission
        if (!$user->can('users.edit')) {
            return false;
        }

        // Cannot toggle own status
        if ($model->id === $user->id) {
            return false;
        }

        // Only admins can toggle status
        return $user->hasRole('admin');
    }
}