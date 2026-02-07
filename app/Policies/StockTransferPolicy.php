<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockTransfer;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockTransferPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any stock transfers
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_stock_transfers');
    }

    /**
     * Determine whether the user can view the stock transfer
     */
    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        if (!$user->can('view_stock_transfers')) {
            return false;
        }

        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisor can view transfers related to their team's depots
        if ($user->hasRole('supervisor')) {
            // Add your team depot logic here
            return true;
        }

        // Technician can only view transfers they created
        if ($user->hasRole('technician')) {
            return $stockTransfer->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create stock transfers
     */
    public function create(User $user): bool
    {
        return $user->can('create_stock_transfers');
    }

    /**
     * Determine whether the user can update the stock transfer
     */
    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        if (!$user->can('edit_stock_transfers')) {
            return false;
        }

        // Can only edit draft transfers
        if ($stockTransfer->status !== StockTransfer::STATUS_DRAFT) {
            return false;
        }

        // Admin can edit all drafts
        if ($user->hasRole('admin')) {
            return true;
        }

        // Others can only edit their own drafts
        return $stockTransfer->created_by === $user->id;
    }

    /**
     * Determine whether the user can approve the stock transfer
     */
    public function approve(User $user, StockTransfer $stockTransfer): bool
    {
        if (!$user->can('approve_stock_transfers')) {
            return false;
        }

        // Cannot approve own transfers
        if ($stockTransfer->created_by === $user->id) {
            return false;
        }

        // Must be in pending approval status
        return $stockTransfer->status === StockTransfer::STATUS_PENDING_APPROVAL;
    }

    /**
     * Determine whether the user can dispatch the stock transfer
     */
    public function dispatch(User $user, StockTransfer $stockTransfer): bool
    {
        if (!$user->can('dispatch_stock_transfers')) {
            return false;
        }

        // Must be in approved status
        return $stockTransfer->status === StockTransfer::STATUS_APPROVED;
    }

    /**
     * Determine whether the user can receive the stock transfer
     */
    public function receive(User $user, StockTransfer $stockTransfer): bool
    {
        if (!$user->can('receive_stock_transfers')) {
            return false;
        }

        // Must be in transit or approved status
        return in_array($stockTransfer->status, [
            StockTransfer::STATUS_IN_TRANSIT,
            StockTransfer::STATUS_APPROVED
        ]);
    }

    /**
     * Determine whether the user can cancel the stock transfer
     */
    public function cancel(User $user, StockTransfer $stockTransfer): bool
    {
        if (!$user->can('cancel_stock_transfers')) {
            return false;
        }

        // Cannot cancel received transfers
        if ($stockTransfer->status === StockTransfer::STATUS_RECEIVED) {
            return false;
        }

        // Admin can cancel any
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisor can cancel approved or pending
        if ($user->hasRole('supervisor')) {
            return in_array($stockTransfer->status, [
                StockTransfer::STATUS_DRAFT,
                StockTransfer::STATUS_PENDING_APPROVAL,
                StockTransfer::STATUS_APPROVED
            ]);
        }

        // Creator can cancel their own drafts
        return $stockTransfer->status === StockTransfer::STATUS_DRAFT 
            && $stockTransfer->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the stock transfer
     */
    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        // Only admin can delete, and only drafts
        return $user->hasRole('admin') 
            && $stockTransfer->status === StockTransfer::STATUS_DRAFT;
    }
}
