<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    /**
     * Determine if the user can view any vendors
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_vendors');
    }

    /**
     * Determine if the user can view the vendor
     */
    public function view(User $user, Vendor $vendor): bool
    {
        return $user->can('view_vendors');
    }

    /**
     * Determine if the user can create vendors
     */
    public function create(User $user): bool
    {
        return $user->can('create_vendors');
    }

    /**
     * Determine if the user can update the vendor
     */
    public function update(User $user, Vendor $vendor): bool
    {
        return $user->can('edit_vendors');
    }

    /**
     * Determine if the user can delete the vendor
     */
    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('delete_vendors');
    }

    /**
     * Determine if the user can restore the vendor
     */
    public function restore(User $user, Vendor $vendor): bool
    {
        return $user->can('restore_vendors');
    }

    /**
     * Determine if the user can permanently delete the vendor
     */
    public function forceDelete(User $user, Vendor $vendor): bool
    {
        return $user->can('delete_vendors');
    }

    /**
     * Determine if the user can export vendors
     */
    public function export(User $user): bool
    {
        return $user->can('export_vendors');
    }

    /**
     * Determine if the user can import vendors
     */
    public function import(User $user): bool
    {
        return $user->can('import_vendors');
    }
}
