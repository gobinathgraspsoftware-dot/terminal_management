<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * TeamPolicy - Authorization for team management.
 *
 * NOTE: No removeFromTeam policy — technicians cannot be made independent.
 *       All technicians must have a supervisor.
 */
class TeamPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function viewOwnTeam(User $user): bool
    {
        return $user->hasRole(['admin', 'supervisor']);
    }

    public function viewTeamMember(User $user, User $member): bool
    {
        if ($user->hasRole('admin')) return true;
        if ($user->hasRole('supervisor')) return $member->supervisor_id === $user->id;
        return $user->id === $member->id;
    }

    public function assignTechnician(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function bulkAssign(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function exportTeam(User $user): bool
    {
        return $user->hasRole(['admin', 'supervisor']);
    }

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin') && $user->can('manage_teams')) return true;
        return null;
    }
}
