<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    /**
     * Can the user see the claims listing at all?
     */
    public function viewAny(User $user): bool
    {
        return $user->canAny(['view_claims', 'view_all_claims', 'view_team_claims', 'view_own_claims']);
    }

    /**
     * Can the user view a specific claim?
     */
    public function view(User $user, Claim $claim): bool
    {
        if (!$user->canAny(['view_claims', 'view_all_claims', 'view_team_claims', 'view_own_claims'])) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamIds[] = $user->id;
            return in_array($claim->technician_id, $teamIds)
                || $claim->submitted_by === $user->id
                || $claim->created_by === $user->id;
        }

        // Technician
        return $claim->technician_id === $user->id
            || $claim->submitted_by === $user->id
            || $claim->created_by === $user->id;
    }

    /**
     * Can the user create a claim?
     *
     * Rules:
     *  - Admin: always
     *  - Supervisor (external): yes — claims are applicable
     *  - Supervisor (internal): NO — claims not applicable for internal supervisors
     *  - Technician: yes
     */
    public function create(User $user): bool
    {
        if (!$user->can('create_claims')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        // Internal supervisors cannot create claims
        if ($user->hasRole('supervisor') && $user->isInternalSupervisor()) {
            return false;
        }

        return true;
    }

    /**
     * Can the user update a claim?
     *
     * Rules:
     *  - Admin: always (if claim is editable)
     *  - Supervisor (external): own submitted claims only (editable status)
     *  - Supervisor (internal): NO
     *  - Technician: own claims in draft/submitted status
     */
    public function update(User $user, Claim $claim): bool
    {
        if (!$user->can('edit_claims')) {
            return false;
        }

        // Claim must be in an editable status
        if (!$claim->isEditable()) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        // Internal supervisors cannot edit claims
        if ($user->hasRole('supervisor') && $user->isInternalSupervisor()) {
            return false;
        }

        // External supervisor: own claims only
        if ($user->hasRole('supervisor')) {
            return $claim->submitted_by === $user->id || $claim->created_by === $user->id;
        }

        // Technician: own claims only
        return $claim->technician_id === $user->id
            || $claim->submitted_by === $user->id;
    }

    /**
     * Can the user verify/approve a claim?
     */
    public function verify(User $user, Claim $claim): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->canAny(['verify_claims', 'approve_claims']);
    }

    /**
     * Can the user process bulk payments?
     */
    public function bulkPay(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->canAny(['bulk_pay_claims', 'mark_paid_claims']);
    }

    /**
     * Can the user export claims?
     */
    public function export(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->can('export_claims');
    }
}
