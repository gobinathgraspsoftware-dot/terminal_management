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
        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('supervisor')) {
            if ($user->isExternalSupervisor()) return false;
            return $ticket->supervisor_id === $user->id || $ticket->created_by === $user->id;
        }

        return false;
    }

    /**
     * Reassign technician — same rules as assign.
     */
    public function reassign(User $user, Ticket $ticket): bool
    {
        return $this->assign($user, $ticket);
    }

    /**
     * Accept an assigned ticket.
     * - Technicians accept tickets assigned to them.
     * - External supervisors accept tickets assigned to them (no technician).
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

        return false;
    }

    /**
     * Reject an assigned ticket.
     * - Technicians reject tickets assigned to them.
     * - External supervisors reject tickets assigned to them.
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
     * Update claim information.
     *
     * Rules:
     * - Admin         → always allowed.
     * - External Supervisor → works alone (no technician under them).
     *                         Can claim directly on their own ticket.
     * - Internal Supervisor → no claims applicable (they manage, not fieldwork).
     * - Technician    → can claim on ANY ticket they are the assigned technician,
     *                   regardless of whether the supervisor is internal or external.
     *                   (Previously blocked on internal-supervisor tickets — this was wrong.)
     */
    public function updateClaim(User $user, Ticket $ticket): bool
    {
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
