<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockBalance;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockBalancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any stock balances
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_stock_balance');
    }

    /**
     * Determine if user can view specific stock balance
     */
    public function view(User $user, StockBalance $balance): bool
    {
        if (!$user->can('view_stock_balance')) {
            return false;
        }

        // Admins can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisors can view their team's balances
        if ($user->hasRole('supervisor')) {
            return $this->isInSupervisorScope($user, $balance);
        }

        // Technicians can view their own balances
        if ($user->hasRole('technician')) {
            return $balance->location_type === 'technician' && $balance->location_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can recalculate balances
     */
    public function recalculate(User $user): bool
    {
        // Only admins can recalculate balances (data integrity operation)
        return $user->can('recalculate_stock_balance') && $user->hasRole('admin');
    }

    /**
     * Determine if user can reserve stock
     */
    public function reserve(User $user): bool
    {
        return $user->can('reserve_stock');
    }

    /**
     * Determine if user can release reservations
     */
    public function releaseReservation(User $user): bool
    {
        return $user->can('release_reservation');
    }

    /**
     * Determine if user can view low stock alerts
     */
    public function viewAlerts(User $user): bool
    {
        return $user->can('view_stock_alerts');
    }

    /**
     * Determine if user can export stock balance
     */
    public function export(User $user): bool
    {
        return $user->can('export_stock_balance');
    }

    /**
     * Check if balance is in supervisor's scope
     */
    protected function isInSupervisorScope(User $supervisor, StockBalance $balance): bool
    {
        // Get supervisor's team technicians
        $teamTechIds = User::where('supervisor_id', $supervisor->id)->pluck('id')->toArray();

        // Check if balance belongs to supervisor's technician
        if ($balance->location_type === 'technician' && in_array($balance->location_id, $teamTechIds)) {
            return true;
        }

        return false;
    }
}
