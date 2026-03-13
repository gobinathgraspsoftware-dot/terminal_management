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

    public function assign(User $user, Ticket $ticket): bool
    {
        if (!$user->can('assign_tickets')) return false;
        if ($user->hasRole('admin')) return true;

        if ($user->hasRole('supervisor')) {
            return $ticket->supervisor_id === $user->id || $ticket->created_by === $user->id;
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

    public function updateClaim(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('admin')) return true;
        if ($user->hasRole('supervisor')) {
            return $ticket->supervisor_id === $user->id;
        }
        return $ticket->technician_id === $user->id;
    }
}
