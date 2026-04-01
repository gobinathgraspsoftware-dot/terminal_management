<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_tickets');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if (!$user->can('view_tickets')) return false;
        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('supervisor')) {
            $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            return $ticket->supervisor_id === $user->id
                || in_array($ticket->technician_id, $teamIds)
                || $ticket->created_by === $user->id;
        }

        return $ticket->technician_id === $user->id || $ticket->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_tickets');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if (!$user->can('edit_tickets')) return false;
        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('supervisor')) {
            $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            return $ticket->supervisor_id === $user->id
                || in_array($ticket->technician_id, $teamIds)
                || $ticket->created_by === $user->id;
        }

        return false;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('delete_tickets') && $user->hasRole('admin');
    }

    /**
     * Assign a technician to a ticket.
     * Only admin and INTERNAL supervisors can assign tickets.
     * External supervisors cannot assign technicians (they work alone).
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        if (!$user->can('assign_tickets')) return false;

        // Block assignment on completed/closed tickets
        if (in_array($ticket->status, [
            Ticket::STATUS_DONE_SUCCESS,
            Ticket::STATUS_DONE_FAIL,
            Ticket::STATUS_CLOSED,
        ])) {
            return false;
        }

        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('supervisor')) {
            if ($user->isExternalSupervisor()) return false;
            return $ticket->supervisor_id === $user->id || $ticket->created_by === $user->id;
        }

        return false;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * Reassign technician.
     * CHANGE #2: Now allowed for both admin AND internal supervisors.
     * Same rules as assign — internal supervisors can reassign
     * technicians within their own team scope.
     * ═══════════════════════════════════════════════════════════════════
     */
    public function reassign(User $user, Ticket $ticket): bool
    {
        return $this->assign($user, $ticket);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #2 (NEW): Reassign supervisor on a ticket.
     * - Only admin can reassign a ticket to a different supervisor.
     * - Blocked once the ticket reaches 'accepted' status or beyond.
     *   Once a technician or external supervisor has accepted, the
     *   supervisor assignment is locked.
     * ═══════════════════════════════════════════════════════════════════
     */
    public function reassignSupervisor(User $user, Ticket $ticket): bool
    {
        if (!$user->hasRole('admin')) return false;
        if (!$user->can('assign_tickets')) return false;

        // Only allow when ticket is in open, assigned, or rejected
        return $ticket->canReassignSupervisor();
    }

    /**
     * Accept an assigned ticket.
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #1: Accept ONLY for technician and external supervisor.
     * Admin and internal supervisor CANNOT accept tickets.
     * ═══════════════════════════════════════════════════════════════════
     */
    public function accept(User $user, Ticket $ticket): bool
    {
        if ($ticket->status !== Ticket::STATUS_ASSIGNED) return false;

        if ($user->hasRole('technician')) {
            return $ticket->technician_id === $user->id;
        }

        if ($user->hasRole('supervisor') && $user->isExternalSupervisor()) {
            return $ticket->supervisor_id === $user->id && !$ticket->technician_id;
        }

        // Admin and internal supervisor: NOT allowed
        return false;
    }

    /**
     * Reject an assigned ticket.
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #1: Reject ONLY for technician and external supervisor.
     * Admin and internal supervisor CANNOT reject tickets.
     * ═══════════════════════════════════════════════════════════════════
     */
    public function reject(User $user, Ticket $ticket): bool
    {
        if ($ticket->status !== Ticket::STATUS_ASSIGNED) return false;

        if ($user->hasRole('technician')) {
            return $ticket->technician_id === $user->id;
        }

        if ($user->hasRole('supervisor') && $user->isExternalSupervisor()) {
            return $ticket->supervisor_id === $user->id && !$ticket->technician_id;
        }

        // Admin and internal supervisor: NOT allowed
        return false;
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('supervisor')) {
            $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            return $ticket->supervisor_id === $user->id
                || in_array($ticket->technician_id, $teamIds);
        }

        return $ticket->technician_id === $user->id;
    }

    public function addComment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #3 (NEW): Update Old Router ID.
     * Only allowed when ticket status is 'in_progress'.
     * The old router ID can only be accurately verified after
     * the technician or supervisor physically visits the site.
     * ═══════════════════════════════════════════════════════════════════
     */
    public function updateOldRouterId(User $user, Ticket $ticket): bool
    {
        if (!$this->view($user, $ticket)) return false;

        // Only allow when ticket is In Progress
        return $ticket->canUpdateOldRouterId();
    }

    /**
     * Update claim information.
     *
     * Rules:
     * - BLOCKED for ALL roles once the linked claim is verified/paid/non-claimable.
     * - Admin         → allowed (if claim still editable).
     * - External Supervisor → works alone, can claim directly on their own ticket.
     * - Internal Supervisor → no claims applicable (they manage, not fieldwork).
     * - Technician    → can claim on ANY ticket they are the assigned technician.
     */
    public function updateClaim(User $user, Ticket $ticket): bool
    {
        // Block ALL roles once claim has been verified/approved/paid
        if (!$ticket->isClaimEditable()) return false;

        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('supervisor')) {
            // External supervisor: claims directly on their own ticket
            if ($user->isExternalSupervisor()) {
                return $ticket->supervisor_id === $user->id;
            }
            // Internal supervisor: no claims
            return false;
        }

        // Technician: can claim on any ticket they are assigned to
        return $ticket->technician_id === $user->id;
    }
}
