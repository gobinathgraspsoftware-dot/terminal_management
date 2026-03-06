<?php

namespace App\Services;

use App\Models\StockIssue;
use App\Models\StockIssueLine;
use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\NumberSeries;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class StockReturnService
{
    /**
     * Get filtered stock returns for DataTables
     */
    public function getFilteredStockReturns($request, $role, $userId)
    {
        $query = StockIssue::with([
            'fromTechnician:id,name',
            'toDepot:id,depot_name',
        ])->where('issue_type', StockIssue::TYPE_RETURN_FROM_TECH);

        // Apply role-based filtering
        if ($role === 'supervisor') {
            $teamTechnicianIds = User::where('supervisor_id', Auth::id())->pluck('id');
            $query->whereIn('from_technician_id', $teamTechnicianIds);
        } elseif ($role === 'technician') {
            $query->where('from_technician_id', $userId);
        }

        // Apply filters
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
            $query->where('to_depot_id', $request->depot_id);
        }

        if ($request->filled('technician_id')) {
            $query->where('from_technician_id', $request->technician_id);
        }

        if ($request->filled('condition')) {
            $query->whereHas('lines', function($q) use ($request) {
                $q->where('condition', $request->condition);
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
     * Generate next return number
     */
    public function generateReturnNumber(): string
    {
        return DB::transaction(function () {
            $series = NumberSeries::lockForUpdate()
                ->where('series_type', 'stock_return')
                ->first();

            if (!$series) {
                $series = NumberSeries::create([
                    'series_type' => 'stock_return',
                    'prefix' => 'SR',
                    'current_number' => 0,
                    'number_length' => 5,
                    'is_active' => 1,
                ]);
            }

            // Increment first
            $series->increment('current_number');

            // Generate return number
            $returnNo = $series->prefix . str_pad($series->current_number, $series->number_length, '0', STR_PAD_LEFT);

            return $returnNo;
        });
    }

    /**
     * Get technician's current inventory
     */
    public function getTechnicianInventory($technicianId, $modelId = null)
    {
        $query = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('status', ['issued', 'deployed', 'faulty']);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query->with([
            'model:id,model_name,category_id',
            'model.category:id,category_name'
        ])->get();
    }

    /**
     * Get technician inventory summary (for listing)
     */
    public function getTechnicianInventorySummary($technicianId)
    {
        return DB::table('inventory_serials as is')
            ->join('terminal_models as tm', 'is.model_id', '=', 'tm.id')
            ->join('terminal_categories as tc', 'tm.category_id', '=', 'tc.id')
            ->select(
                'tm.id as model_id',
                'tm.model_name',
                'tc.category_name',
                'is.current_status',
                DB::raw('COUNT(*) as quantity'),
                DB::raw('GROUP_CONCAT(is.serial_no ORDER BY is.serial_no SEPARATOR ", ") as serial_numbers'),
                DB::raw('GROUP_CONCAT(is.id ORDER BY is.serial_no SEPARATOR ",") as serial_ids')
            )
            ->where('is.current_location_type', 'technician')
            ->where('is.current_location_id', $technicianId)
            ->whereIn('is.current_status', ['issued', 'deployed', 'faulty'])
            ->groupBy('tm.id', 'tm.model_name', 'tc.category_name', 'is.current_status')
            ->orderBy('tc.category_name')
            ->orderBy('tm.model_name')
            ->get();
    }

    /**
     * Create stock return
     */
    public function createStockReturn(array $data): StockIssue
    {
        DB::beginTransaction();
        try {
            // Generate return number if not provided
            if (empty($data['issue_no'])) {
                $data['issue_no'] = $this->generateReturnNumber();
            }

            // Validate technician has the items
            $this->validateTechnicianInventory($data);

            // Create header
            $stockReturn = StockIssue::create([
                'issue_no' => $data['issue_no'],
                'issue_date' => $data['issue_date'],
                'issue_type' => StockIssue::TYPE_RETURN_FROM_TECH,
                'from_technician_id' => $data['from_technician_id'],
                'to_depot_id' => $data['to_depot_id'],
                'remarks' => $data['remarks'] ?? null,
                'status' => StockIssue::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            // Create lines if provided
            if (!empty($data['lines'])) {
                $this->createLines($stockReturn, $data['lines']);
            }

            DB::commit();
            return $stockReturn->fresh(['lines.model', 'lines.serial']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update stock return
     */
    public function updateStockReturn(StockIssue $stockReturn, array $data): StockIssue
    {
        DB::beginTransaction();
        try {
            // Only draft can be updated
            if ($stockReturn->status !== StockIssue::STATUS_DRAFT) {
                throw new Exception('Only draft stock returns can be updated');
            }

            // Validate technician has the items
            $this->validateTechnicianInventory($data);

            // Update header
            $stockReturn->update([
                'issue_date' => $data['issue_date'],
                'from_technician_id' => $data['from_technician_id'],
                'to_depot_id' => $data['to_depot_id'],
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Update lines if provided
            if (isset($data['lines'])) {
                // Delete existing lines
                $stockReturn->lines()->delete();

                // Create new lines
                $this->createLines($stockReturn, $data['lines']);
            }

            DB::commit();
            return $stockReturn->fresh(['lines.model', 'lines.serial']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create lines for stock return
     */
    protected function createLines(StockIssue $stockReturn, array $lines): void
    {
        $lineNo = 1;
        $totalItems = 0;

        foreach ($lines as $line) {
            StockIssueLine::create([
                'stock_issue_id' => $stockReturn->id,
                'line_no' => $lineNo++,
                'model_id' => $line['model_id'],
                'serial_id' => $line['serial_id'] ?? null,
                'serial_no' => $line['serial_no'] ?? null,
                'quantity' => $line['quantity'] ?? 1,
                'condition' => $line['condition'] ?? StockIssueLine::CONDITION_GOOD,
                'remarks' => $line['remarks'] ?? null,
            ]);

            $totalItems += ($line['quantity'] ?? 1);
        }

        // Update total items
        $stockReturn->update(['total_items' => $totalItems]);
    }

    /**
     * Validate technician has the items being returned
     */
    protected function validateTechnicianInventory(array $data): void
    {
        if (empty($data['lines'])) {
            return;
        }

        $technicianId = $data['from_technician_id'];

        foreach ($data['lines'] as $line) {
            if (!empty($line['serial_id'])) {
                $serial = InventorySerial::find($line['serial_id']);

                if (!$serial) {
                    throw new Exception("Serial number not found");
                }

                if ($serial->current_location_type !== 'technician' ||
                    $serial->current_location_id != $technicianId) {
                    throw new Exception("Serial {$serial->serial_no} is not with this technician");
                }
            }
        }
    }

    /**
     * Post stock return (creates ledger entries)
     */
    public function postStockReturn(StockIssue $stockReturn): StockIssue
    {
        DB::beginTransaction();
        try {
            if ($stockReturn->status !== StockIssue::STATUS_DRAFT) {
                throw new Exception('Only draft stock returns can be posted');
            }

            if ($stockReturn->lines->isEmpty()) {
                throw new Exception('Cannot post stock return without line items');
            }

            // Create ledger entries for each line
            foreach ($stockReturn->lines as $line) {
                $this->createLedgerEntry($stockReturn, $line);

                // Update serial status and location if serialized
                if ($line->serial_id) {
                    $this->updateSerialLocation($stockReturn, $line);
                }

                // Update stock balances
                $this->updateStockBalances($stockReturn, $line);
            }

            // Mark as posted
            $stockReturn->update([
                'status' => StockIssue::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            DB::commit();
            return $stockReturn->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create ledger entry for stock return
     */
    protected function createLedgerEntry(StockIssue $stockReturn, StockIssueLine $line): void
    {
        // Get the latest unit cost for this model at the depot (if exists)
        $latestLedger = StockLedger::where('model_id', $line->model_id)
            ->where('to_location_type', 'depot')
            ->where('to_location_id', $stockReturn->to_depot_id)
            ->whereNotNull('unit_cost')
            ->orderBy('id', 'desc')
            ->first();

        $unitCost = $latestLedger->unit_cost ?? 0;
        $totalCost = $unitCost * $line->quantity;

        StockLedger::create([
            'transaction_date' => $stockReturn->issue_date,
            'transaction_no' => $stockReturn->issue_no,
            'transaction_type' => 'return_from_tech',
            'reference_type' => 'stock_return',
            'reference_id' => $stockReturn->id,
            'serial_id' => $line->serial_id,
            'serial_no' => $line->serial_no,
            'model_id' => $line->model_id,
            'quantity' => $line->quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'from_location_type' => 'technician',
            'from_location_id' => $stockReturn->from_technician_id,
            'to_location_type' => 'depot',
            'to_location_id' => $stockReturn->to_depot_id,
            'remarks' => $line->condition !== StockIssueLine::CONDITION_GOOD
                ? "Returned in {$line->condition} condition. {$line->remarks}"
                : $line->remarks,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Update serial location and status based on condition
     */
    protected function updateSerialLocation(StockIssue $stockReturn, StockIssueLine $line): void
    {
        $serial = InventorySerial::find($line->serial_id);
        if (!$serial) return;

        // Determine status based on condition
        $status = match($line->condition) {
            StockIssueLine::CONDITION_GOOD => 'available',
            StockIssueLine::CONDITION_DAMAGED => 'damaged',
            StockIssueLine::CONDITION_DEFECTIVE => 'defective',
            default => 'available',
        };

        $serial->update([
            'current_location_type' => 'depot',
            'current_location_id' => $stockReturn->to_depot_id,
            'status' => $status,
        ]);
    }

    /**
     * Update stock balances
     */
    protected function updateStockBalances(StockIssue $stockReturn, StockIssueLine $line): void
    {
        // Decrease technician stock
        $this->adjustBalance(
            $line->model_id,
            'technician',
            $stockReturn->from_technician_id,
            -$line->quantity,
            $stockReturn->issue_date
        );

        // Increase depot stock
        $this->adjustBalance(
            $line->model_id,
            'depot',
            $stockReturn->to_depot_id,
            $line->quantity,
            $stockReturn->issue_date
        );
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

        // Use increment/decrement to avoid casting issues with DB::raw()
        if ($quantity > 0) {
            $balance->increment('quantity_on_hand', abs($quantity));
        } else {
            $balance->decrement('quantity_on_hand', abs($quantity));
        }

        // Update last movement date separately
        $balance->update(['last_movement_date' => $movementDate]);
    }

    /**
     * Cancel/Reverse stock return
     */
    public function cancelStockReturn(StockIssue $stockReturn): StockIssue
    {
        DB::beginTransaction();
        try {
            if ($stockReturn->status !== StockIssue::STATUS_POSTED) {
                throw new Exception('Only posted stock returns can be cancelled');
            }

            // Reverse ledger entries
            foreach ($stockReturn->lines as $line) {
                $this->reverseLedgerEntry($stockReturn, $line);

                // Reverse serial status if serialized
                if ($line->serial_id) {
                    $this->reverseSerialLocation($stockReturn, $line);
                }

                // Reverse stock balances
                $this->reverseStockBalances($stockReturn, $line);
            }

            // Mark as cancelled
            $stockReturn->update([
                'status' => StockIssue::STATUS_CANCELLED,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();
            return $stockReturn->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse ledger entry
     */
    protected function reverseLedgerEntry(StockIssue $stockReturn, StockIssueLine $line): void
    {
        // Get original ledger entry
        $originalLedger = StockLedger::where('reference_type', 'stock_return')
            ->where('reference_id', $stockReturn->id)
            ->where('model_id', $line->model_id)
            ->where('serial_id', $line->serial_id)
            ->first();

        if ($originalLedger) {
            // Create reversal entry with opposite quantity
            StockLedger::create([
                'transaction_date' => now()->toDateString(),
                'transaction_no' => $stockReturn->issue_no . '-REV',
                'transaction_type' => 'return_from_tech',
                'reference_type' => 'stock_return_reversal',
                'reference_id' => $stockReturn->id,
                'serial_id' => $originalLedger->serial_id,
                'serial_no' => $originalLedger->serial_no,
                'model_id' => $originalLedger->model_id,
                'quantity' => -$originalLedger->quantity,
                'unit_cost' => $originalLedger->unit_cost ?? 0,
                'total_cost' => ($originalLedger->unit_cost ?? 0) * -$originalLedger->quantity,
                'from_location_type' => $originalLedger->to_location_type,
                'from_location_id' => $originalLedger->to_location_id,
                'to_location_type' => $originalLedger->from_location_type,
                'to_location_id' => $originalLedger->from_location_id,
                'remarks' => 'Reversal of ' . $stockReturn->issue_no,
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Reverse serial location
     */
    protected function reverseSerialLocation(StockIssue $stockReturn, StockIssueLine $line): void
    {
        $serial = InventorySerial::find($line->serial_id);
        if (!$serial) return;

        // Return to technician
        $serial->update([
            'current_location_type' => 'technician',
            'current_location_id' => $stockReturn->from_technician_id,
            'status' => 'issued',
        ]);
    }

    /**
     * Reverse stock balances
     */
    protected function reverseStockBalances(StockIssue $stockReturn, StockIssueLine $line): void
    {
        // Increase technician stock (reverse the decrease)
        $this->adjustBalance(
            $line->model_id,
            'technician',
            $stockReturn->from_technician_id,
            $line->quantity,
            now()->toDateString()
        );

        // Decrease depot stock (reverse the increase)
        $this->adjustBalance(
            $line->model_id,
            'depot',
            $stockReturn->to_depot_id,
            -$line->quantity,
            now()->toDateString()
        );
    }

    /**
     * Get damaged/defective items summary
     */
    public function getDamagedItemsSummary($request, $role, $userId)
    {
        $query = StockIssueLine::with([
            'model:id,model_name',
            'stockIssue.fromTechnician:id,name',
            'stockIssue.toDepot:id,depot_name'
        ])
        ->whereHas('stockIssue', function($q) {
            $q->where('issue_type', StockIssue::TYPE_RETURN_FROM_TECH)
              ->where('status', StockIssue::STATUS_POSTED);
        })
        ->whereIn('condition', [StockIssueLine::CONDITION_DAMAGED, StockIssueLine::CONDITION_DEFECTIVE]);

        // Apply role-based filtering
        if ($role === 'supervisor') {
            $teamTechnicianIds = User::where('supervisor_id', Auth::id())->pluck('id');
            $query->whereHas('stockIssue', function($q) use ($teamTechnicianIds) {
                $q->whereIn('from_technician_id', $teamTechnicianIds);
            });
        } elseif ($role === 'technician') {
            $query->whereHas('stockIssue', function($q) use ($userId) {
                $q->where('from_technician_id', $userId);
            });
        }

        return $query;
    }
}
