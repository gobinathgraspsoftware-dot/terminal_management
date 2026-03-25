<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    /**
     * View inventory items list.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasAnyPermission(['view_inventory', 'view_own_inventory']);
    }

    /**
     * View a single inventory item.
     */
    public function view(User $user, InventoryItem $item): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('view_inventory');
    }

    /**
     * Create an inventory item.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('create_inventory');
    }

    /**
     * Update an inventory item.
     */
    public function update(User $user, InventoryItem $item): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('edit_inventory');
    }

    /**
     * Delete an inventory item.
     */
    public function delete(User $user, InventoryItem $item): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('delete_inventory');
    }

    /**
     * Export inventory data.
     */
    public function export(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('export_inventory');
    }

    /**
     * Perform stock in.
     */
    public function stockIn(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('create_stock_in');
    }

    /**
     * Perform stock out.
     */
    public function stockOut(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('create_stock_out');
    }

    /**
     * Perform stock return.
     */
    public function stockReturn(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('create_stock_return');
    }

    /**
     * Perform stock adjustment.
     */
    public function stockAdjustment(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('create_stock_adjustment');
    }

    /**
     * View stock movements.
     */
    public function viewMovements(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasPermissionTo('view_stock_movements');
    }
}
