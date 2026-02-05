<?php

namespace App\Services\Inventory;

use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\InventorySerial;
use App\Models\TerminalModel;
use App\Models\Depot;
use App\Models\Grn;
use App\Models\GrnLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Stock Valuation Service
 * 
 * Handles all stock valuation calculations including:
 * - Average cost calculation
 * - Inventory valuation by location/category/model
 * - Movement value tracking
 * - Cost of goods issued/installed/wasted
 */
class StockValuationService
{
    /**
     * Get stock valuation summary
     *
     * @param array $filters
     * @return array
     */
    public function getValuationSummary(array $filters = []): array
    {
        $query = StockBalance::with(['model.category', 'depot'])
            ->where('quantity_on_hand', '>', 0);

        // Apply filters
        if (!empty($filters['depot_id'])) {
            $query->where('location_type', 'depot')
                  ->where('location_id', $filters['depot_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('model', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        $balances = $query->get();

        // Calculate values
        $totalValue = 0;
        $totalQuantity = 0;
        $byDepot = [];
        $byCategory = [];
        $byModel = [];

        foreach ($balances as $balance) {
            $avgCost = $this->getAverageCost($balance->model_id, $balance->location_type, $balance->location_id);
            $value = $balance->quantity_on_hand * $avgCost;

            $totalValue += $value;
            $totalQuantity += $balance->quantity_on_hand;

            // By Depot
            if ($balance->location_type === 'depot') {
                $depotName = $balance->depot ? $balance->depot->depot_name : 'Unknown';
                if (!isset($byDepot[$depotName])) {
                    $byDepot[$depotName] = [
                        'depot_name' => $depotName,
                        'quantity' => 0,
                        'value' => 0,
                    ];
                }
                $byDepot[$depotName]['quantity'] += $balance->quantity_on_hand;
                $byDepot[$depotName]['value'] += $value;
            }

            // By Category
            $categoryName = $balance->model->category->category_name ?? 'Uncategorized';
            if (!isset($byCategory[$categoryName])) {
                $byCategory[$categoryName] = [
                    'category_name' => $categoryName,
                    'quantity' => 0,
                    'value' => 0,
                ];
            }
            $byCategory[$categoryName]['quantity'] += $balance->quantity_on_hand;
            $byCategory[$categoryName]['value'] += $value;

            // By Model
            $modelKey = $balance->model_id;
            if (!isset($byModel[$modelKey])) {
                $byModel[$modelKey] = [
                    'model_id' => $balance->model_id,
                    'model_name' => $balance->model->model_name,
                    'category_name' => $balance->model->category->category_name ?? 'N/A',
                    'quantity' => 0,
                    'avg_cost' => 0,
                    'value' => 0,
                ];
            }
            $byModel[$modelKey]['quantity'] += $balance->quantity_on_hand;
            $byModel[$modelKey]['value'] += $value;
            $byModel[$modelKey]['avg_cost'] = $byModel[$modelKey]['value'] / $byModel[$modelKey]['quantity'];
        }

        return [
            'total_value' => $totalValue,
            'total_quantity' => $totalQuantity,
            'by_depot' => array_values($byDepot),
            'by_category' => array_values($byCategory),
            'by_model' => array_values($byModel),
            'summary_date' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get detailed valuation by location
     *
     * @param array $filters
     * @return Collection
     */
    public function getDetailedValuation(array $filters = []): Collection
    {
        $query = StockBalance::with(['model.category', 'depot'])
            ->where('quantity_on_hand', '>', 0);

        // Apply filters
        if (!empty($filters['depot_id'])) {
            $query->where('location_type', 'depot')
                  ->where('location_id', $filters['depot_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('model', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['location_type'])) {
            $query->where('location_type', $filters['location_type']);
        }

        $balances = $query->get();

        return $balances->map(function ($balance) {
            $avgCost = $this->getAverageCost($balance->model_id, $balance->location_type, $balance->location_id);
            $totalValue = $balance->quantity_on_hand * $avgCost;

            return [
                'location_type' => ucfirst($balance->location_type),
                'location_name' => $this->getLocationName($balance->location_type, $balance->location_id),
                'model_name' => $balance->model->model_name,
                'category_name' => $balance->model->category->category_name ?? 'N/A',
                'quantity_on_hand' => $balance->quantity_on_hand,
                'average_cost' => $avgCost,
                'total_value' => $totalValue,
                'last_movement_date' => $balance->last_movement_date,
            ];
        });
    }

    /**
     * Get movement value tracking
     *
     * @param array $filters
     * @return array
     */
    public function getMovementValueTracking(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

        // Cost of Goods Issued
        $issued = $this->getMovementValue(StockLedger::TYPE_ISSUE_TO_TECH, $startDate, $endDate, $filters);

        // Cost of Goods Installed
        $installed = $this->getMovementValue(StockLedger::TYPE_INSTALL, $startDate, $endDate, $filters);

        // Wastage Value
        $wastage = $this->getMovementValue(StockLedger::TYPE_WASTAGE, $startDate, $endDate, $filters);

        // Returned to Vendor Value
        $returnedToVendor = $this->getMovementValue(StockLedger::TYPE_RETURN_TO_VENDOR, $startDate, $endDate, $filters);

        // GRN In Value
        $grnIn = $this->getMovementValue(StockLedger::TYPE_GRN_IN, $startDate, $endDate, $filters);

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'grn_in' => [
                'quantity' => $grnIn['quantity'],
                'value' => $grnIn['value'],
            ],
            'issued' => [
                'quantity' => $issued['quantity'],
                'value' => $issued['value'],
            ],
            'installed' => [
                'quantity' => $installed['quantity'],
                'value' => $installed['value'],
            ],
            'wastage' => [
                'quantity' => $wastage['quantity'],
                'value' => $wastage['value'],
            ],
            'returned_to_vendor' => [
                'quantity' => $returnedToVendor['quantity'],
                'value' => $returnedToVendor['value'],
            ],
            'total_out_value' => $issued['value'] + $installed['value'] + $wastage['value'] + $returnedToVendor['value'],
        ];
    }

    /**
     * Get movement value for a specific type
     *
     * @param string $movementType
     * @param string $startDate
     * @param string $endDate
     * @param array $filters
     * @return array
     */
    protected function getMovementValue(string $movementType, string $startDate, string $endDate, array $filters = []): array
    {
        $query = StockLedger::where('transaction_type', $movementType)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNull('reversed_at'); // Only active movements

        // Apply filters
        if (!empty($filters['depot_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('from_location_type', 'depot')
                  ->where('from_location_id', $filters['depot_id'])
                  ->orWhere('to_location_type', 'depot')
                  ->where('to_location_id', $filters['depot_id']);
            });
        }

        if (!empty($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('model', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        $movements = $query->get();

        $totalQuantity = $movements->sum(function ($m) {
            return abs($m->quantity);
        });

        $totalValue = $movements->sum(function ($m) {
            return abs($m->total_cost ?? 0);
        });

        return [
            'quantity' => $totalQuantity,
            'value' => $totalValue,
        ];
    }

    /**
     * Calculate average cost for a model at a location
     * Uses weighted average method from GRN entries
     *
     * @param int $modelId
     * @param string $locationType
     * @param int $locationId
     * @return float
     */
    public function getAverageCost(int $modelId, string $locationType, int $locationId): float
    {
        // Get all GRN entries for this model
        $grnEntries = StockLedger::where('model_id', $modelId)
            ->where('transaction_type', StockLedger::TYPE_GRN_IN)
            ->where('to_location_type', $locationType)
            ->where('to_location_id', $locationId)
            ->whereNotNull('unit_cost')
            ->where('unit_cost', '>', 0)
            ->whereNull('reversed_at')
            ->select('quantity', 'unit_cost', 'total_cost')
            ->get();

        if ($grnEntries->isEmpty()) {
            // Fallback: Get from inventory_serials purchase_price
            $avgFromSerials = InventorySerial::where('model_id', $modelId)
                ->where('current_location_type', $locationType)
                ->where('current_location_id', $locationId)
                ->where('current_status', '!=', 'wasted')
                ->whereNotNull('purchase_price')
                ->where('purchase_price', '>', 0)
                ->avg('purchase_price');

            return $avgFromSerials ?? 0;
        }

        // Calculate weighted average
        $totalQuantity = $grnEntries->sum('quantity');
        $totalValue = $grnEntries->sum('total_cost');

        if ($totalQuantity <= 0) {
            return 0;
        }

        return $totalValue / $totalQuantity;
    }

    /**
     * Get latest cost from most recent GRN
     *
     * @param int $modelId
     * @return float
     */
    public function getLatestCost(int $modelId): float
    {
        $latestGrn = StockLedger::where('model_id', $modelId)
            ->where('transaction_type', StockLedger::TYPE_GRN_IN)
            ->whereNotNull('unit_cost')
            ->where('unit_cost', '>', 0)
            ->whereNull('reversed_at')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $latestGrn ? $latestGrn->unit_cost : 0;
    }

    /**
     * Update cost from GRN
     * Called when a GRN is posted
     *
     * @param int $grnId
     * @return void
     */
    public function updateCostFromGRN(int $grnId): void
    {
        $grn = Grn::with('lines')->find($grnId);
        
        if (!$grn || $grn->status !== 'posted') {
            return;
        }

        foreach ($grn->lines as $line) {
            if ($line->unit_cost && $line->unit_cost > 0) {
                // Update inventory_serials that came from this GRN
                InventorySerial::where('grn_id', $grnId)
                    ->where('model_id', $line->model_id)
                    ->update([
                        'purchase_price' => $line->unit_cost,
                    ]);
            }
        }
    }

    /**
     * Get stock aging report
     * Shows how long items have been in stock
     *
     * @param array $filters
     * @return Collection
     */
    public function getStockAging(array $filters = []): Collection
    {
        $query = InventorySerial::with(['model.category', 'depot'])
            ->where('current_status', 'in_stock');

        // Apply filters
        if (!empty($filters['depot_id'])) {
            $query->where('current_location_type', 'depot')
                  ->where('current_location_id', $filters['depot_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('model', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        $serials = $query->get();

        return $serials->map(function ($serial) {
            $daysInStock = $serial->grn_date ? Carbon::parse($serial->grn_date)->diffInDays(now()) : 0;
            $agingBracket = $this->getAgingBracket($daysInStock);

            return [
                'serial_no' => $serial->serial_no,
                'model_name' => $serial->model->model_name,
                'category_name' => $serial->model->category->category_name ?? 'N/A',
                'location_name' => $serial->current_location_name,
                'grn_date' => $serial->grn_date,
                'days_in_stock' => $daysInStock,
                'aging_bracket' => $agingBracket,
                'purchase_price' => $serial->purchase_price,
            ];
        });
    }

    /**
     * Determine aging bracket
     *
     * @param int $days
     * @return string
     */
    protected function getAgingBracket(int $days): string
    {
        if ($days <= 30) return '0-30 days';
        if ($days <= 60) return '31-60 days';
        if ($days <= 90) return '61-90 days';
        if ($days <= 180) return '91-180 days';
        if ($days <= 365) return '181-365 days';
        return 'Over 1 year';
    }

    /**
     * Get location name
     *
     * @param string $type
     * @param int $id
     * @return string
     */
    protected function getLocationName(string $type, int $id): string
    {
        switch ($type) {
            case 'depot':
                $depot = Depot::find($id);
                return $depot ? $depot->depot_name : "Depot #{$id}";
            case 'technician':
                $user = \App\Models\User::find($id);
                return $user ? $user->name : "Technician #{$id}";
            case 'site':
                $site = \App\Models\Site::find($id);
                return $site ? $site->site_name : "Site #{$id}";
            case 'vendor':
                $vendor = \App\Models\Vendor::find($id);
                return $vendor ? $vendor->vendor_name : "Vendor #{$id}";
            default:
                return "Unknown";
        }
    }

    /**
     * Get valuation summary by category
     *
     * @param array $filters
     * @return Collection
     */
    public function getValuationByCategory(array $filters = []): Collection
    {
        $summary = $this->getValuationSummary($filters);
        
        return collect($summary['by_category'])->sortByDesc('value');
    }

    /**
     * Get valuation summary by depot
     *
     * @param array $filters
     * @return Collection
     */
    public function getValuationByDepot(array $filters = []): Collection
    {
        $summary = $this->getValuationSummary($filters);
        
        return collect($summary['by_depot'])->sortByDesc('value');
    }

    /**
     * Get top valued models
     *
     * @param int $limit
     * @param array $filters
     * @return Collection
     */
    public function getTopValuedModels(int $limit = 10, array $filters = []): Collection
    {
        $summary = $this->getValuationSummary($filters);
        
        return collect($summary['by_model'])
            ->sortByDesc('value')
            ->take($limit)
            ->values();
    }
}
