<?php

namespace App\Services\Inventory;

use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\TerminalModel;
use App\Models\InventorySerial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StockLedgerService
{
    protected StockBalanceService $balanceService;

    public function __construct(StockBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Main entry point for recording movements
     */
    public function recordMovement(array $data): StockLedger
    {
        DB::beginTransaction();
        try {
            // Validate movement data
            $this->validateMovementData($data);

            // Create ledger entry
            $ledger = StockLedger::create([
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'transaction_no' => $data['transaction_no'] ?? $this->generateTransactionNo($data['transaction_type']),
                'transaction_type' => $data['transaction_type'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'serial_id' => $data['serial_id'] ?? null,
                'serial_no' => $data['serial_no'] ?? null,
                'model_id' => $data['model_id'],
                'quantity' => $data['quantity'],
                'from_location_type' => $data['from_location_type'] ?? null,
                'from_location_id' => $data['from_location_id'] ?? null,
                'to_location_type' => $data['to_location_type'] ?? null,
                'to_location_id' => $data['to_location_id'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
                'total_cost' => $data['total_cost'] ?? ($data['unit_cost'] ?? 0) * $data['quantity'],
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            // Update stock balances
            $this->updateBalancesAfterMovement($ledger);

            DB::commit();
            
            Log::info('Stock movement recorded', [
                'ledger_id' => $ledger->id,
                'type' => $ledger->transaction_type,
                'model_id' => $ledger->model_id,
                'quantity' => $ledger->quantity
            ]);

            return $ledger;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stock movement failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Record GRN (Goods Receipt Note) - Stock IN
     */
    public function recordGRN(array $data): StockLedger
    {
        return $this->recordMovement([
            'transaction_type' => StockLedger::TYPE_GRN_IN,
            'transaction_date' => $data['grn_date'] ?? now()->toDateString(),
            'reference_type' => 'grn',
            'reference_id' => $data['grn_id'] ?? null,
            'serial_id' => $data['serial_id'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'model_id' => $data['model_id'],
            'quantity' => abs($data['quantity']), // Always positive for IN
            'from_location_type' => 'vendor',
            'from_location_id' => $data['vendor_id'] ?? null,
            'to_location_type' => 'depot',
            'to_location_id' => $data['depot_id'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'total_cost' => $data['total_cost'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Record Stock Issue to Technician
     */
    public function recordIssue(array $data): StockLedger
    {
        return $this->recordMovement([
            'transaction_type' => StockLedger::TYPE_ISSUE_TO_TECH,
            'transaction_date' => $data['issue_date'] ?? now()->toDateString(),
            'reference_type' => 'stock_issue',
            'reference_id' => $data['issue_id'] ?? null,
            'serial_id' => $data['serial_id'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'model_id' => $data['model_id'],
            'quantity' => abs($data['quantity']),
            'from_location_type' => 'depot',
            'from_location_id' => $data['from_depot_id'],
            'to_location_type' => 'technician',
            'to_location_id' => $data['technician_id'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Record Return from Technician to Depot
     */
    public function recordReturn(array $data): StockLedger
    {
        return $this->recordMovement([
            'transaction_type' => StockLedger::TYPE_RETURN_FROM_TECH,
            'transaction_date' => $data['return_date'] ?? now()->toDateString(),
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'serial_id' => $data['serial_id'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'model_id' => $data['model_id'],
            'quantity' => abs($data['quantity']),
            'from_location_type' => 'technician',
            'from_location_id' => $data['technician_id'],
            'to_location_type' => 'depot',
            'to_location_id' => $data['to_depot_id'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Record Stock Transfer (Depot to Depot or Tech to Tech)
     */
    public function recordTransfer(array $data): StockLedger
    {
        return $this->recordMovement([
            'transaction_type' => StockLedger::TYPE_TRANSFER,
            'transaction_date' => $data['transfer_date'] ?? now()->toDateString(),
            'reference_type' => 'stock_transfer',
            'reference_id' => $data['transfer_id'] ?? null,
            'serial_id' => $data['serial_id'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'model_id' => $data['model_id'],
            'quantity' => abs($data['quantity']),
            'from_location_type' => $data['from_location_type'],
            'from_location_id' => $data['from_location_id'],
            'to_location_type' => $data['to_location_type'],
            'to_location_id' => $data['to_location_id'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Record Installation to Site
     */
    public function recordInstallation(array $data): StockLedger
    {
        return $this->recordMovement([
            'transaction_type' => StockLedger::TYPE_INSTALL,
            'transaction_date' => $data['installation_date'] ?? now()->toDateString(),
            'reference_type' => 'job',
            'reference_id' => $data['job_id'] ?? null,
            'serial_id' => $data['serial_id'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'model_id' => $data['model_id'],
            'quantity' => abs($data['quantity']),
            'from_location_type' => $data['from_location_type'] ?? 'technician',
            'from_location_id' => $data['from_location_id'] ?? $data['technician_id'] ?? null,
            'to_location_type' => 'site',
            'to_location_id' => $data['site_id'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Record Replacement OUT (Uninstall from site)
     */
    public function recordReplacement(array $data): StockLedger
    {
        // OUT movement - uninstall
        $replacementOut = $this->recordMovement([
            'transaction_type' => StockLedger::TYPE_REPLACEMENT_OUT,
            'transaction_date' => $data['replacement_date'] ?? now()->toDateString(),
            'reference_type' => 'job',
            'reference_id' => $data['job_id'] ?? null,
            'serial_id' => $data['old_serial_id'] ?? null,
            'serial_no' => $data['old_serial_no'] ?? null,
            'model_id' => $data['old_model_id'] ?? $data['model_id'],
            'quantity' => abs($data['quantity'] ?? 1),
            'from_location_type' => 'site',
            'from_location_id' => $data['site_id'],
            'to_location_type' => $data['to_location_type'] ?? 'technician',
            'to_location_id' => $data['to_location_id'] ?? $data['technician_id'] ?? null,
            'unit_cost' => $data['old_unit_cost'] ?? null,
            'remarks' => $data['remarks'] ?? 'Replacement OUT',
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);

        // IN movement - install new
        if (isset($data['new_serial_id']) || isset($data['new_serial_no'])) {
            $this->recordMovement([
                'transaction_type' => StockLedger::TYPE_REPLACEMENT_IN,
                'transaction_date' => $data['replacement_date'] ?? now()->toDateString(),
                'reference_type' => 'job',
                'reference_id' => $data['job_id'] ?? null,
                'serial_id' => $data['new_serial_id'] ?? null,
                'serial_no' => $data['new_serial_no'] ?? null,
                'model_id' => $data['new_model_id'] ?? $data['model_id'],
                'quantity' => abs($data['quantity'] ?? 1),
                'from_location_type' => $data['from_location_type'] ?? 'technician',
                'from_location_id' => $data['from_location_id'] ?? $data['technician_id'] ?? null,
                'to_location_type' => 'site',
                'to_location_id' => $data['site_id'],
                'unit_cost' => $data['new_unit_cost'] ?? null,
                'remarks' => $data['remarks'] ?? 'Replacement IN',
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);
        }

        return $replacementOut;
    }

    /**
     * Record Wastage/Scrap
     */
    public function recordWastage(array $data): StockLedger
    {
        return $this->recordMovement([
            'transaction_type' => StockLedger::TYPE_WASTAGE,
            'transaction_date' => $data['wastage_date'] ?? now()->toDateString(),
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'serial_id' => $data['serial_id'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'model_id' => $data['model_id'],
            'quantity' => abs($data['quantity']),
            'from_location_type' => $data['from_location_type'],
            'from_location_id' => $data['from_location_id'],
            'to_location_type' => null,
            'to_location_id' => null,
            'unit_cost' => $data['unit_cost'] ?? null,
            'remarks' => $data['remarks'] ?? 'Wastage/Scrap',
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Record Return to Vendor
     */
    public function recordReturnToVendor(array $data): StockLedger
    {
        return $this->recordMovement([
            'transaction_type' => StockLedger::TYPE_RETURN_TO_VENDOR,
            'transaction_date' => $data['return_date'] ?? now()->toDateString(),
            'reference_type' => $data['reference_type'] ?? 'return',
            'reference_id' => $data['reference_id'] ?? null,
            'serial_id' => $data['serial_id'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'model_id' => $data['model_id'],
            'quantity' => abs($data['quantity']),
            'from_location_type' => 'depot',
            'from_location_id' => $data['depot_id'],
            'to_location_type' => 'vendor',
            'to_location_id' => $data['vendor_id'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Reverse a stock movement
     */
    public function reverseMovement(int $ledgerId, ?string $reason = null): StockLedger
    {
        DB::beginTransaction();
        try {
            $originalLedger = StockLedger::findOrFail($ledgerId);

            // Validate reversal
            if ($originalLedger->is_reversed) {
                throw new Exception('This movement has already been reversed');
            }

            if ($originalLedger->reversal_of_id) {
                throw new Exception('Cannot reverse a reversal entry');
            }

            if (!$originalLedger->is_reversible) {
                throw new Exception('This movement type cannot be reversed');
            }

            // Create reversal entry
            $reversalType = StockLedger::REVERSIBLE_TYPES[$originalLedger->transaction_type] ?? null;
            
            if (!$reversalType) {
                throw new Exception('No reversal type defined for this movement');
            }

            $reversalLedger = StockLedger::create([
                'transaction_date' => now()->toDateString(),
                'transaction_no' => $this->generateTransactionNo('reversal'),
                'transaction_type' => $reversalType,
                'reference_type' => $originalLedger->reference_type,
                'reference_id' => $originalLedger->reference_id,
                'serial_id' => $originalLedger->serial_id,
                'serial_no' => $originalLedger->serial_no,
                'model_id' => $originalLedger->model_id,
                'quantity' => $originalLedger->quantity,
                'from_location_type' => $originalLedger->to_location_type,
                'from_location_id' => $originalLedger->to_location_id,
                'to_location_type' => $originalLedger->from_location_type,
                'to_location_id' => $originalLedger->from_location_id,
                'unit_cost' => $originalLedger->unit_cost,
                'total_cost' => $originalLedger->total_cost,
                'remarks' => 'REVERSAL: ' . ($reason ?? 'Movement reversed'),
                'reversal_of_id' => $originalLedger->id,
                'created_by' => auth()->id(),
            ]);

            // Mark original as reversed
            $originalLedger->update([
                'is_reversed' => true,
                'reversed_by_id' => $reversalLedger->id,
                'reversed_at' => now(),
                'reversed_by_user_id' => auth()->id(),
            ]);

            // Update balances
            $this->updateBalancesAfterMovement($reversalLedger);

            DB::commit();

            Log::info('Stock movement reversed', [
                'original_id' => $originalLedger->id,
                'reversal_id' => $reversalLedger->id,
            ]);

            return $reversalLedger;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Movement reversal failed', [
                'ledger_id' => $ledgerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update stock balances after movement
     */
    protected function updateBalancesAfterMovement(StockLedger $ledger): void
    {
        // Update FROM location (decrease)
        if ($ledger->from_location_type && $ledger->from_location_id) {
            $this->balanceService->updateBalance(
                $ledger->model_id,
                $ledger->from_location_type,
                $ledger->from_location_id,
                -abs($ledger->quantity),
                $ledger->transaction_date
            );
        }

        // Update TO location (increase)
        if ($ledger->to_location_type && $ledger->to_location_id) {
            $this->balanceService->updateBalance(
                $ledger->model_id,
                $ledger->to_location_type,
                $ledger->to_location_id,
                abs($ledger->quantity),
                $ledger->transaction_date
            );
        }
    }

    /**
     * Validate movement data
     */
    protected function validateMovementData(array $data): void
    {
        if (!isset($data['model_id'])) {
            throw new Exception('Model ID is required');
        }

        if (!isset($data['quantity']) || $data['quantity'] == 0) {
            throw new Exception('Quantity is required and must not be zero');
        }

        // Verify model exists
        if (!TerminalModel::find($data['model_id'])) {
            throw new Exception('Invalid model ID');
        }
    }

    /**
     * Generate transaction number
     */
    protected function generateTransactionNo(string $type): string
    {
        $prefix = match($type) {
            StockLedger::TYPE_GRN_IN => 'GRN',
            StockLedger::TYPE_ISSUE_TO_TECH => 'ISS',
            StockLedger::TYPE_RETURN_FROM_TECH => 'RTN',
            StockLedger::TYPE_TRANSFER => 'TRF',
            StockLedger::TYPE_INSTALL => 'INS',
            StockLedger::TYPE_REPLACEMENT_OUT => 'RPO',
            StockLedger::TYPE_REPLACEMENT_IN => 'RPI',
            StockLedger::TYPE_WASTAGE => 'WST',
            StockLedger::TYPE_RETURN_TO_VENDOR => 'RTV',
            StockLedger::TYPE_ADJUSTMENT => 'ADJ',
            'reversal' => 'REV',
            default => 'TXN',
        };

        $date = now()->format('Ymd');
        $sequence = StockLedger::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Get running balance for a location
     */
    public function getRunningBalance(int $modelId, string $locationType, int $locationId): float
    {
        return $this->balanceService->getBalance($modelId, $locationType, $locationId);
    }

    /**
     * Get movement history
     */
    public function getMovementHistory(array $filters = [])
    {
        $query = StockLedger::with(['model', 'serial', 'createdBy'])
            ->activeMovements() // Exclude reversed entries
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if (isset($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (isset($filters['serial_id'])) {
            $query->where('serial_id', $filters['serial_id']);
        }

        if (isset($filters['location_type']) && isset($filters['location_id'])) {
            $query->byLocation($filters['location_type'], $filters['location_id']);
        }

        if (isset($filters['from_date'])) {
            $query->where('transaction_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('transaction_date', '<=', $filters['to_date']);
        }

        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        return $query;
    }
}
