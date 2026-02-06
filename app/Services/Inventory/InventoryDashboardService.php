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

        $baseQuery = DB::table('stock_balances');

        if ($locationType) {
            $baseQuery->where('stock_balances.location_type', $locationType);
        }
        if ($locationId) {
            $baseQuery->where('stock_balances.location_id', $locationId);
        }

        $lowStock = (clone $baseQuery)
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->where('stock_balances.quantity_available', '<=', $threshold)
            ->where('stock_balances.quantity_available', '>', 0)
            ->count();

        $outOfStock = (clone $baseQuery)
            ->where('stock_balances.quantity_available', '<=', 0)
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->count();

        return (object) [
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'total_alerts' => $lowStock + $outOfStock,
        ];
    }

    // =========================================================================
    // WIDGET 3: RECENT MOVEMENTS
    // =========================================================================

    /**
     * Get recent stock movements
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
                'stock_ledger.reference_type',
                'stock_ledger.reference_id',
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

    /**
     * Get movement summary for a date range
     */
    public function getMovementSummary(int $days = 7, ?string $locationType = null, ?int $locationId = null): Collection
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $query = DB::table('stock_ledger')
            ->select(
                'transaction_type',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(ABS(quantity)) as total_quantity')
            )
            ->where('transaction_date', '>=', $startDate->toDateString())
            ->where('is_reversed', false)
            ->groupBy('transaction_type')
            ->orderBy('total_count', 'desc');

        if ($locationType && $locationId) {
            $query->where(function ($q) use ($locationType, $locationId) {
                $q->where(function ($sub) use ($locationType, $locationId) {
                    $sub->where('from_location_type', $locationType)
                        ->where('from_location_id', $locationId);
                })->orWhere(function ($sub) use ($locationType, $locationId) {
                    $sub->where('to_location_type', $locationType)
                        ->where('to_location_id', $locationId);
                });
            });
        }

        return $query->get()->map(function ($item) {
            $item->type_label = StockLedger::TYPE_OPTIONS[$item->transaction_type] ?? ucfirst(str_replace('_', ' ', $item->transaction_type));
            $item->type_color = StockLedger::TYPE_COLORS[$item->transaction_type] ?? 'secondary';
            $item->type_icon = StockLedger::TYPE_ICONS[$item->transaction_type] ?? 'bi-arrow-left-right';
            return $item;
        });
    }

    // =========================================================================
    // WIDGET 4: STOCK AGING
    // =========================================================================

    /**
     * Get stock aging data (items in stock > X days)
     * Based on inventory_serials.grn_date or created_at
     */
    public function getStockAging(?string $locationType = null, ?int $locationId = null): object
    {
        $now = Carbon::now();

        $baseQuery = DB::table('inventory_serials')
            ->where('current_status', 'in_stock')
            ->whereNull('deleted_at');

        if ($locationType) {
            $baseQuery->where('current_location_type', $locationType);
        }
        if ($locationId) {
            $baseQuery->where('current_location_id', $locationId);
        }

        // Define aging brackets (days)
        $brackets = [
            ['label' => '0-30 Days',   'min' => 0,   'max' => 30,  'color' => '#198754'],
            ['label' => '31-60 Days',  'min' => 31,  'max' => 60,  'color' => '#0dcaf0'],
            ['label' => '61-90 Days',  'min' => 61,  'max' => 90,  'color' => '#ffc107'],
            ['label' => '91-180 Days', 'min' => 91,  'max' => 180, 'color' => '#fd7e14'],
            ['label' => '180+ Days',   'min' => 181, 'max' => 99999, 'color' => '#dc3545'],
        ];

        $results = [];
        foreach ($brackets as $bracket) {
            $fromDate = $now->copy()->subDays($bracket['max'])->startOfDay();
            $toDate   = $now->copy()->subDays($bracket['min'])->endOfDay();

            $count = (clone $baseQuery)
                ->where(function ($q) use ($fromDate, $toDate) {
                    $q->whereBetween('grn_date', [$fromDate->toDateString(), $toDate->toDateString()])
                      ->orWhere(function ($sub) use ($fromDate, $toDate) {
                          $sub->whereNull('grn_date')
                              ->whereBetween('created_at', [$fromDate, $toDate]);
                      });
                })
                ->count();

            $results[] = (object) [
                'label' => $bracket['label'],
                'count' => $count,
                'color' => $bracket['color'],
                'min_days' => $bracket['min'],
                'max_days' => $bracket['max'],
            ];
        }

        $totalInStock = (clone $baseQuery)->count();

        return (object) [
            'brackets' => collect($results),
            'total_in_stock' => $totalInStock,
        ];
    }

    /**
     * Get aged stock items (older than X days)
     */
    public function getAgedStockItems(int $daysThreshold = 90, ?string $locationType = null, ?int $locationId = null, int $limit = 20): Collection
    {
        $cutoffDate = Carbon::now()->subDays($daysThreshold);

        $query = DB::table('inventory_serials')
            ->join('terminal_models', 'inventory_serials.model_id', '=', 'terminal_models.id')
            ->join('terminal_categories', 'terminal_models.category_id', '=', 'terminal_categories.id')
            ->select(
                'inventory_serials.id',
                'inventory_serials.serial_no',
                'inventory_serials.grn_date',
                'inventory_serials.created_at',
                'inventory_serials.current_location_type',
                'inventory_serials.current_location_id',
                'inventory_serials.purchase_price',
                'terminal_models.model_name',
                'terminal_models.model_code',
                'terminal_categories.category_name'
            )
            ->where('inventory_serials.current_status', 'in_stock')
            ->whereNull('inventory_serials.deleted_at')
            ->where(function ($q) use ($cutoffDate) {
                $q->where('inventory_serials.grn_date', '<=', $cutoffDate->toDateString())
                  ->orWhere(function ($sub) use ($cutoffDate) {
                      $sub->whereNull('inventory_serials.grn_date')
                          ->where('inventory_serials.created_at', '<=', $cutoffDate);
                  });
            })
            ->orderByRaw('COALESCE(inventory_serials.grn_date, DATE(inventory_serials.created_at)) ASC')
            ->limit($limit);

        if ($locationType) {
            $query->where('inventory_serials.current_location_type', $locationType);
        }
        if ($locationId) {
            $query->where('inventory_serials.current_location_id', $locationId);
        }

        return $query->get()->map(function ($item) {
            $receivedDate = $item->grn_date ?? Carbon::parse($item->created_at)->toDateString();
            $item->days_in_stock = Carbon::parse($receivedDate)->diffInDays(Carbon::now());
            $item->location_name = $this->resolveLocationName($item->current_location_type, $item->current_location_id);
            return $item;
        });
    }

    // =========================================================================
    // WIDGET 5: TOP MODELS BY QUANTITY
    // =========================================================================

    /**
     * Get top models by total quantity on hand
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
                DB::raw('SUM(stock_balances.quantity_reserved) as total_reserved'),
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
    // SUPERVISOR-SPECIFIC METHODS
    // =========================================================================

    /**
     * Get team technician IDs for a supervisor
     */
    public function getTeamTechnicianIds(int $supervisorId): array
    {
        return User::where('supervisor_id', $supervisorId)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get stock by technician (for supervisor view)
     */
    public function getStockByTechnician(array $technicianIds): Collection
    {
        return DB::table('stock_balances')
            ->join('users', function ($join) {
                $join->on('stock_balances.location_id', '=', 'users.id')
                    ->where('stock_balances.location_type', '=', 'technician');
            })
            ->select(
                'users.id as technician_id',
                'users.name as technician_name',
                DB::raw('COUNT(DISTINCT stock_balances.model_id) as unique_models'),
                DB::raw('SUM(stock_balances.quantity_on_hand) as total_on_hand'),
                DB::raw('SUM(stock_balances.quantity_available) as total_available')
            )
            ->whereIn('stock_balances.location_id', $technicianIds)
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();
    }

    // =========================================================================
    // TECHNICIAN-SPECIFIC METHODS
    // =========================================================================

    /**
     * Get technician's personal stock summary
     */
    public function getTechnicianStockSummary(int $technicianId): object
    {
        $totals = DB::table('stock_balances')
            ->select(
                DB::raw('COUNT(DISTINCT model_id) as unique_models'),
                DB::raw('SUM(quantity_on_hand) as total_on_hand'),
                DB::raw('SUM(quantity_available) as total_available')
            )
            ->where('location_type', 'technician')
            ->where('location_id', $technicianId)
            ->where('quantity_on_hand', '>', 0)
            ->first();

        $totalSerials = DB::table('inventory_serials')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('current_status', 'issued_to_tech')
            ->whereNull('deleted_at')
            ->count();

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
