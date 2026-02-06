<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockIssue;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockIssuePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any stock issues.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_stock_issues');
    }

    /**
     * Determine whether the user can view the stock issue.
     */
    public function view(User $user, StockIssue $stockIssue): bool
    {
        if (!$user->hasPermissionTo('view_stock_issues')) {
            return false;
        }

        $role = $user->roles->first()?->name;

        // Admin can view all
        if ($role === 'admin') {
            return true;
        }

        // Supervisor can view issues from their team's depots or to their team technicians
        if ($role === 'supervisor') {
            $teamTechnicianIds = $user->teamMembers()->pluck('id');
            
            return $stockIssue->to_technician_id && $teamTechnicianIds->contains($stockIssue->to_technician_id)
                || $stockIssue->from_technician_id && $teamTechnicianIds->contains($stockIssue->from_technician_id);
        }

        // Technician can only view their own issues
        if ($role === 'technician') {
            return $stockIssue->to_technician_id === $user->id 
                || $stockIssue->from_technician_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create stock issues.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_stock_issues');
    }

    /**
     * Determine whether the user can update the stock issue.
     */
    public function update(User $user, StockIssue $stockIssue): bool
    {
        if (!$user->hasPermissionTo('edit_stock_issues')) {
            return false;
        }

        // Can only edit draft issues
        if ($stockIssue->status !== StockIssue::STATUS_DRAFT) {
            return false;
        }

        $role = $user->roles->first()?->name;

        // Admin can edit all drafts
        if ($role === 'admin') {
            return true;
        }

        // Supervisor can edit drafts from their team
        if ($role === 'supervisor') {
            $teamTechnicianIds = $user->teamMembers()->pluck('id');
            
            return $stockIssue->to_technician_id && $teamTechnicianIds->contains($stockIssue->to_technician_id)
                || $stockIssue->from_technician_id && $teamTechnicianIds->contains($stockIssue->from_technician_id);
        }

        return false;
    }

    /**
     * Determine whether the user can post the stock issue.
     */
    public function post(User $user, StockIssue $stockIssue): bool
    {
        if (!$user->hasPermissionTo('post_stock_issues')) {
            return false;
        }

        // Can only post draft issues
        return $stockIssue->status === StockIssue::STATUS_DRAFT;
    }

    /**
     * Determine whether the user can cancel the stock issue.
     */
    public function cancel(User $user, StockIssue $stockIssue): bool
    {
        if (!$user->hasPermissionTo('cancel_stock_issues')) {
            return false;
        }

        // Can only cancel posted issues (not already cancelled)
        return $stockIssue->status === StockIssue::STATUS_POSTED;
    }

    /**
     * Determine whether the user can delete the stock issue.
     */
    public function delete(User $user, StockIssue $stockIssue): bool
    {
        // Only draft issues can be deleted, and only by admin
        return $user->roles->first()?->name === 'admin' 
            && $stockIssue->status === StockIssue::STATUS_DRAFT;
    }
}
