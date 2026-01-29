<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine if the user can view any clients
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_clients');
    }

    /**
     * Determine if the user can view the client
     */
    public function view(User $user, Client $client): bool
    {
        return $user->can('view_clients');
    }

    /**
     * Determine if the user can create clients
     */
    public function create(User $user): bool
    {
        return $user->can('create_clients');
    }

    /**
     * Determine if the user can update the client
     */
    public function update(User $user, Client $client): bool
    {
        return $user->can('edit_clients');
    }

    /**
     * Determine if the user can delete the client
     */
    public function delete(User $user, Client $client): bool
    {
        // Cannot delete if client has:
        // - Active sites
        // - Active job orders
        // - Unpaid invoices
        
        if ($client->sites()->exists()) {
            return false;
        }

        if ($client->jobOrders()->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
            return false;
        }

        if ($client->invoices()->where('status', '!=', 'paid')->exists()) {
            return false;
        }

        return $user->can('delete_clients');
    }

    /**
     * Determine if the user can restore the client
     */
    public function restore(User $user, Client $client): bool
    {
        return $user->can('delete_clients');
    }

    /**
     * Determine if the user can permanently delete the client
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $user->hasRole('admin') && $user->can('delete_clients');
    }

    /**
     * Determine if the user can export clients
     */
    public function export(User $user): bool
    {
        return $user->can('export_clients');
    }

    /**
     * Determine if the user can import clients
     */
    public function import(User $user): bool
    {
        return $user->can('import_clients');
    }
}
