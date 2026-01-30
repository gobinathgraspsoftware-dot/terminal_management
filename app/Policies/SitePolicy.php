<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * SitePolicy
 *
 * Handles authorization for Site operations
 */
class SitePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any sites
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_sites');
    }

    /**
     * Determine if user can view a specific site
     */
    public function view(User $user, Site $site): bool
    {
        // Admin can view all sites
        if ($user->hasRole('admin')) {
            return $user->can('view_sites');
        }

        // Supervisor can view sites in their coverage states
        if ($user->hasRole('supervisor')) {
            $coverageStates = json_decode($user->coverage_states, true) ?? [];
            return $user->can('view_sites') && in_array($site->state, $coverageStates);
        }

        // Technician can view sites they have jobs at
        if ($user->hasRole('technician')) {
            // Check if technician has any jobs at this site
            return $user->can('view_sites') && $user->jobAssignments()
                ->whereHas('jobOrder', function ($query) use ($site) {
                    $query->where('site_id', $site->id);
                })->exists();
        }

        return false;
    }

    /**
     * Determine if user can create sites
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') && $user->can('create_sites');
    }

    /**
     * Determine if user can update a site
     */
    public function update(User $user, Site $site): bool
    {
        return $user->hasRole('admin') && $user->can('edit_sites');
    }

    /**
     * Determine if user can delete a site
     */
    public function delete(User $user, Site $site): bool
    {
        // Cannot delete if site has related records
        if ($site->jobOrders()->count() > 0) {
            return false;
        }

        if ($site->siteAssets()->count() > 0) {
            return false;
        }

        return $user->hasRole('admin') && $user->can('delete_sites');
    }

    /**
     * Determine if user can restore a site
     */
    public function restore(User $user, Site $site): bool
    {
        return $user->hasRole('admin') && $user->can('restore_sites');
    }

    /**
     * Determine if user can permanently delete a site
     */
    public function forceDelete(User $user, Site $site): bool
    {
        // Only super admin can force delete
        return $user->hasRole('admin') && $user->can('delete_sites');
    }

    /**
     * Determine if user can manage site contacts
     */
    public function manageContacts(User $user, Site $site): bool
    {
        return $user->hasRole('admin') && $user->can('edit_sites');
    }

    /**
     * Determine if user can view site assets
     */
    public function viewAssets(User $user, Site $site): bool
    {
        return $user->can('view_sites') || $user->can('view_assets');
    }
}
