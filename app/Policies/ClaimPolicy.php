<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['view_claims', 'view_all_claims', 'view_team_claims', 'view_own_claims']);
    }

    public function view(User $user, Claim $claim): bool
    {
        if (!$user->canAny(['view_claims', 'view_all_claims', 'view_team_claims', 'view_own_claims'])) return false;
        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('supervisor')) {
            $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamIds[] = $user->id;
            return in_array($claim->technician_id, $teamIds)
                || $claim->submitted_by === $user->id
                || $claim->created_by === $user->id;
        }

        return $claim->technician_id === $user->id
            || $claim->submitted_by === $user->id
            || $claim->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_claims');
    }

    public function update(User $user, Claim $claim): bool
    {
        if (!$user->can('edit_claims')) return false;
        if ($user->hasRole('admin')) return true;

        return $claim->technician_id === $user->id
            || $claim->submitted_by === $user->id;
    }

    public function verify(User $user, Claim $claim): bool
    {
        if ($user->hasRole('admin')) return true;
        return $user->canAny(['verify_claims', 'approve_claims']);
    }

    public function bulkPay(User $user): bool
    {
        if ($user->hasRole('admin')) return true;
        return $user->canAny(['bulk_pay_claims', 'mark_paid_claims']);
    }

    public function export(User $user): bool
    {
        if ($user->hasRole('admin')) return true;
        return $user->can('export_claims');
    }
}
