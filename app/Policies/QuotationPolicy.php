<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Quotation;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any quotations.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_quotations');
    }

    /**
     * Determine if the user can view the quotation.
     */
    public function view(User $user, Quotation $quotation): bool
    {
        // Admins can view all quotations
        if ($user->hasRole('admin')) {
            return $user->can('view_quotations');
        }

        // Supervisors can view quotations created by their team
        if ($user->hasRole('supervisor')) {
            // Get supervisor's team member IDs
            $teamMemberIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamMemberIds[] = $user->id; // Include supervisor's own quotations

            return $user->can('view_quotations') 
                && in_array($quotation->created_by, $teamMemberIds);
        }

        // Technicians can only view quotations they created
        if ($user->hasRole('technician')) {
            return $user->can('view_quotations') 
                && $quotation->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can create quotations.
     */
    public function create(User $user): bool
    {
        return $user->can('create_quotations');
    }

    /**
     * Determine if the user can update the quotation.
     */
    public function update(User $user, Quotation $quotation): bool
    {
        // Cannot edit if not in editable status
        if (!$quotation->isEditable()) {
            return false;
        }

        // Admins can edit all quotations
        if ($user->hasRole('admin')) {
            return $user->can('edit_quotations');
        }

        // Supervisors can edit team quotations
        if ($user->hasRole('supervisor')) {
            $teamMemberIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamMemberIds[] = $user->id;

            return $user->can('edit_quotations') 
                && in_array($quotation->created_by, $teamMemberIds);
        }

        // Technicians can edit their own quotations if in draft
        if ($user->hasRole('technician')) {
            return $user->can('edit_quotations') 
                && $quotation->created_by === $user->id
                && $quotation->isDraft();
        }

        return false;
    }

    /**
     * Determine if the user can delete the quotation.
     */
    public function delete(User $user, Quotation $quotation): bool
    {
        // Can only delete draft quotations
        if (!$quotation->isDraft()) {
            return false;
        }

        // Admins can delete all quotations
        if ($user->hasRole('admin')) {
            return $user->can('delete_quotations');
        }

        // Supervisors can delete team quotations
        if ($user->hasRole('supervisor')) {
            $teamMemberIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamMemberIds[] = $user->id;

            return $user->can('delete_quotations') 
                && in_array($quotation->created_by, $teamMemberIds);
        }

        // Technicians cannot delete quotations
        return false;
    }

    /**
     * Determine if the user can restore the quotation.
     */
    public function restore(User $user, Quotation $quotation): bool
    {
        return $user->hasRole('admin') && $user->can('delete_quotations');
    }

    /**
     * Determine if the user can permanently delete the quotation.
     */
    public function forceDelete(User $user, Quotation $quotation): bool
    {
        return $user->hasRole('admin') && $user->can('delete_quotations');
    }

    /**
     * Determine if the user can approve the quotation.
     */
    public function approve(User $user, Quotation $quotation): bool
    {
        // Only pending approval quotations can be approved
        if (!$quotation->canBeApproved()) {
            return false;
        }

        // Only supervisors and admins can approve
        if ($user->hasRole('admin')) {
            return $user->can('approve_quotations');
        }

        if ($user->hasRole('supervisor')) {
            // Supervisors can approve quotations from their team members
            $teamMemberIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            
            return $user->can('approve_quotations')
                && in_array($quotation->created_by, $teamMemberIds)
                && $quotation->created_by !== $user->id; // Cannot approve own quotation
        }

        return false;
    }

    /**
     * Determine if the user can reject the quotation.
     */
    public function reject(User $user, Quotation $quotation): bool
    {
        return $this->approve($user, $quotation);
    }

    /**
     * Determine if the user can send the quotation.
     */
    public function send(User $user, Quotation $quotation): bool
    {
        // Must be approved to send
        if (!$quotation->canBeSent()) {
            return false;
        }

        // Admins can send all quotations
        if ($user->hasRole('admin')) {
            return $user->can('send_quotations');
        }

        // Supervisors can send team quotations
        if ($user->hasRole('supervisor')) {
            $teamMemberIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamMemberIds[] = $user->id;

            return $user->can('send_quotations')
                && in_array($quotation->created_by, $teamMemberIds);
        }

        return false;
    }

    // convertToPO() method REMOVED (Procurement tabs removed)

    /**
     * Determine if the user can export quotations.
     */
    public function export(User $user): bool
    {
        return $user->can('export_quotations');
    }
}
