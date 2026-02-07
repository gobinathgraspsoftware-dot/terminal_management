<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\Depot;
use App\Models\TerminalModel;
use App\Models\NumberSeries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StockAdjustmentService
{
    /**
     * Get filtered and paginated stock adjustments
     */
    public function getFilteredAdjustments($filters = [], $perPage = 15)
    {
        $query = StockAdjustment::with(['depot', 'creator', 'approver'])
            ->orderBy('adjustment_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['depot_id'])) {
            $query->where('depot_id', $filters['depot_id']);
        }

        if (!empty($filters['adjustment_type'])) {
            $query->where('adjustment_type', $filters['adjustment_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('adjustment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('adjustment_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_no', 'like', $search)
                  ->orWhere('reason', 'like', $search);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Create new stock adjustment
     */
    public function createAdjustment(array $data, $userId): StockAdjustment
    {
        DB::beginTransaction();
        
        try {
            // Generate adjustment number
            $adjustmentNo = $this->generateAdjustmentNumber();

            // Create adjustment header
            $adjustment = StockAdjustment::create([
                'adjustment_no' => $adjustmentNo,
                'adjustment_date' => $data['adjustment_date'],
                'adjustment_type' => $data['adjustment_type'],
                'depot_id' => $data['depot_id'],
                'reason' => $data['reason'],
                'status' => $data['status'] ?? StockAdjustment::STATUS_DRAFT,
                'total_items' => count($data['lines']),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Create adjustment lines
            if (!empty($data['lines'])) {
                $this->createAdjustmentLines($adjustment, $data['lines']);
            }

            DB::commit();
            
            Log::info('Stock adjustment created', [
                'adjustment_no' => $adjustmentNo,
                'user_id' => $userId
            ]);

            return $adjustment->load('lines.model', 'depot');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating stock adjustment', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Update existing stock adjustment
     */
    public function updateAdjustment(StockAdjustment $adjustment, array $data, $userId): StockAdjustment
    {
        // Can only update draft adjustments
        if ($adjustment->status !== StockAdjustment::STATUS_DRAFT) {
            throw new \Exception('Only draft adjustments can be updated.');
        }

        DB::beginTransaction();
        
        try {
            // Update adjustment header
            $adjustment->update([
                'adjustment_date' => $data['adjustment_date'],
                'adjustment_type' => $data['adjustment_type'],
                'depot_id' => $data['depot_id'],
                'reason' => $data['reason'],
                'status' => $data['status'] ?? $adjustment->status,
                'total_items' => count($data['lines']),
                'updated_by' => $userId,
            ]);

            // Delete old lines and create new ones
            $adjustment->lines()->delete();
            
            if (!empty($data['lines'])) {
                $this->createAdjustmentLines($adjustment, $data['lines']);
            }

            DB::commit();
            
            Log::info('Stock adjustment updated', [
                'adjustment_no' => $adjustment->adjustment_no,
                'user_id' => $userId
            ]);

            return $adjustment->fresh()->load('lines.model', 'depot');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating stock adjustment', [
                'adjustment_no' => $adjustment->adjustment_no,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Create adjustment lines
     */
    private function createAdjustmentLines(StockAdjustment $adjustment, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $varianceQty = $line['physical_quantity'] - $line['system_quantity'];
            $varianceValue = isset($line['unit_cost']) 
                ? $varianceQty * $line['unit_cost'] 
                : null;

            StockAdjustmentLine::create([
                'stock_adjustment_id' => $adjustment->id,
                'line_no' => $index + 1,
                'model_id' => $line['model_id'],
                'serial_id' => $line['serial_id'] ?? null,
                'serial_no' => $line['serial_no'] ?? null,
                'system_quantity' => $line['system_quantity'],
                'physical_quantity' => $line['physical_quantity'],
                'unit_cost' => $line['unit_cost'] ?? null,
                'variance_value' => $varianceValue,
                'remarks' => $line['remarks'] ?? null,
            ]);
        }
    }

    /**
     * Submit adjustment for approval
     */
    public function submitForApproval(StockAdjustment $adjustment, $userId): StockAdjustment
    {
        if ($adjustment->status !== StockAdjustment::STATUS_DRAFT) {
            throw new \Exception('Only draft adjustments can be submitted for approval.');
        }

        $adjustment->update([
            'status' => StockAdjustment::STATUS_PENDING_APPROVAL,
            'updated_by' => $userId,
        ]);

        Log::info('Stock adjustment submitted for approval', [
            'adjustment_no' => $adjustment->adjustment_no,
            'user_id' => $userId
        ]);

        return $adjustment;
    }

    /**
     * Approve stock adjustment
     */
    public function approveAdjustment(StockAdjustment $adjustment, $userId): StockAdjustment
    {
        if ($adjustment->status !== StockAdjustment::STATUS_PENDING_APPROVAL) {
            throw new \Exception('Only pending adjustments can be approved.');
        }

        if ($adjustment->created_by === $userId) {
            throw new \Exception('You cannot approve your own adjustment.');
        }

        $adjustment->update([
            'status' => StockAdjustment::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        Log::info('Stock adjustment approved', [
            'adjustment_no' => $adjustment->adjustment_no,
            'approved_by' => $userId
        ]);

        return $adjustment;
    }

    /**
     * Reject stock adjustment
     */
    public function rejectAdjustment(StockAdjustment $adjustment, $userId, $remarks = null): StockAdjustment
    {
        if ($adjustment->status !== StockAdjustment::STATUS_PENDING_APPROVAL) {
            throw new \Exception('Only pending adjustments can be rejected.');
        }

        $adjustment->update([
            'status' => StockAdjustment::STATUS_REJECTED,
            'reason' => $adjustment->reason . "\n\nRejection Remarks: " . $remarks,
            'updated_by' => $userId,
        ]);

        Log::info('Stock adjustment rejected', [
            'adjustment_no' => $adjustment->adjustment_no,
            'rejected_by' => $userId,
            'remarks' => $remarks
        ]);

        return $adjustment;
    }

    /**
     * Post approved adjustment (update stock ledger and balances)
     */
    public function postAdjustment(StockAdjustment $adjustment, $userId): StockAdjustment
    {
        if ($adjustment->status !== StockAdjustment::STATUS_APPROVED) {
            throw new \Exception('Only approved adjustments can be posted.');
        }

        DB::beginTransaction();
        
        try {
            foreach ($adjustment->lines as $line) {
                $varianceQty = $line->physical_quantity - $line->system_quantity;
                
                // Skip if no variance
                if ($varianceQty == 0) {
                    continue;
                }

                // Create stock ledger entry
                StockLedger::create([
                    'transaction_date' => $adjustment->adjustment_date,
                    'transaction_no' => $adjustment->adjustment_no,
                    'transaction_type' => 'adjustment',
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => $adjustment->id,
                    'serial_id' => $line->serial_id,
                    'serial_no' => $line->serial_no,
                    'model_id' => $line->model_id,
                    'quantity' => $varianceQty, // Positive = increase, Negative = decrease
                    'from_location_type' => $varianceQty < 0 ? 'depot' : null,
                    'from_location_id' => $varianceQty < 0 ? $adjustment->depot_id : null,
                    'to_location_type' => $varianceQty > 0 ? 'depot' : null,
                    'to_location_id' => $varianceQty > 0 ? $adjustment->depot_id : null,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => $line->variance_value,
                    'remarks' => "Stock Adjustment - {$adjustment->adjustment_type}: " . $line->remarks,
                    'created_by' => $userId,
                ]);

                // Update stock balance
                $this->updateStockBalance(
                    $line->model_id,
                    $adjustment->depot_id,
                    $varianceQty,
                    $adjustment->adjustment_date
                );
            }

            // Mark adjustment as posted
            $adjustment->update([
                'status' => StockAdjustment::STATUS_POSTED ?? 'approved', // Using approved if posted doesn't exist
                'updated_by' => $userId,
            ]);

            DB::commit();
            
            Log::info('Stock adjustment posted', [
                'adjustment_no' => $adjustment->adjustment_no,
                'user_id' => $userId
            ]);

            return $adjustment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error posting stock adjustment', [
                'adjustment_no' => $adjustment->adjustment_no,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Update stock balance
     */
    private function updateStockBalance($modelId, $depotId, $quantity, $date): void
    {
        $balance = StockBalance::firstOrCreate(
            [
                'model_id' => $modelId,
                'location_type' => 'depot',
                'location_id' => $depotId,
            ],
            [
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]
        );

        $balance->increment('quantity_on_hand', $quantity);
        $balance->last_movement_date = $date;
        $balance->save();
    }

    /**
     * Get current stock for depot
     */
    public function getDepotStock($depotId, $modelId = null)
    {
        $query = StockBalance::with('model')
            ->where('location_type', 'depot')
            ->where('location_id', $depotId)
            ->where('quantity_on_hand', '>', 0);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query->get();
    }

    /**
     * Get variance report data
     */
    public function getVarianceReport($filters = [])
    {
        $query = StockAdjustmentLine::with(['stockAdjustment.depot', 'model'])
            ->whereHas('stockAdjustment', function ($q) use ($filters) {
                if (!empty($filters['depot_id'])) {
                    $q->where('depot_id', $filters['depot_id']);
                }
                if (!empty($filters['adjustment_type'])) {
                    $q->where('adjustment_type', $filters['adjustment_type']);
                }
                if (!empty($filters['date_from'])) {
                    $q->where('adjustment_date', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $q->where('adjustment_date', '<=', $filters['date_to']);
                }
                if (!empty($filters['status'])) {
                    $q->where('status', $filters['status']);
                }
            })
            ->whereRaw('physical_quantity != system_quantity')
            ->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * Generate adjustment number
     */
    private function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ';
        $year = date('Y');
        $month = date('m');

        // Get or create number series
        $series = NumberSeries::firstOrCreate(
            [
                'series_type' => 'stock_adjustment',
                'prefix' => $prefix,
                'year' => $year,
                'month' => $month,
            ],
            [
                'last_number' => 0,
                'padding' => 5,
            ]
        );

        // Increment and get next number
        DB::table('number_series')
            ->where('id', $series->id)
            ->lockForUpdate()
            ->increment('last_number');

        $series->refresh();

        return sprintf(
            '%s%s%s%s',
            $prefix,
            $year,
            $month,
            str_pad($series->last_number, $series->padding, '0', STR_PAD_LEFT)
        );
    }

    /**
     * Cancel/delete draft adjustment
     */
    public function cancelAdjustment(StockAdjustment $adjustment, $userId): bool
    {
        if ($adjustment->status !== StockAdjustment::STATUS_DRAFT) {
            throw new \Exception('Only draft adjustments can be cancelled.');
        }

        DB::beginTransaction();
        
        try {
            $adjustmentNo = $adjustment->adjustment_no;
            
            // Delete lines first
            $adjustment->lines()->delete();
            
            // Delete adjustment
            $adjustment->delete();

            DB::commit();
            
            Log::info('Stock adjustment cancelled', [
                'adjustment_no' => $adjustmentNo,
                'user_id' => $userId
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling stock adjustment', [
                'adjustment_no' => $adjustment->adjustment_no,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }
}
