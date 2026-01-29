<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PartnerPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Admins can do anything
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any partners.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_partners');
    }

    /**
     * Determine whether the user can view the partner.
     */
    public function view(User $user, Partner $partner): bool
    {
        // Check basic permission
        if (!$user->can('view_partners')) {
            return false;
        }

        // Technicians can only view active partners
        if ($user->hasRole('technician')) {
            return $partner->status === Partner::STATUS_ACTIVE;
        }

        return true;
    }

    /**
     * Determine whether the user can create partners.
     */
    public function create(User $user): bool
    {
        return $user->can('create_partners');
    }

    /**
     * Determine whether the user can update the partner.
     */
    public function update(User $user, Partner $partner): bool
    {
        // Only admins can update (supervisors and technicians are view-only)
        return $user->can('edit_partners');
    }

    /**
     * Determine whether the user can delete the partner.
     */
    public function delete(User $user, Partner $partner): bool
    {
        // Check permission
        if (!$user->can('delete_partners')) {
            return false;
        }

        // Cannot delete partner with active clients
        if ($partner->clients()->where('status', 'active')->exists()) {
            return false;
        }

        // Cannot delete partner with pending jobs
        if ($partner->jobOrders()->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the partner.
     */
    public function restore(User $user, Partner $partner): bool
    {
        return $user->can('delete_partners');
    }

    /**
     * Determine whether the user can permanently delete the partner.
     */
    public function forceDelete(User $user, Partner $partner): bool
    {
        // Only admins can force delete
        return $user->hasRole('admin') && $user->can('delete_partners');
    }

    /**
     * Determine whether the user can export partners.
     */
    public function export(User $user): bool
    {
        return $user->can('export_partners');
    }

    /**
     * Determine whether the user can import partners.
     */
    public function import(User $user): bool
    {
        return $user->can('import_partners');
    }

    /**
     * Determine whether the user can toggle partner status.
     */
    public function toggleStatus(User $user, Partner $partner): bool
    {
        return $user->can('edit_partners');
    }

    /**
     * Determine whether the user can regenerate API key.
     */
    public function regenerateApiKey(User $user, Partner $partner): bool
    {
        return $user->can('edit_partners');
    }
}
