<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_purchase_orders');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return $purchaseOrder->created_by === $user->id ||
                   $purchaseOrder->createdBy?->supervisor_id === $user->id;
        }

        return $purchaseOrder->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_purchase_orders');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (!$user->can('edit_purchase_orders')) {
            return false;
        }

        if (!in_array($purchaseOrder->status, ['draft', 'pending_approval'])) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $purchaseOrder->created_by === $user->id;
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (!$user->can('delete_purchase_orders')) {
            return false;
        }

        if ($purchaseOrder->status !== 'draft') {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $purchaseOrder->created_by === $user->id;
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('approve_purchase_orders') &&
               $purchaseOrder->status === 'pending_approval';
    }

    public function reject(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('reject_purchase_orders') &&
               $purchaseOrder->status === 'pending_approval';
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('send_purchase_orders') &&
               $purchaseOrder->status === 'approved';
    }

    public function close(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('close_purchase_orders') &&
               in_array($purchaseOrder->status, ['sent', 'open', 'partially_received']);
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('cancel_purchase_orders') &&
               in_array($purchaseOrder->status, ['draft', 'pending_approval', 'approved']);
    }
}
