<?php

namespace App\Policies;

use App\Models\InventorySerial;
use App\Models\User;

class InventorySerialPolicy
{
    /**
     * Determine whether the user can view any inventory serials.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_inventory');
    }

    /**
     * Determine whether the user can view a specific serial.
     */
    public function view(User $user, InventorySerial $serial): bool
    {
        if (!$user->can('view_inventory')) {
            return false;
        }

        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisor can view team serials + all depot stock
        if ($user->hasRole('supervisor')) {
            if ($serial->current_location_type === InventorySerial::LOCATION_TYPE_DEPOT) {
                return true;
            }
            if ($serial->current_location_type === InventorySerial::LOCATION_TYPE_TECHNICIAN) {
                $teamIds = $user->technicians()->pluck('id')->toArray();
                $teamIds[] = $user->id;
                return in_array($serial->current_location_id, $teamIds);
            }
            return true; // Supervisor can view installed/vendor serials for reference
        }

        // Technician can view own stock only
        if ($user->hasRole('technician')) {
            return $serial->current_location_type === InventorySerial::LOCATION_TYPE_TECHNICIAN
                && $serial->current_location_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create inventory serials.
     */
    public function create(User $user): bool
    {
        return $user->can('create_inventory');
    }

    /**
     * Determine whether the user can update the serial.
     */
    public function update(User $user, InventorySerial $serial): bool
    {
        return $user->can('edit_inventory');
    }

    /**
     * Determine whether the user can delete the serial.
     */
    public function delete(User $user, InventorySerial $serial): bool
    {
        if (!$user->can('delete_inventory')) {
            return false;
        }

        // Cannot delete tracked items
        if (in_array($serial->current_status, InventorySerial::TRACKED_STATUSES)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore a soft-deleted serial.
     */
    public function restore(User $user, InventorySerial $serial): bool
    {
        return $user->hasRole('admin') && $user->can('delete_inventory');
    }

    /**
     * Determine whether the user can view serial history.
     */
    public function viewHistory(User $user, InventorySerial $serial): bool
    {
        return $user->can('view_history_inventory');
    }

    /**
     * Determine whether the user can export inventory data.
     */
    public function export(User $user): bool
    {
        return $user->can('export_inventory');
    }

    /**
     * Determine whether the user can perform serial lookup.
     */
    public function lookup(User $user): bool
    {
        return $user->can('view_inventory');
    }
}
