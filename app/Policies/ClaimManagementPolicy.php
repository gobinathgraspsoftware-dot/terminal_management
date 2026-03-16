<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

class ClaimManagementPolicy
{
    /**
     * Admin bypass - runs before all other checks.
     * Returning true grants access; returning null falls through to specific methods.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * View any claims listing
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view_claims', 'view_all_claims', 'view_team_claims', 'view_own_claims']);
    }

    /**
     * View a specific claim
     */
    public function view(User $user, Claim $claim): bool
    {
        if ($user->hasPermissionTo('view_team_claims') && $user->hasRole('supervisor')) {
            $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamIds[] = $user->id;
            return in_array($claim->technician_id, $teamIds)
                || $claim->submitted_by === $user->id
                || $claim->created_by === $user->id;
        }

        if ($user->hasPermissionTo('view_own_claims')) {
            return $claim->technician_id === $user->id
                || $claim->submitted_by === $user->id
                || $claim->created_by === $user->id;
        }

        return false;
    }

    /**
     * Create a new claim (other claims)
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_claims');
    }

    /**
     * Verify / mark non-claimable
     */
    public function verify(User $user, ?Claim $claim = null): bool
    {
        if (!$user->hasPermissionTo('verify_claims')) {
            return false;
        }

        // If claim passed, check it can be verified
        if ($claim && !$claim->canBeVerified()) {
            return false;
        }

        return true;
    }

    /**
     * Bulk pay claims
     */
    public function bulkPay(User $user): bool
    {
        return $user->hasPermissionTo('bulk_pay_claims');
    }

    /**
     * Export claims
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_claims');
    }
}
