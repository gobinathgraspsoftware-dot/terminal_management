<?php

namespace App\Services\Inventory;

use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\TerminalModel;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StockBalanceService
{
    /**
     * Update stock balance after a movement
     */
    public function updateBalance(
        int $modelId,
        string $locationType,
        int $locationId,
        float $quantity,
        ?string $movementDate = null
    ): StockBalance {
        DB::beginTransaction();
        try {
            // Get or create balance record
            $balance = StockBalance::firstOrCreate(
                [
                    'model_id' => $modelId,
                    'location_type' => $locationType,
                    'location_id' => $locationId,
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'last_movement_date' => $movementDate ?? now()->toDateString(),
                ]
            );

            // Calculate new quantity
            $newQuantity = $balance->quantity_on_hand + $quantity;

            // Prevent negative inventory
            if ($newQuantity < 0) {
                throw new Exception(
                    "Insufficient stock. Available: {$balance->quantity_on_hand}, Required: " . abs($quantity)
                );
            }

            // Update balance
            $balance->update([
                'quantity_on_hand' => $newQuantity,
                'last_movement_date' => $movementDate ?? now()->toDateString(),
            ]);

            DB::commit();

            Log::info('Stock balance updated', [
                'model_id' => $modelId,
                'location' => "{$locationType}#{$locationId}",
                'old_quantity' => $balance->quantity_on_hand - $quantity,
                'change' => $quantity,
                'new_quantity' => $newQuantity,
            ]);

            // Check for low stock alert
            $this->checkLowStockAlert($balance);

            return $balance->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Balance update failed', [
                'model_id' => $modelId,
                'location' => "{$locationType}#{$locationId}",
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get current balance
     */
    public function getBalance(int $modelId, string $locationType, int $locationId): float
    {
        $balance = StockBalance::where('model_id', $modelId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->first();

        return $balance ? $balance->quantity_on_hand : 0;
    }

    /**
     * Get available balance (on hand - reserved)
     */
    public function getAvailableBalance(int $modelId, string $locationType, int $locationId): float
    {
        $balance = StockBalance::where('model_id', $modelId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->first();

        return $balance ? $balance->quantity_available : 0;
    }

    /**
     * Check if sufficient stock is available
     */
    public function checkAvailability(
        int $modelId,
        string $locationType,
        int $locationId,
        float $requiredQuantity
    ): bool {
        $available = $this->getAvailableBalance($modelId, $locationType, $locationId);
        return $available >= $requiredQuantity;
    }

    /**
     * Reserve stock for future transaction
     */
    public function reserveStock(
        int $modelId,
        string $locationType,
        int $locationId,
        float $quantity,
        ?string $reservationReason = null
    ): StockBalance {
        DB::beginTransaction();
        try {
            $balance = StockBalance::where('model_id', $modelId)
                ->where('location_type', $locationType)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->firstOrFail();

            // Check if enough available stock
            if ($balance->quantity_available < $quantity) {
                throw new Exception(
                    "Insufficient available stock. Available: {$balance->quantity_available}, Required: {$quantity}"
                );
            }

            // Update reservation
            $balance->increment('quantity_reserved', $quantity);

            DB::commit();

            Log::info('Stock reserved', [
                'model_id' => $modelId,
                'location' => "{$locationType}#{$locationId}",
                'quantity' => $quantity,
                'reason' => $reservationReason,
            ]);

            return $balance->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stock reservation failed', [
                'model_id' => $modelId,
                'location' => "{$locationType}#{$locationId}",
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Release reserved stock
     */
    public function releaseReservation(
        int $modelId,
        string $locationType,
        int $locationId,
        float $quantity
    ): StockBalance {
        DB::beginTransaction();
        try {
            $balance = StockBalance::where('model_id', $modelId)
                ->where('location_type', $locationType)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->firstOrFail();

            // Prevent negative reservation
            $newReserved = max(0, $balance->quantity_reserved - $quantity);

            $balance->update([
                'quantity_reserved' => $newReserved,
            ]);

            DB::commit();

            Log::info('Stock reservation released', [
                'model_id' => $modelId,
                'location' => "{$locationType}#{$locationId}",
                'quantity' => $quantity,
            ]);

            return $balance->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Reservation release failed', [
                'model_id' => $modelId,
                'location' => "{$locationType}#{$locationId}",
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all balances for a model across locations
     */
    public function getModelBalances(int $modelId)
    {
        return StockBalance::with(['model'])
            ->where('model_id', $modelId)
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('location_type')
            ->orderBy('location_id')
            ->get();
    }

    /**
     * Get all balances for a location
     */
    public function getLocationBalances(string $locationType, int $locationId)
    {
        return StockBalance::with('model')
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('model_id')
            ->get();
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems(?string $locationType = null, ?int $locationId = null)
    {
        $query = StockBalance::with(['model'])
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('quantity_available', 'asc');

        if ($locationType && $locationId) {
            $query->where('location_type', $locationType)
                ->where('location_id', $locationId);
        }

        $balances = $query->get();

        // Filter by low stock threshold
        return $balances->filter(function ($balance) {
            $model = $balance->model;
            $minStock = $model->min_stock_level ?? 5;
            return $balance->quantity_available <= $minStock;
        });
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems(?string $locationType = null, ?int $locationId = null)
    {
        $query = StockBalance::with(['model'])
            ->where('quantity_available', '<=', 0);

        if ($locationType && $locationId) {
            $query->where('location_type', $locationType)
                ->where('location_id', $locationId);
        }

        return $query->get();
    }

    /**
     * Recalculate all balances from ledger (data integrity check)
     */
    public function recalculateAllBalances(?int $modelId = null): array
    {
        DB::beginTransaction();
        try {
            $results = [
                'total_recalculated' => 0,
                'discrepancies_found' => 0,
                'errors' => [],
            ];

            // Get all unique location combinations from ledger
            $query = StockLedger::select('model_id', 'to_location_type as location_type', 'to_location_id as location_id')
                ->whereNotNull('to_location_type')
                ->whereNotNull('to_location_id')
                ->union(
                    StockLedger::select('model_id', 'from_location_type as location_type', 'from_location_id as location_id')
                        ->whereNotNull('from_location_type')
                        ->whereNotNull('from_location_id')
                )
                ->distinct();

            if ($modelId) {
                $query->where('model_id', $modelId);
            }

            $locations = $query->get();

            foreach ($locations as $location) {
                try {
                    $calculated = $this->recalculateBalance(
                        $location->model_id,
                        $location->location_type,
                        $location->location_id
                    );

                    $results['total_recalculated']++;

                    if ($calculated['has_discrepancy']) {
                        $results['discrepancies_found']++;
                    }

                } catch (Exception $e) {
                    $results['errors'][] = [
                        'model_id' => $location->model_id,
                        'location' => "{$location->location_type}#{$location->location_id}",
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Balance recalculation completed', $results);

            return $results;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Balance recalculation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Recalculate balance for specific location
     */
    public function recalculateBalance(int $modelId, string $locationType, int $locationId): array
    {
        // Get current balance from database
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

        $currentBalance = $balance->quantity_on_hand;

        // Calculate from ledger
        $inbound = StockLedger::activeMovements()
            ->where('model_id', $modelId)
            ->where('to_location_type', $locationType)
            ->where('to_location_id', $locationId)
            ->sum('quantity');

        $outbound = StockLedger::activeMovements()
            ->where('model_id', $modelId)
            ->where('from_location_type', $locationType)
            ->where('from_location_id', $locationId)
            ->sum('quantity');

        $calculatedBalance = $inbound - $outbound;

        // Check for discrepancy
        $hasDiscrepancy = abs($currentBalance - $calculatedBalance) > 0.0001;

        // Update if discrepancy found
        if ($hasDiscrepancy) {
            $balance->update([
                'quantity_on_hand' => $calculatedBalance,
            ]);

            Log::warning('Balance discrepancy corrected', [
                'model_id' => $modelId,
                'location' => "{$locationType}#{$locationId}",
                'old_balance' => $currentBalance,
                'calculated_balance' => $calculatedBalance,
                'difference' => $calculatedBalance - $currentBalance,
            ]);
        }

        return [
            'model_id' => $modelId,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'old_balance' => $currentBalance,
            'calculated_balance' => $calculatedBalance,
            'has_discrepancy' => $hasDiscrepancy,
        ];
    }

    /**
     * Check for low stock alert
     */
    protected function checkLowStockAlert(StockBalance $balance): void
    {
        $model = $balance->model;
        $minStock = $model->min_stock_level ?? 5;

        if ($balance->quantity_available <= $minStock && $balance->quantity_available > 0) {
            // Log low stock warning
            Log::warning('Low stock alert', [
                'model_id' => $balance->model_id,
                'model_name' => $model->model_name,
                'location' => "{$balance->location_type}#{$balance->location_id}",
                'current_stock' => $balance->quantity_available,
                'min_stock' => $minStock,
            ]);

            // You can trigger notification here
            // event(new LowStockAlert($balance));
        }

        if ($balance->quantity_available <= 0) {
            Log::warning('Out of stock alert', [
                'model_id' => $balance->model_id,
                'model_name' => $model->model_name,
                'location' => "{$balance->location_type}#{$balance->location_id}",
            ]);

            // You can trigger notification here
            // event(new OutOfStockAlert($balance));
        }
    }

    /**
     * Get stock summary by location type
     */
    public function getStockSummary(?string $locationType = null)
    {
        $query = StockBalance::select(
            'location_type',
            DB::raw('COUNT(DISTINCT model_id) as unique_models'),
            DB::raw('SUM(quantity_on_hand) as total_quantity'),
            DB::raw('SUM(quantity_reserved) as total_reserved'),
            DB::raw('SUM(quantity_available) as total_available')
        )
        ->where('quantity_on_hand', '>', 0)
        ->groupBy('location_type');

        if ($locationType) {
            $query->where('location_type', $locationType);
        }

        return $query->get();
    }

    /**
     * Get detailed balance report
     */
    public function getBalanceReport(array $filters = [])
    {
        $query = StockBalance::with(['model'])
            ->where('quantity_on_hand', '>', 0);

        if (isset($filters['location_type'])) {
            $query->where('location_type', $filters['location_type']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (isset($filters['category_id'])) {
            $query->whereHas('model', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            // This would need to be filtered in PHP after loading
            // as it requires model's min_stock_level
        }

        return $query->orderBy('model_id')->orderBy('location_type')->get();
    }

    /**
     * Transfer stock between locations (with balance update)
     */
    public function transferStock(
        int $modelId,
        string $fromLocationType,
        int $fromLocationId,
        string $toLocationType,
        int $toLocationId,
        float $quantity
    ): array {
        DB::beginTransaction();
        try {
            // Check availability at source
            if (!$this->checkAvailability($modelId, $fromLocationType, $fromLocationId, $quantity)) {
                throw new Exception('Insufficient stock at source location');
            }

            // Update FROM location
            $fromBalance = $this->updateBalance(
                $modelId,
                $fromLocationType,
                $fromLocationId,
                -abs($quantity)
            );

            // Update TO location
            $toBalance = $this->updateBalance(
                $modelId,
                $toLocationType,
                $toLocationId,
                abs($quantity)
            );

            DB::commit();

            return [
                'from_balance' => $fromBalance,
                'to_balance' => $toBalance,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
