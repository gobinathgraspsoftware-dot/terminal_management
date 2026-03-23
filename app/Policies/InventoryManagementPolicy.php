<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Replacement;
use App\Models\AccessoryUsage;

class InventoryManagementPolicy
{
    /**
     * View inventory management hub.
     */
    public function viewHub(User $user): bool
    {
        return $user->hasPermissionTo('view_inventory_management');
    }

    // ── Stock In ──

    public function viewStockIn(User $user): bool
    {
        return $user->hasPermissionTo('view_stock_in');
    }

    public function createStockIn(User $user): bool
    {
        return $user->hasPermissionTo('create_stock_in');
    }

    public function postStockIn(User $user): bool
    {
        return $user->hasPermissionTo('post_stock_in');
    }

    public function cancelStockIn(User $user): bool
    {
        return $user->hasPermissionTo('cancel_stock_in');
    }

    // ── Stock Out ──

    public function viewStockOut(User $user): bool
    {
        return $user->hasPermissionTo('view_stock_out');
    }

    public function createStockOut(User $user): bool
    {
        return $user->hasPermissionTo('create_stock_out');
    }

    public function postStockOut(User $user): bool
    {
        return $user->hasPermissionTo('post_stock_out');
    }

    public function cancelStockOut(User $user): bool
    {
        return $user->hasPermissionTo('cancel_stock_out');
    }

    // ── Replacements ──

    public function viewReplacements(User $user): bool
    {
        return $user->hasPermissionTo('view_replacements');
    }

    public function createReplacement(User $user): bool
    {
        return $user->hasPermissionTo('create_replacements');
    }

    public function completeReplacement(User $user): bool
    {
        return $user->hasPermissionTo('complete_replacements');
    }

    public function cancelReplacement(User $user): bool
    {
        return $user->hasPermissionTo('cancel_replacements');
    }

    // ── Accessory Usage ──

    public function viewAccessoryUsage(User $user): bool
    {
        return $user->hasPermissionTo('view_accessory_usage');
    }

    public function createAccessoryUsage(User $user): bool
    {
        return $user->hasPermissionTo('create_accessory_usage');
    }

    public function returnAccessory(User $user): bool
    {
        return $user->hasPermissionTo('return_accessory');
    }

    public function exportAccessoryUsage(User $user): bool
    {
        return $user->hasPermissionTo('export_accessory_usage');
    }
}
