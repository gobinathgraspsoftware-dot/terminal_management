<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

/**
 * InventoryItemPolicy — Admin-Only Access
 *
 * CHANGE: All policy methods now restrict access to admin role only.
 * Supervisor and Technician roles no longer have any inventory access.
 */
class InventoryItemPolicy
{
    /**
     * View inventory items list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * View a single inventory item.
     */
    public function view(User $user, InventoryItem $item): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Create an inventory item.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Update an inventory item.
     */
    public function update(User $user, InventoryItem $item): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Delete an inventory item.
     */
    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Export inventory data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Perform stock in.
     */
    public function stockIn(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Perform stock out.
     */
    public function stockOut(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Perform stock return.
     */
    public function stockReturn(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Perform stock adjustment.
     */
    public function stockAdjustment(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * View stock movements.
     */
    public function viewMovements(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
