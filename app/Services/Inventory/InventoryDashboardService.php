<?php

namespace App\Services\Inventory;

use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\InventorySerial;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class InventoryDashboardService
{
    /**
     * Default low stock threshold
     * Since terminal_models table does not have min_stock_level column,
     * we use a system-wide default. This can be made configurable via system_settings.
     */
    const DEFAULT_LOW_STOCK_THRESHOLD = 5;

    // =========================================================================
    // WIDGET 1: STOCK SUMMARY
    // =========================================================================

    /**
     * Get stock summary by category
     * Returns total items grouped by terminal category
     */
    public function getStockByCategory(?string $locationType = null, ?int $locationId = null): Collection
    {
        $query = DB::table('stock_balances')
            ->join('terminal_models', 'stock_balances.model_id', '=', 'terminal_models.id')
            ->join('terminal_categories', 'terminal_models.category_id', '=', 'terminal_categories.id')
            ->select(
                'terminal_categories.id as category_id',
                'terminal_categories.category_name',
                'terminal_categories.category_code',
                'terminal_categories.category_type',
                DB::raw('COUNT(DISTINCT stock_balances.model_id) as unique_models'),
                DB::raw('SUM(stock_balances.quantity_on_hand) as total_on_hand'),
                DB::raw('SUM(stock_balances.quantity_reserved) as total_reserved'),
                DB::raw('SUM(stock_balances.quantity_available) as total_available')
            )
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->groupBy(
                'terminal_categories.id',
                'terminal_categories.category_name',
                'terminal_categories.category_code',
                'terminal_categories.category_type'
            )
            ->orderBy('terminal_categories.sort_order')
            ->orderBy('terminal_categories.category_name');

        $this->applyLocationFilter($query, $locationType, $locationId);

        return $query->get();
    }

    /**
     * Get stock summary by status (from inventory_serials)
     */
    public function getStockByStatus(?string $locationType = null, ?int $locationId = null): Collection
    {
        $query = DB::table('inventory_serials')
            ->select(
                'current_status',
                DB::raw('COUNT(*) as total_count')
            )
            ->whereNull('deleted_at')
            ->groupBy('current_status')
            ->orderBy('total_count', 'desc');

        if ($locationType) {
            $query->where('current_location_type', $locationType);
        }
        if ($locationId) {
            $query->where('current_location_id', $locationId);
        }

        return $query->get()->map(function ($item) {
            $item->status_label = InventorySerial::STATUS_OPTIONS[$item->current_status] ?? ucfirst(str_replace('_', ' ', $item->current_status));
            $item->status_color = InventorySerial::STATUS_BADGES[$item->current_status] ?? 'secondary';
            return $item;
        });
    }

    /**
     * Get stock summary by depot
     */
    public function getStockByDepot(): Collection
    {
        return DB::table('stock_balances')
            ->join('depots', function ($join) {
                $join->on('stock_balances.location_id', '=', 'depots.id')
                    ->where('stock_balances.location_type', '=', 'depot');
            })
            ->select(
                'depots.id as depot_id',
                'depots.depot_name',
                'depots.depot_code',
                DB::raw('COUNT(DISTINCT stock_balances.model_id) as unique_models'),
                DB::raw('SUM(stock_balances.quantity_on_hand) as total_on_hand'),
                DB::raw('SUM(stock_balances.quantity_reserved) as total_reserved'),
                DB::raw('SUM(stock_balances.quantity_available) as total_available')
            )
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->groupBy('depots.id', 'depots.depot_name', 'depots.depot_code')
            ->orderBy('depots.depot_name')
            ->get();
    }

    /**
     * Get overall stock totals
     */
    public function getOverallTotals(?string $locationType = null, ?int $locationId = null): object
    {
        $query = DB::table('stock_balances')
            ->select(
                DB::raw('COUNT(DISTINCT model_id) as unique_models'),
                DB::raw('SUM(quantity_on_hand) as total_on_hand'),
                DB::raw('SUM(quantity_reserved) as total_reserved'),
                DB::raw('SUM(quantity_available) as total_available'),
                DB::raw('COUNT(*) as total_records')
            )
            ->where('quantity_on_hand', '>', 0);

        $this->applyLocationFilter($query, $locationType, $locationId);

        $result = $query->first();

        // Also get total serial count
        $serialQuery = DB::table('inventory_serials')
            ->whereNull('deleted_at');

        if ($locationType) {
            $serialQuery->where('current_location_type', $locationType);
        }
        if ($locationId) {
            $serialQuery->where('current_location_id', $locationId);
        }

        $result->total_serials = $serialQuery->count();

        return $result;
    }

    /**
     * Get technician's stock summary
     */
    public function getTechnicianStockSummary(int $technicianId): object
    {
        $balances = StockBalance::where('location_type', 'technician')
            ->where('location_id', $technicianId)
            ->get();

        $serials = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereNull('deleted_at')
            ->get();

        return (object) [
            'total_items' => $balances->sum('quantity_on_hand'),
            'total_available' => $balances->sum('quantity_available'),
            'total_reserved' => $balances->sum('quantity_reserved'),
            'unique_models' => $balances->pluck('model_id')->unique()->count(),
            'total_serials' => $serials->count(),
            'serials_by_status' => $serials->groupBy('current_status')->map(fn($g) => $g->count()),
        ];
    }

    // =========================================================================
    // WIDGET 2: LOW STOCK ALERTS
    // =========================================================================

    /**
     * Get low stock alert items
     * Items where available quantity is at or below the default threshold
     * Note: terminal_models does NOT have min_stock_level column,
     *       so we use a system-wide default constant
     */
    public function getLowStockAlerts(?string $locationType = null, ?int $locationId = null, int $limit = 20): Collection
    {
        $threshold = self::DEFAULT_LOW_STOCK_THRESHOLD;

        $query = DB::table('stock_balances')
            ->join('terminal_models', 'stock_balances.model_id', '=', 'terminal_models.id')
            ->join('terminal_categories', 'terminal_models.category_id', '=', 'terminal_categories.id')
            ->select(
                'stock_balances.*',
                'terminal_models.model_name',
                'terminal_models.model_code',
                'terminal_categories.category_name',
                DB::raw("{$threshold} as threshold")
            )
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->where('stock_balances.quantity_available', '<=', $threshold)
            ->orderBy('stock_balances.quantity_available', 'asc')
            ->limit($limit);

        $this->applyLocationFilter($query, $locationType, $locationId);

        return $query->get()->map(function ($item) {
            $item->location_name = $this->resolveLocationName($item->location_type, $item->location_id);
            $item->severity = $item->quantity_available <= 0 ? 'critical' : 'warning';
            $item->severity_badge = $item->quantity_available <= 0
                ? '<span class="badge bg-danger">Out of Stock</span>'
                : '<span class="badge bg-warning text-dark">Low Stock</span>';
            return $item;
        });
    }

    /**
     * Get low stock counts for badge display
     */
    public function getLowStockCounts(?string $locationType = null, ?int $locationId = null): object
    {
        $threshold = self::DEFAULT_LOW_STOCK_THRESHOLD;

        $baseQuery = DB::table('stock_balances')
            ->where('quantity_on_hand', '>', 0);

        $this->applyLocationFilter($baseQuery, $locationType, $locationId);

        $totalAlerts = (clone $baseQuery)->where('quantity_available', '<=', $threshold)->count();
        $outOfStock = (clone $baseQuery)->where('quantity_available', '<=', 0)->count();
        $lowStock = (clone $baseQuery)
            ->where('quantity_available', '>', 0)
            ->where('quantity_available', '<=', $threshold)
            ->count();

        return (object) [
            'total_alerts' => $totalAlerts,
            'out_of_stock' => $outOfStock,
            'low_stock'    => $lowStock,
        ];
    }

    // =========================================================================
    // WIDGET 3: RECENT MOVEMENTS
    // =========================================================================

    /**
     * Get recent stock movements (from stock_ledger)
     */
    public function getRecentMovements(?string $locationType = null, ?int $locationId = null, int $limit = 15): Collection
    {
        $query = DB::table('stock_ledger')
            ->join('terminal_models', 'stock_ledger.model_id', '=', 'terminal_models.id')
            ->leftJoin('users', 'stock_ledger.created_by', '=', 'users.id')
            ->select(
                'stock_ledger.id',
                'stock_ledger.transaction_date',
                'stock_ledger.transaction_no',
                'stock_ledger.transaction_type',
                'stock_ledger.serial_no',
                'stock_ledger.quantity',
                'stock_ledger.from_location_type',
                'stock_ledger.from_location_id',
                'stock_ledger.to_location_type',
                'stock_ledger.to_location_id',
                'stock_ledger.remarks',
                'stock_ledger.created_at',
                'stock_ledger.is_reversed',
                'terminal_models.model_name',
                'terminal_models.model_code',
                'users.name as created_by_name'
            )
            ->orderBy('stock_ledger.created_at', 'desc')
            ->limit($limit);

        if ($locationType && $locationId) {
            $query->where(function ($q) use ($locationType, $locationId) {
                $q->where(function ($sub) use ($locationType, $locationId) {
                    $sub->where('stock_ledger.from_location_type', $locationType)
                        ->where('stock_ledger.from_location_id', $locationId);
                })->orWhere(function ($sub) use ($locationType, $locationId) {
                    $sub->where('stock_ledger.to_location_type', $locationType)
                        ->where('stock_ledger.to_location_id', $locationId);
                });
            });
        }

        return $query->get()->map(function ($item) {
            $item->type_label = StockLedger::TYPE_OPTIONS[$item->transaction_type] ?? ucfirst(str_replace('_', ' ', $item->transaction_type));
            $item->type_color = StockLedger::TYPE_COLORS[$item->transaction_type] ?? 'secondary';
            $item->type_icon = StockLedger::TYPE_ICONS[$item->transaction_type] ?? 'bi-arrow-left-right';
            $item->from_location_name = $this->resolveLocationName($item->from_location_type, $item->from_location_id);
            $item->to_location_name = $this->resolveLocationName($item->to_location_type, $item->to_location_id);
            $item->time_ago = Carbon::parse($item->created_at)->diffForHumans();
            return $item;
        });
    }

    // =========================================================================
    // WIDGET 4: STOCK AGING ANALYSIS
    // =========================================================================

    /**
     * Get stock aging analysis
     * Groups inventory serials by age brackets (0-30, 31-60, 61-90, 90+ days)
     */
    public function getStockAging(?string $locationType = null, ?int $locationId = null): array
    {
        $query = DB::table('inventory_serials')
            ->whereNull('deleted_at')
            ->whereIn('current_status', ['in_stock', 'reserved', 'issued_to_tech']);

        if ($locationType) {
            $query->where('current_location_type', $locationType);
        }
        if ($locationId) {
            $query->where('current_location_id', $locationId);
        }

        $serials = $query->get();
        $now = Carbon::now();

        // Initialize age brackets
        $brackets = [
            [
                'label' => '0-30 days',
                'min'   => 0,
                'max'   => 30,
                'count' => 0,
                'color' => 'rgba(25, 135, 84, 0.7)', // Green
            ],
            [
                'label' => '31-60 days',
                'min'   => 31,
                'max'   => 60,
                'count' => 0,
                'color' => 'rgba(13, 110, 253, 0.7)', // Blue
            ],
            [
                'label' => '61-90 days',
                'min'   => 61,
                'max'   => 90,
                'count' => 0,
                'color' => 'rgba(255, 193, 7, 0.7)', // Yellow
            ],
            [
                'label' => '90+ days',
                'min'   => 91,
                'max'   => 99999,
                'count' => 0,
                'color' => 'rgba(220, 53, 69, 0.7)', // Red
            ],
        ];

        // Count serials in each bracket
        foreach ($serials as $serial) {
            $receiveDate = $serial->grn_date ? Carbon::parse($serial->grn_date) : null;

            if (!$receiveDate) {
                continue; // Skip if no GRN date
            }

            $ageInDays = $receiveDate->diffInDays($now);

            foreach ($brackets as &$bracket) {
                if ($ageInDays >= $bracket['min'] && $ageInDays <= $bracket['max']) {
                    $bracket['count']++;
                    break;
                }
            }
        }

        return [
            'brackets'       => $brackets,
            'total_in_stock' => $serials->count(),
        ];
    }

    /**
     * Get aged stock items (items older than threshold days)
     * Returns detailed list of items exceeding the age threshold
     */
    public function getAgedStockItems(int $thresholdDays = 90, ?string $locationType = null, ?int $locationId = null, int $limit = 20): Collection
    {
        $cutoffDate = Carbon::now()->subDays($thresholdDays)->format('Y-m-d');

        $query = DB::table('inventory_serials')
            ->join('terminal_models', 'inventory_serials.model_id', '=', 'terminal_models.id')
            ->join('terminal_categories', 'terminal_models.category_id', '=', 'terminal_categories.id')
            ->select(
                'inventory_serials.id',
                'inventory_serials.serial_no',
                'inventory_serials.grn_date',
                'inventory_serials.current_status',
                'inventory_serials.current_location_type',
                'inventory_serials.current_location_id',
                'terminal_models.model_name',
                'terminal_models.model_code',
                'terminal_categories.category_name',
                DB::raw("DATEDIFF(NOW(), inventory_serials.grn_date) as age_days")
            )
            ->whereNull('inventory_serials.deleted_at')
            ->whereNotNull('inventory_serials.grn_date')
            ->where('inventory_serials.grn_date', '<=', $cutoffDate)
            ->whereIn('inventory_serials.current_status', ['in_stock', 'reserved', 'issued_to_tech'])
            ->orderBy('inventory_serials.grn_date', 'asc')
            ->limit($limit);

        if ($locationType) {
            $query->where('inventory_serials.current_location_type', $locationType);
        }
        if ($locationId) {
            $query->where('inventory_serials.current_location_id', $locationId);
        }

        return $query->get()->map(function ($item) {
            $item->location_name = $this->resolveLocationName($item->current_location_type, $item->current_location_id);
            $item->status_label = InventorySerial::STATUS_OPTIONS[$item->current_status] ?? ucfirst(str_replace('_', ' ', $item->current_status));
            $item->status_badge = InventorySerial::STATUS_BADGES[$item->current_status] ?? 'secondary';
            $item->grn_date_formatted = $item->grn_date ? Carbon::parse($item->grn_date)->format('d M Y') : '-';

            // Age severity classification
            if ($item->age_days >= 180) {
                $item->age_severity = 'critical';
                $item->age_badge = '<span class="badge bg-danger">' . $item->age_days . ' days</span>';
            } elseif ($item->age_days >= 120) {
                $item->age_severity = 'warning';
                $item->age_badge = '<span class="badge bg-warning text-dark">' . $item->age_days . ' days</span>';
            } else {
                $item->age_severity = 'normal';
                $item->age_badge = '<span class="badge bg-info">' . $item->age_days . ' days</span>';
            }

            return $item;
        });
    }

    // =========================================================================
    // WIDGET 5: TOP MODELS BY QUANTITY
    // =========================================================================

    /**
     * Get top terminal models by quantity on hand
     */
    public function getTopModelsByQuantity(int $limit = 10, ?string $locationType = null, ?int $locationId = null): Collection
    {
        $query = DB::table('stock_balances')
            ->join('terminal_models', 'stock_balances.model_id', '=', 'terminal_models.id')
            ->join('terminal_categories', 'terminal_models.category_id', '=', 'terminal_categories.id')
            ->select(
                'terminal_models.id as model_id',
                'terminal_models.model_name',
                'terminal_models.model_code',
                'terminal_models.brand',
                'terminal_categories.category_name',
                DB::raw('SUM(stock_balances.quantity_on_hand) as total_on_hand'),
                DB::raw('SUM(stock_balances.quantity_available) as total_available'),
                DB::raw('COUNT(DISTINCT CONCAT(stock_balances.location_type, "-", stock_balances.location_id)) as location_count')
            )
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->groupBy(
                'terminal_models.id',
                'terminal_models.model_name',
                'terminal_models.model_code',
                'terminal_models.brand',
                'terminal_categories.category_name'
            )
            ->orderBy('total_on_hand', 'desc')
            ->limit($limit);

        $this->applyLocationFilter($query, $locationType, $locationId);

        return $query->get();
    }

    // =========================================================================
    // WIDGET 6: MOVEMENT SUMMARY
    // =========================================================================

    /**
     * Get movement summary for the last N days
     */
    public function getMovementSummary(int $days = 7, ?string $locationType = null, ?int $locationId = null): object
    {
        $startDate = Carbon::now()->subDays($days)->format('Y-m-d');

        $baseQuery = DB::table('stock_ledger')
            ->where('transaction_date', '>=', $startDate)
            ->where('is_reversed', false);

        if ($locationType && $locationId) {
            $inbound = (clone $baseQuery)
                ->where('to_location_type', $locationType)
                ->where('to_location_id', $locationId)
                ->sum('quantity');

            $outbound = (clone $baseQuery)
                ->where('from_location_type', $locationType)
                ->where('from_location_id', $locationId)
                ->sum('quantity');
        } else {
            $inbound = (clone $baseQuery)
                ->where('quantity', '>', 0)
                ->sum('quantity');

            $outbound = (clone $baseQuery)
                ->where('quantity', '<', 0)
                ->sum(DB::raw('ABS(quantity)'));
        }

        $totalTransactions = (clone $baseQuery)->count();

        // Get unique models moved
        $uniqueModels = (clone $baseQuery)
            ->distinct('model_id')
            ->count('model_id');

        // Get total serial count
        $totalSerials = DB::table('inventory_serials')
            ->whereNull('deleted_at')
            ->count();

        $totals = (object) [
            'inbound'            => (float) $inbound,
            'outbound'           => (float) $outbound,
            'net_movement'       => (float) ($inbound - $outbound),
            'total_transactions' => $totalTransactions,
            'unique_models'      => $uniqueModels,
            'days'               => $days,
        ];

        $totals->total_serials = $totalSerials;

        return $totals;
    }

    // =========================================================================
    // CHART DATA METHODS
    // =========================================================================

    /**
     * Get movement trend data for chart (last N days)
     */
    public function getMovementTrend(int $days = 7, ?string $locationType = null, ?int $locationId = null): array
    {
        $labels = [];
        $inbound = [];
        $outbound = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');
            $dateStr = $date->format('Y-m-d');

            $baseQuery = DB::table('stock_ledger')
                ->where('transaction_date', $dateStr)
                ->where('is_reversed', false);

            if ($locationType && $locationId) {
                $inQuery = (clone $baseQuery)
                    ->where('to_location_type', $locationType)
                    ->where('to_location_id', $locationId)
                    ->sum('quantity');

                $outQuery = (clone $baseQuery)
                    ->where('from_location_type', $locationType)
                    ->where('from_location_id', $locationId)
                    ->sum('quantity');
            } else {
                $inQuery = (clone $baseQuery)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                $outQuery = (clone $baseQuery)
                    ->where('quantity', '<', 0)
                    ->sum(DB::raw('ABS(quantity)'));
            }

            $inbound[] = (float) $inQuery;
            $outbound[] = (float) $outQuery;
        }

        return [
            'labels'   => $labels,
            'inbound'  => $inbound,
            'outbound' => $outbound,
        ];
    }

    /**
     * Get category distribution data for chart
     */
    public function getCategoryDistribution(?string $locationType = null, ?int $locationId = null): array
    {
        $data = $this->getStockByCategory($locationType, $locationId);

        $colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#0dcaf0', '#6c757d', '#d63384'];

        return [
            'labels' => $data->pluck('category_name')->toArray(),
            'data'   => $data->pluck('total_on_hand')->map(fn($v) => (float) $v)->toArray(),
            'colors' => array_slice($colors, 0, $data->count()),
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Apply location filter to query
     */
    protected function applyLocationFilter($query, ?string $locationType, ?int $locationId): void
    {
        if ($locationType) {
            $query->where('stock_balances.location_type', $locationType);
        }
        if ($locationId) {
            $query->where('stock_balances.location_id', $locationId);
        }
    }

    /**
     * Resolve location name from type and ID
     */
    public function resolveLocationName(?string $type, ?int $id): string
    {
        if (!$type || !$id) {
            return '-';
        }

        return match ($type) {
            'depot' => Depot::find($id)?->depot_name ?? "Depot #{$id}",
            'technician' => User::find($id)?->name ?? "Technician #{$id}",
            default => ucfirst($type) . " #{$id}",
        };
    }

    /**
     * Get supervisor's team-scoped low stock alerts
     * Uses default threshold since terminal_models does not have min_stock_level
     */
    public function getTeamLowStockAlerts(array $technicianIds, int $limit = 20): Collection
    {
        $threshold = self::DEFAULT_LOW_STOCK_THRESHOLD;

        return DB::table('stock_balances')
            ->join('terminal_models', 'stock_balances.model_id', '=', 'terminal_models.id')
            ->join('terminal_categories', 'terminal_models.category_id', '=', 'terminal_categories.id')
            ->join('users', function ($join) {
                $join->on('stock_balances.location_id', '=', 'users.id')
                    ->where('stock_balances.location_type', '=', 'technician');
            })
            ->select(
                'stock_balances.*',
                'terminal_models.model_name',
                'terminal_models.model_code',
                'terminal_categories.category_name',
                'users.name as technician_name',
                DB::raw("{$threshold} as threshold")
            )
            ->whereIn('stock_balances.location_id', $technicianIds)
            ->where('stock_balances.location_type', 'technician')
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->where('stock_balances.quantity_available', '<=', $threshold)
            ->orderBy('stock_balances.quantity_available', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->severity = $item->quantity_available <= 0 ? 'critical' : 'warning';
                $item->severity_badge = $item->quantity_available <= 0
                    ? '<span class="badge bg-danger">Out of Stock</span>'
                    : '<span class="badge bg-warning text-dark">Low Stock</span>';
                $item->location_name = $item->technician_name;
                return $item;
            });
    }

    /**
     * Get team recent movements for supervisor
     */
    public function getTeamRecentMovements(array $technicianIds, int $limit = 15): Collection
    {
        return DB::table('stock_ledger')
            ->join('terminal_models', 'stock_ledger.model_id', '=', 'terminal_models.id')
            ->leftJoin('users', 'stock_ledger.created_by', '=', 'users.id')
            ->select(
                'stock_ledger.id',
                'stock_ledger.transaction_date',
                'stock_ledger.transaction_no',
                'stock_ledger.transaction_type',
                'stock_ledger.serial_no',
                'stock_ledger.quantity',
                'stock_ledger.from_location_type',
                'stock_ledger.from_location_id',
                'stock_ledger.to_location_type',
                'stock_ledger.to_location_id',
                'stock_ledger.remarks',
                'stock_ledger.created_at',
                'stock_ledger.is_reversed',
                'terminal_models.model_name',
                'terminal_models.model_code',
                'users.name as created_by_name'
            )
            ->where(function ($q) use ($technicianIds) {
                $q->where(function ($sub) use ($technicianIds) {
                    $sub->where('stock_ledger.from_location_type', 'technician')
                        ->whereIn('stock_ledger.from_location_id', $technicianIds);
                })->orWhere(function ($sub) use ($technicianIds) {
                    $sub->where('stock_ledger.to_location_type', 'technician')
                        ->whereIn('stock_ledger.to_location_id', $technicianIds);
                });
            })
            ->orderBy('stock_ledger.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->type_label = StockLedger::TYPE_OPTIONS[$item->transaction_type] ?? ucfirst(str_replace('_', ' ', $item->transaction_type));
                $item->type_color = StockLedger::TYPE_COLORS[$item->transaction_type] ?? 'secondary';
                $item->type_icon = StockLedger::TYPE_ICONS[$item->transaction_type] ?? 'bi-arrow-left-right';
                $item->from_location_name = $this->resolveLocationName($item->from_location_type, $item->from_location_id);
                $item->to_location_name = $this->resolveLocationName($item->to_location_type, $item->to_location_id);
                $item->time_ago = Carbon::parse($item->created_at)->diffForHumans();
                return $item;
            });
    }
}
