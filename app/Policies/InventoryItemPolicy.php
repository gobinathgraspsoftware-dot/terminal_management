<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

/**
 * InventoryItemPolicy
 *
 * IMPORTANT: Named InventoryItemPolicy (not InventoryPolicy) to match
 * Laravel's auto-discovery convention: {ModelName}Policy.
 * Also explicitly registered in AuthServiceProvider::$policies.
 */
class InventoryItemPolicy
{
    /**
     * View any inventory items.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasPermissionTo('view_inventory') || $user->hasPermissionTo('view_own_inventory');
    }

    /**
     * View a specific inventory item.
     */
    public function view(User $user, InventoryItem $item): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasPermissionTo('view_inventory') || $user->hasPermissionTo('view_own_inventory');
    }

    /**
     * Create inventory items.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasPermissionTo('create_inventory');
    }

    /**
     * Update inventory items.
     */
    public function update(User $user, InventoryItem $item): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasPermissionTo('edit_inventory');
    }

    /**
     * Delete inventory items.
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
     * Create stock transfer.
     */
    public function stockTransfer(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasPermissionTo('create_stock_transfer');
    }

    /**
     * Approve stock transfer.
     */
    public function approveTransfer(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasPermissionTo('approve_stock_transfer');
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
