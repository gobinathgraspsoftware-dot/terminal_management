<?php

namespace App\Policies;

use App\Models\ChargeCatalog;
use App\Models\User;

/**
 * Charge Catalog Policy
 *
 * Authorization rules for charge catalog operations
 */
class ChargeCatalogPolicy
{
    /**
     * Determine if user can view any charges.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'view_charges',
            'view_quotations',
            'view_invoices',
        ]);
    }

    /**
     * Determine if user can view the charge.
     */
    public function view(User $user, ?ChargeCatalog $charge = null): bool
    {
        return $user->hasAnyPermission([
            'view_charges',
            'view_quotations',
            'view_invoices',
        ]);
    }

    /**
     * Determine if user can create charges.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_charges');
    }

    /**
     * Determine if user can update the charge.
     */
    public function update(User $user, ?ChargeCatalog $charge = null): bool
    {
        return $user->hasPermissionTo('edit_charges');
    }

    /**
     * Determine if user can delete the charge.
     */
    public function delete(User $user, ChargeCatalog $charge): bool
    {
        return $user->hasPermissionTo('delete_charges');
    }

    /**
     * Determine if user can restore the charge.
     */
    public function restore(User $user, ChargeCatalog $charge): bool
    {
        return $user->hasPermissionTo('delete_charges');
    }

    /**
     * Determine if user can permanently delete the charge.
     */
    public function forceDelete(User $user, ChargeCatalog $charge): bool
    {
        return $user->hasRole('admin');
    }
}
