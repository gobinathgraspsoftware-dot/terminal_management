<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockAdjustment;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockAdjustmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any stock adjustments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_stock_adjustments');
    }

    /**
     * Determine whether the user can view the stock adjustment.
     */
    public function view(User $user, StockAdjustment $stockAdjustment): bool
    {
        if (!$user->hasPermissionTo('view_stock_adjustments')) {
            return false;
        }

        $role = $user->roles->first()?->name;

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'supervisor') {
            return $stockAdjustment->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create stock adjustments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_stock_adjustments');
    }

    /**
     * Determine whether the user can update the stock adjustment.
     */
    public function update(User $user, StockAdjustment $stockAdjustment): bool
    {
        if (!$user->hasPermissionTo('create_stock_adjustments')) {
            return false;
        }

        if ($stockAdjustment->status !== StockAdjustment::STATUS_DRAFT) {
            return false;
        }

        $role = $user->roles->first()?->name;

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'supervisor') {
            return $stockAdjustment->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the stock adjustment.
     */
    public function delete(User $user, StockAdjustment $stockAdjustment): bool
    {
        if ($stockAdjustment->status !== StockAdjustment::STATUS_DRAFT) {
            return false;
        }

        $role = $user->roles->first()?->name;

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'supervisor' && $user->hasPermissionTo('create_stock_adjustments')) {
            return $stockAdjustment->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can approve stock adjustments.
     */
    public function approve(User $user, StockAdjustment $stockAdjustment): bool
    {
        if (!$user->hasPermissionTo('approve_stock_adjustments')) {
            return false;
        }

        if ($stockAdjustment->status !== StockAdjustment::STATUS_PENDING_APPROVAL) {
            return false;
        }

        if ($stockAdjustment->created_by === $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can reject stock adjustments.
     */
    public function reject(User $user, StockAdjustment $stockAdjustment): bool
    {
        if (!$user->hasPermissionTo('reject_stock_adjustments')) {
            return false;
        }

        if ($stockAdjustment->status !== StockAdjustment::STATUS_PENDING_APPROVAL) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can post stock adjustments.
     */
    public function post(User $user, StockAdjustment $stockAdjustment): bool
    {
        if (!$user->hasPermissionTo('post_stock_adjustments')) {
            return false;
        }

        if ($stockAdjustment->status !== StockAdjustment::STATUS_APPROVED) {
            return false;
        }

        return true;
    }
}
