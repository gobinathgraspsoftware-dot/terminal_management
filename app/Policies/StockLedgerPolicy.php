<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockLedger;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockLedgerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any stock ledger entries
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_stock_ledger');
    }

    /**
     * Determine if user can view specific stock ledger entry
     */
    public function view(User $user, StockLedger $ledger): bool
    {
        if (!$user->can('view_stock_ledger')) {
            return false;
        }

        // Admins can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisors can view their team's transactions
        if ($user->hasRole('supervisor')) {
            // Check if the transaction involves the supervisor's team members or depots
            return $this->isInSupervisorScope($user, $ledger);
        }

        // Technicians can view their own transactions
        if ($user->hasRole('technician')) {
            return $this->isTechnicianTransaction($user, $ledger);
        }

        return false;
    }

    /**
     * Determine if user can create stock ledger entries
     */
    public function create(User $user): bool
    {
        // Direct creation is typically done through services
        // But we allow admins to manually create entries if needed
        return $user->can('create_stock_ledger') && $user->hasRole('admin');
    }

    /**
     * Determine if user can reverse stock movements
     */
    public function reverse(User $user, StockLedger $ledger): bool
    {
        if (!$user->can('reverse_stock_ledger')) {
            return false;
        }

        // Can't reverse if already reversed
        if ($ledger->is_reversed || $ledger->reversal_of_id) {
            return false;
        }

        // Can't reverse if not reversible type
        if (!$ledger->is_reversible) {
            return false;
        }

        // Admins can reverse any
        if ($user->hasRole('admin')) {
            return true;
        }

        // Supervisors can reverse their team's transactions
        if ($user->hasRole('supervisor')) {
            return $this->isInSupervisorScope($user, $ledger);
        }

        return false;
    }

    /**
     * Determine if user can export stock ledger
     */
    public function export(User $user): bool
    {
        return $user->can('export_stock_ledger');
    }

    /**
     * Check if transaction is in supervisor's scope
     */
    protected function isInSupervisorScope(User $supervisor, StockLedger $ledger): bool
    {
        // Get supervisor's team technicians
        $teamTechIds = User::where('supervisor_id', $supervisor->id)->pluck('id')->toArray();

        // Check if transaction involves supervisor's technicians
        if ($ledger->from_location_type === 'technician' && in_array($ledger->from_location_id, $teamTechIds)) {
            return true;
        }

        if ($ledger->to_location_type === 'technician' && in_array($ledger->to_location_id, $teamTechIds)) {
            return true;
        }

        // Check if created by supervisor or their team
        if ($ledger->created_by === $supervisor->id || in_array($ledger->created_by, $teamTechIds)) {
            return true;
        }

        return false;
    }

    /**
     * Check if transaction belongs to technician
     */
    protected function isTechnicianTransaction(User $technician, StockLedger $ledger): bool
    {
        // Check if transaction involves the technician
        if ($ledger->from_location_type === 'technician' && $ledger->from_location_id === $technician->id) {
            return true;
        }

        if ($ledger->to_location_type === 'technician' && $ledger->to_location_id === $technician->id) {
            return true;
        }

        // Check if created by technician
        if ($ledger->created_by === $technician->id) {
            return true;
        }

        return false;
    }
}
