<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorType;

class VendorTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_vendor_types');
    }

    public function view(User $user, VendorType $vendorType): bool
    {
        return $user->can('view_vendor_types');
    }

    public function create(User $user): bool
    {
        return $user->can('create_vendor_types');
    }

    public function update(User $user, VendorType $vendorType): bool
    {
        return $user->can('edit_vendor_types');
    }

    public function delete(User $user, VendorType $vendorType): bool
    {
        return $user->can('delete_vendor_types');
    }
}
