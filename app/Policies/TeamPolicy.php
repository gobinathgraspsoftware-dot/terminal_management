<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * TeamPolicy
 * 
 * Authorization policy for team management operations
 * Controls access to team viewing, assignment, and management features
 * 
 * @package App\Policies
 */
class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can manage teams (admin only)
     * 
     * @param User $user
     * @return bool
     */
    public function manageTeams(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can view team dashboard
     * 
     * @param User $user
     * @return bool
     */
    public function viewTeamDashboard(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor']);
    }

    /**
     * Determine if user can view a specific team member
     * 
     * @param User $user
     * @param User $teamMember
     * @return bool
     */
    public function viewTeamMember(User $user, User $teamMember): bool
    {
        // Admins can view anyone
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisors can view their team members
        if ($user->hasRole('supervisor')) {
            return $teamMember->supervisor_id === $user->id;
        }

        // Technicians can view themselves
        if ($user->hasRole('technician')) {
            return $user->id === $teamMember->id;
        }

        return false;
    }

    /**
     * Determine if user can assign technicians to supervisors
     * 
     * @param User $user
     * @return bool
     */
    public function assignTechnicians(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can perform bulk assignments
     * 
     * @param User $user
     * @return bool
     */
    public function bulkAssign(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can remove a technician from a team
     * 
     * @param User $user
     * @param User $technician
     * @return bool
     */
    public function removeFromTeam(User $user, User $technician): bool
    {
        // Only admins can remove from teams
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can view team statistics
     * 
     * @param User $user
     * @param User $supervisor
     * @return bool
     */
    public function viewTeamStats(User $user, User $supervisor): bool
    {
        // Admins can view all stats
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisors can view their own team stats
        if ($user->hasRole('supervisor')) {
            return $user->id === $supervisor->id;
        }

        return false;
    }

    /**
     * Determine if user can view all teams (admin overview)
     * 
     * @param User $user
     * @return bool
     */
    public function viewAllTeams(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can reassign technicians between supervisors
     * 
     * @param User $user
     * @return bool
     */
    public function reassignTechnicians(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can make technicians independent
     * 
     * @param User $user
     * @return bool
     */
    public function makeIndependent(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can export team data
     * 
     * @param User $user
     * @return bool
     */
    public function exportTeamData(User $user): bool
    {
        // Both admins and supervisors can export
        return $user->hasAnyRole(['admin', 'supervisor']);
    }
}
