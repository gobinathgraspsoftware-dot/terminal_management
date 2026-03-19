<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * TeamPolicy - Authorization for team management.
 *
 * NOTE: No removeFromTeam policy — technicians cannot be made independent.
 *       All technicians must have a supervisor.
 *       Only INTERNAL supervisors can have teams.
 *       External supervisors operate independently without technicians.
 */
class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Can view all teams? Admin only.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Can view own team?
     * Admin: always.
     * Internal Supervisor: yes (has team).
     * External Supervisor: no (no team to view).
     */
    public function viewOwnTeam(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return $user->isInternalSupervisor();
        }

        return false;
    }

    /**
     * Can view a specific team member?
     * Admin: any member.
     * Internal Supervisor: own team members only.
     * External Supervisor: no team members.
     * Technician: self only.
     */
    public function viewTeamMember(User $user, User $member): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            if ($user->isExternalSupervisor()) {
                return false;
            }
            return $member->supervisor_id === $user->id;
        }

        return $user->id === $member->id;
    }

    /**
     * Can assign technicians? Admin only.
     */
    public function assignTechnician(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Can bulk assign? Admin only.
     */
    public function bulkAssign(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Can export team data?
     * Admin: always.
     * Internal Supervisor: yes.
     * External Supervisor: no (no team to export).
     */
    public function exportTeam(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return $user->isInternalSupervisor();
        }

        return false;
    }
}
