<?php

namespace App\Services;

use App\Models\StockIssue;
use App\Models\StockIssueLine;
use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\NumberSeries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class StockIssueService
{
    /**
     * Get filtered stock issues for DataTables
     */
    public function getFilteredStockIssues($request, $role, $userId)
    {
        $query = StockIssue::with([
            'fromDepot:id,depot_name',
            'toDepot:id,depot_name',
            'toTechnician:id,name',
            'fromTechnician:id,name',
        ]);

        // Apply role-based filtering
        if ($role === 'supervisor') {
            $teamTechnicianIds = Auth::user()->teamMembers()->pluck('id');
            $query->where(function($q) use ($teamTechnicianIds) {
                $q->whereIn('to_technician_id', $teamTechnicianIds)
                  ->orWhereIn('from_technician_id', $teamTechnicianIds);
            });
        } elseif ($role === 'technician') {
            $query->where(function($q) use ($userId) {
                $q->where('to_technician_id', $userId)
                  ->orWhere('from_technician_id', $userId);
            });
        }

        // Apply filters
        if ($request->filled('issue_type')) {
            $query->where('issue_type', $request->issue_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        if ($request->filled('depot_id')) {
            $query->where(function($q) use ($request) {
                $q->where('from_depot_id', $request->depot_id)
                  ->orWhere('to_depot_id', $request->depot_id);
            });
        }

        if ($request->filled('technician_id')) {
            $query->where(function($q) use ($request) {
                $q->where('to_technician_id', $request->technician_id)
                  ->orWhere('from_technician_id', $request->technician_id);
            });
        }

        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('issue_no', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Generate next issue number
     */
    public function generateIssueNumber(): string
    {
        return DB::transaction(function () {
            $series = NumberSeries::lockForUpdate()
                ->where('series_type', 'stock_issue')
                ->first();

            if (!$series) {
                $series = NumberSeries::create([
                    'series_type' => 'stock_issue',
                    'prefix' => 'SI',
                    'current_number' => 0,
                    'number_length' => 5,
                    'is_active' => 1,
                ]);
            }

            // Increment first
            $series->increment('current_number');

            // Generate issue number
            $issueNo = $series->prefix . str_pad($series->current_number, $series->number_length, '0', STR_PAD_LEFT);

            return $issueNo;
        });
    }

    /**
     * Create stock issue
     */
    public function createStockIssue(array $data): StockIssue
    {
        DB::beginTransaction();
        try {
            // Generate issue number if not provided
            if (empty($data['issue_no'])) {
                $data['issue_no'] = $this->generateIssueNumber();
            }

            // Create header
            $stockIssue = StockIssue::create([
                'issue_no' => $data['issue_no'],
                'issue_date' => $data['issue_date'],
                'issue_type' => $data['issue_type'],
                'from_depot_id' => $data['from_depot_id'] ?? null,
                'to_technician_id' => $data['to_technician_id'] ?? null,
                'from_technician_id' => $data['from_technician_id'] ?? null,
                'to_depot_id' => $data['to_depot_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => StockIssue::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            // Create lines if provided
            if (!empty($data['lines'])) {
                $this->createLines($stockIssue, $data['lines']);
            }

            DB::commit();
            return $stockIssue->fresh(['lines.model', 'lines.serial']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update stock issue
     */
    public function updateStockIssue(StockIssue $stockIssue, array $data): StockIssue
    {
        DB::beginTransaction();
        try {
            // Only draft can be updated
            if ($stockIssue->status !== StockIssue::STATUS_DRAFT) {
                throw new Exception('Only draft stock issues can be updated');
            }

            // Update header
            $stockIssue->update([
                'issue_date' => $data['issue_date'],
                'issue_type' => $data['issue_type'],
                'from_depot_id' => $data['from_depot_id'] ?? null,
                'to_technician_id' => $data['to_technician_id'] ?? null,
                'from_technician_id' => $data['from_technician_id'] ?? null,
                'to_depot_id' => $data['to_depot_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Update lines if provided
            if (isset($data['lines'])) {
                // Delete existing lines
                $stockIssue->lines()->delete();

                // Create new lines
                $this->createLines($stockIssue, $data['lines']);
            }

            DB::commit();
            return $stockIssue->fresh(['lines.model', 'lines.serial']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create lines for stock issue
     */
    protected function createLines(StockIssue $stockIssue, array $lines): void
    {
        $lineNo = 1;
        $totalItems = 0;

        foreach ($lines as $line) {
            StockIssueLine::create([
                'stock_issue_id' => $stockIssue->id,
                'line_no' => $lineNo++,
                'model_id' => $line['model_id'],
                'serial_id' => $line['serial_id'] ?? null,
                'serial_no' => $line['serial_no'] ?? null,
                'quantity' => $line['quantity'] ?? 1,
                'remarks' => $line['remarks'] ?? null,
            ]);

            $totalItems += ($line['quantity'] ?? 1);
        }

        // Update total items
        $stockIssue->update(['total_items' => $totalItems]);
    }

    /**
     * Post stock issue (creates ledger entries)
     */
    public function postStockIssue(StockIssue $stockIssue): StockIssue
    {
        DB::beginTransaction();
        try {
            if ($stockIssue->status !== StockIssue::STATUS_DRAFT) {
                throw new Exception('Only draft stock issues can be posted');
            }

            if ($stockIssue->lines->isEmpty()) {
                throw new Exception('Cannot post stock issue without line items');
            }

            // Validate stock availability
            $this->validateStockAvailability($stockIssue);

            // Create ledger entries for each line
            foreach ($stockIssue->lines as $line) {
                $this->createLedgerEntry($stockIssue, $line);

                // Update serial status if serialized
                if ($line->serial_id) {
                    $this->updateSerialStatus($stockIssue, $line);
                }

                // Update stock balances
                $this->updateStockBalances($stockIssue, $line);
            }

            // Mark as posted
            $stockIssue->update([
                'status' => StockIssue::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            DB::commit();
            return $stockIssue->fresh(['lines.model', 'lines.serial']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate stock availability
     */
    protected function validateStockAvailability(StockIssue $stockIssue): void
    {
        foreach ($stockIssue->lines as $line) {
            // For issue to technician - check depot stock
            if ($stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
                if ($line->serial_id) {
                    // Check serial availability
                    $serial = InventorySerial::find($line->serial_id);
                    if (!$serial || $serial->current_location_type !== 'depot'
                        || $serial->current_location_id !== $stockIssue->from_depot_id) {
                        throw new Exception("Serial {$line->serial_no} is not available in the selected depot");
                    }
                    if ($serial->status !== 'available') {
                        throw new Exception("Serial {$line->serial_no} is not available (status: {$serial->status})");
                    }
                } else {
                    // Check non-serialized stock balance
                    $balance = StockBalance::where('model_id', $line->model_id)
                        ->where('location_type', 'depot')
                        ->where('location_id', $stockIssue->from_depot_id)
                        ->first();

                    $available = $balance ? $balance->quantity_on_hand - $balance->quantity_reserved : 0;
                    if ($available < $line->quantity) {
                        throw new Exception("Insufficient stock for model {$line->model->model_name}. Available: {$available}, Required: {$line->quantity}");
                    }
                }
            }

            // For return from technician - check technician stock
            if ($stockIssue->issue_type === StockIssue::TYPE_RETURN_FROM_TECH) {
                if ($line->serial_id) {
                    $serial = InventorySerial::find($line->serial_id);
                    if (!$serial || $serial->current_location_type !== 'technician'
                        || $serial->current_location_id !== $stockIssue->from_technician_id) {
                        throw new Exception("Serial {$line->serial_no} is not with the selected technician");
                    }
                }
            }
        }
    }

    /**
     * Create ledger entry for stock issue line
     */
    protected function createLedgerEntry(StockIssue $stockIssue, StockIssueLine $line): void
    {
        $transactionType = $stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH
            ? 'issue_to_tech'
            : 'return_from_tech';

        // Determine from/to locations
        if ($stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
            $fromLocationType = 'depot';
            $fromLocationId = $stockIssue->from_depot_id;
            $toLocationType = 'technician';
            $toLocationId = $stockIssue->to_technician_id;
            $quantity = -$line->quantity; // Negative for out
        } else {
            $fromLocationType = 'technician';
            $fromLocationId = $stockIssue->from_technician_id;
            $toLocationType = 'depot';
            $toLocationId = $stockIssue->to_depot_id;
            $quantity = $line->quantity; // Positive for in
        }

        StockLedger::create([
            'transaction_date' => $stockIssue->issue_date,
            'transaction_no' => $stockIssue->issue_no,
            'transaction_type' => $transactionType,
            'reference_type' => 'stock_issue',
            'reference_id' => $stockIssue->id,
            'serial_id' => $line->serial_id,
            'serial_no' => $line->serial_no,
            'model_id' => $line->model_id,
            'quantity' => $quantity,
            'from_location_type' => $fromLocationType,
            'from_location_id' => $fromLocationId,
            'to_location_type' => $toLocationType,
            'to_location_id' => $toLocationId,
            'remarks' => $line->remarks,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Update serial status
     */
    protected function updateSerialStatus(StockIssue $stockIssue, StockIssueLine $line): void
    {
        $serial = InventorySerial::find($line->serial_id);
        if (!$serial) return;

        if ($stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
            $serial->update([
                'current_location_type' => 'technician',
                'current_location_id' => $stockIssue->to_technician_id,
                'status' => 'issued',
            ]);
        } else {
            $serial->update([
                'current_location_type' => 'depot',
                'current_location_id' => $stockIssue->to_depot_id,
                'status' => 'available',
            ]);
        }
    }

    /**
     * Update stock balances
     */
    protected function updateStockBalances(StockIssue $stockIssue, StockIssueLine $line): void
    {
        if ($stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
            // Decrease depot stock
            $this->adjustBalance(
                $line->model_id,
                'depot',
                $stockIssue->from_depot_id,
                -$line->quantity,
                $stockIssue->issue_date
            );

            // Increase technician stock
            $this->adjustBalance(
                $line->model_id,
                'technician',
                $stockIssue->to_technician_id,
                $line->quantity,
                $stockIssue->issue_date
            );
        } else {
            // Decrease technician stock
            $this->adjustBalance(
                $line->model_id,
                'technician',
                $stockIssue->from_technician_id,
                -$line->quantity,
                $stockIssue->issue_date
            );

            // Increase depot stock
            $this->adjustBalance(
                $line->model_id,
                'depot',
                $stockIssue->to_depot_id,
                $line->quantity,
                $stockIssue->issue_date
            );
        }
    }

    /**
     * Adjust stock balance
     */
    protected function adjustBalance($modelId, $locationType, $locationId, $quantity, $movementDate): void
    {
        $balance = StockBalance::firstOrCreate(
            [
                'model_id' => $modelId,
                'location_type' => $locationType,
                'location_id' => $locationId,
            ],
            [
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]
        );

        $balance->update([
            'quantity_on_hand' => DB::raw("quantity_on_hand + ($quantity)"),
            'last_movement_date' => $movementDate,
        ]);
    }

    /**
     * Cancel/Reverse stock issue
     */
    public function cancelStockIssue(StockIssue $stockIssue): StockIssue
    {
        DB::beginTransaction();
        try {
            if ($stockIssue->status !== StockIssue::STATUS_POSTED) {
                throw new Exception('Only posted stock issues can be cancelled');
            }

            // Reverse ledger entries
            foreach ($stockIssue->lines as $line) {
                $this->reverseLedgerEntry($stockIssue, $line);

                // Reverse serial status if serialized
                if ($line->serial_id) {
                    $this->reverseSerialStatus($stockIssue, $line);
                }

                // Reverse stock balances
                $this->reverseStockBalances($stockIssue, $line);
            }

            // Mark as cancelled
            $stockIssue->update([
                'status' => StockIssue::STATUS_CANCELLED,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();
            return $stockIssue->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse ledger entry
     */
    protected function reverseLedgerEntry(StockIssue $stockIssue, StockIssueLine $line): void
    {
        $transactionType = $stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH
            ? 'issue_to_tech'
            : 'return_from_tech';

        // Get original ledger entry
        $originalLedger = StockLedger::where('reference_type', 'stock_issue')
            ->where('reference_id', $stockIssue->id)
            ->where('model_id', $line->model_id)
            ->where('serial_id', $line->serial_id)
            ->first();

        if ($originalLedger) {
            // Create reversal entry with opposite quantity
            StockLedger::create([
                'transaction_date' => now()->toDateString(),
                'transaction_no' => $stockIssue->issue_no . '-REV',
                'transaction_type' => $transactionType,
                'reference_type' => 'stock_issue_reversal',
                'reference_id' => $stockIssue->id,
                'serial_id' => $originalLedger->serial_id,
                'serial_no' => $originalLedger->serial_no,
                'model_id' => $originalLedger->model_id,
                'quantity' => -$originalLedger->quantity, // Opposite quantity
                'from_location_type' => $originalLedger->to_location_type,
                'from_location_id' => $originalLedger->to_location_id,
                'to_location_type' => $originalLedger->from_location_type,
                'to_location_id' => $originalLedger->from_location_id,
                'remarks' => 'Reversal of ' . $stockIssue->issue_no,
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Reverse serial status
     */
    protected function reverseSerialStatus(StockIssue $stockIssue, StockIssueLine $line): void
    {
        $serial = InventorySerial::find($line->serial_id);
        if (!$serial) return;

        if ($stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
            // Return to depot
            $serial->update([
                'current_location_type' => 'depot',
                'current_location_id' => $stockIssue->from_depot_id,
                'status' => 'available',
            ]);
        } else {
            // Return to technician
            $serial->update([
                'current_location_type' => 'technician',
                'current_location_id' => $stockIssue->from_technician_id,
                'status' => 'issued',
            ]);
        }
    }

    /**
     * Reverse stock balances
     */
    protected function reverseStockBalances(StockIssue $stockIssue, StockIssueLine $line): void
    {
        if ($stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
            // Increase depot stock (reverse the decrease)
            $this->adjustBalance(
                $line->model_id,
                'depot',
                $stockIssue->from_depot_id,
                $line->quantity,
                now()->toDateString()
            );

            // Decrease technician stock (reverse the increase)
            $this->adjustBalance(
                $line->model_id,
                'technician',
                $stockIssue->to_technician_id,
                -$line->quantity,
                now()->toDateString()
            );
        } else {
            // Increase technician stock (reverse the decrease)
            $this->adjustBalance(
                $line->model_id,
                'technician',
                $stockIssue->from_technician_id,
                $line->quantity,
                now()->toDateString()
            );

            // Decrease depot stock (reverse the increase)
            $this->adjustBalance(
                $line->model_id,
                'depot',
                $stockIssue->to_depot_id,
                -$line->quantity,
                now()->toDateString()
            );
        }
    }

    /**
     * Get available serials for depot
     */
    public function getAvailableSerials($depotId, $modelId = null)
    {
        $query = InventorySerial::where('current_location_type', 'depot')
            ->where('current_location_id', $depotId)
            ->where('status', 'available');

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query->with('model:id,model_name')->get();
    }

    /**
     * Get technician serials
     */
    public function getTechnicianSerials($technicianId, $modelId = null)
    {
        $query = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query->with('model:id,model_name')->get();
    }
}
