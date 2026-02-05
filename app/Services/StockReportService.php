<?php

namespace App\Services;

use App\Models\StockLedger;
use App\Models\InventorySerial;
use App\Models\TerminalModel;
use App\Models\Depot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockReportService
{
    /**
     * Get movement report by date range with filters
     */
    public function getMovementReport(array $filters)
    {
        $query = StockLedger::with(['model.category', 'serial', 'createdBy'])
            ->notReversed();

        // Date range filter
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->byDateRange($filters['from_date'], $filters['to_date']);
        } elseif (!empty($filters['from_date'])) {
            $query->where('transaction_date', '>=', $filters['from_date']);
        } elseif (!empty($filters['to_date'])) {
            $query->where('transaction_date', '<=', $filters['to_date']);
        }

        // Location filter (from or to)
        if (!empty($filters['location_type']) && !empty($filters['location_id'])) {
            $query->byLocation($filters['location_type'], $filters['location_id']);
        }

        // Model filter
        if (!empty($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('model', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        // Movement type filter
        if (!empty($filters['transaction_type'])) {
            $query->byType($filters['transaction_type']);
        }

        // Serial number search
        if (!empty($filters['serial_no'])) {
            $query->where('serial_no', 'like', '%' . $filters['serial_no'] . '%');
        }

        // Technician filter (for supervisors viewing their team)
        if (!empty($filters['technician_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('from_location_type', 'technician')
                        ->where('from_location_id', $filters['technician_id']);
                })->orWhere(function ($sub) use ($filters) {
                    $sub->where('to_location_type', 'technician')
                        ->where('to_location_id', $filters['technician_id']);
                });
            });
        }

        // Role-based filtering
        if (!empty($filters['role'])) {
            $query = $this->applyRoleBasedFilter($query, $filters['role'], $filters['user'] ?? null);
        }

        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get movement summary statistics
     */
    public function getMovementSummary(array $filters)
    {
        $baseQuery = $this->getMovementReport($filters);

        return [
            'total_movements' => (clone $baseQuery)->count(),
            'by_type' => (clone $baseQuery)->select('transaction_type', DB::raw('count(*) as count'))
                ->groupBy('transaction_type')
                ->get()
                ->pluck('count', 'transaction_type')
                ->toArray(),
            'by_model' => (clone $baseQuery)->with('model')
                ->select('model_id', DB::raw('count(*) as count'))
                ->groupBy('model_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'model' => $item->model ? $item->model->model_name : 'Unknown',
                        'count' => $item->count
                    ];
                }),
            'total_in' => (clone $baseQuery)->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => (clone $baseQuery)->where('quantity', '<', 0)->sum('quantity'),
        ];
    }

    /**
     * Get stock card for a specific serial number
     *
     * FIXED: Removed 'currentLocation' eager loading to prevent polymorphic relationship error
     * The views use 'current_location_name' attribute instead
     */
    public function getStockCard(int $serialId)
    {
        // Don't eager load currentLocation - it causes polymorphic errors
        // The model has getCurrentLocationNameAttribute() accessor which is used in views
        $serial = InventorySerial::with(['model.category'])
            ->findOrFail($serialId);

        $movements = StockLedger::where('serial_id', $serialId)
            ->with(['createdBy'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balance
        $balance = 0;
        $movementsWithBalance = $movements->map(function ($movement) use (&$balance) {
            $balance += $movement->quantity;
            $movement->running_balance = $balance;
            return $movement;
        });

        return [
            'serial' => $serial,
            'movements' => $movementsWithBalance,
            'current_balance' => $balance,
            'total_in' => $movements->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => abs($movements->where('quantity', '<', 0)->sum('quantity')),
            'movement_count' => $movements->count(),
        ];
    }

    /**
     * Get movement report by location
     */
    public function getMovementByLocation(array $filters)
    {
        $query = StockLedger::with(['model.category'])
            ->notReversed();

        // Date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->byDateRange($filters['from_date'], $filters['to_date']);
        }

        // Group by location
        $movements = $query->select(
            'from_location_type',
            'from_location_id',
            'to_location_type',
            'to_location_id',
            'transaction_type',
            DB::raw('count(*) as movement_count'),
            DB::raw('sum(abs(quantity)) as total_quantity')
        )
            ->groupBy('from_location_type', 'from_location_id', 'to_location_type', 'to_location_id', 'transaction_type')
            ->get();

        return $movements->map(function ($movement) {
            return [
                'from' => $this->getLocationName($movement->from_location_type, $movement->from_location_id),
                'to' => $this->getLocationName($movement->to_location_type, $movement->to_location_id),
                'type' => StockLedger::TYPE_OPTIONS[$movement->transaction_type] ?? $movement->transaction_type,
                'count' => $movement->movement_count,
                'quantity' => $movement->total_quantity,
            ];
        });
    }

    /**
     * Get movement report by model
     */
    public function getMovementByModel(array $filters)
    {
        $query = StockLedger::with(['model.category'])
            ->notReversed();

        // Date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->byDateRange($filters['from_date'], $filters['to_date']);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('model', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        // Group by model and transaction type
        $movements = $query->select(
            'model_id',
            'transaction_type',
            DB::raw('count(*) as movement_count'),
            DB::raw('sum(case when quantity > 0 then quantity else 0 end) as total_in'),
            DB::raw('sum(case when quantity < 0 then abs(quantity) else 0 end) as total_out')
        )
            ->groupBy('model_id', 'transaction_type')
            ->get();

        // Group by model
        $byModel = [];
        foreach ($movements as $movement) {
            $modelKey = $movement->model_id;

            if (!isset($byModel[$modelKey])) {
                $byModel[$modelKey] = [
                    'model' => $movement->model ? $movement->model->model_name : 'Unknown',
                    'category' => $movement->model && $movement->model->category ? $movement->model->category->category_name : 'N/A',
                    'movements' => [],
                    'total_in' => 0,
                    'total_out' => 0,
                    'movement_count' => 0,
                ];
            }

            $byModel[$modelKey]['movements'][] = [
                'type' => StockLedger::TYPE_OPTIONS[$movement->transaction_type] ?? $movement->transaction_type,
                'count' => $movement->movement_count,
                'in' => $movement->total_in,
                'out' => $movement->total_out,
            ];

            $byModel[$modelKey]['total_in'] += $movement->total_in;
            $byModel[$modelKey]['total_out'] += $movement->total_out;
            $byModel[$modelKey]['movement_count'] += $movement->movement_count;
        }

        return array_values($byModel);
    }

    /**
     * Get top movers report
     */
    public function getTopMovers(int $limit = 10, array $filters = [])
    {
        $query = StockLedger::with(['model.category', 'serial'])
            ->notReversed();

        // Date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->byDateRange($filters['from_date'], $filters['to_date']);
        }

        $topModels = $query->select(
            'model_id',
            DB::raw('count(*) as movement_count'),
            DB::raw('count(distinct serial_id) as unique_serials')
        )
            ->groupBy('model_id')
            ->orderBy('movement_count', 'desc')
            ->limit($limit)
            ->get();

        return $topModels->map(function ($item) {
            return [
                'model_id' => $item->model_id,
                'model_name' => $item->model ? $item->model->model_name : 'Unknown',
                'category' => $item->model && $item->model->category ? $item->model->category->category_name : 'N/A',
                'movement_count' => $item->movement_count,
                'unique_serials' => $item->unique_serials,
            ];
        });
    }

    /**
     * Get technician inventory movements
     */
    public function getTechnicianMovements(int $technicianId, array $filters = [])
    {
        $query = StockLedger::with(['model.category', 'serial'])
            ->notReversed()
            ->where(function ($q) use ($technicianId) {
                $q->where(function ($sub) use ($technicianId) {
                    $sub->where('from_location_type', 'technician')
                        ->where('from_location_id', $technicianId);
                })->orWhere(function ($sub) use ($technicianId) {
                    $sub->where('to_location_type', 'technician')
                        ->where('to_location_id', $technicianId);
                });
            });

        // Date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->byDateRange($filters['from_date'], $filters['to_date']);
        }

        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Apply role-based filtering
     */
    protected function applyRoleBasedFilter($query, string $role, $user = null)
    {
        if ($role === 'supervisor' && $user) {
            // Supervisor can only see their team's movements
            $teamTechnicianIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();

            if (!empty($teamTechnicianIds)) {
                $query->where(function ($q) use ($teamTechnicianIds) {
                    $q->where(function ($sub) use ($teamTechnicianIds) {
                        $sub->where('from_location_type', 'technician')
                            ->whereIn('from_location_id', $teamTechnicianIds);
                    })->orWhere(function ($sub) use ($teamTechnicianIds) {
                        $sub->where('to_location_type', 'technician')
                            ->whereIn('to_location_id', $teamTechnicianIds);
                    });
                });
            }
        } elseif ($role === 'technician' && $user) {
            // Technician can only see their own movements
            $query->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('from_location_type', 'technician')
                        ->where('from_location_id', $user->id);
                })->orWhere(function ($sub) use ($user) {
                    $sub->where('to_location_type', 'technician')
                        ->where('to_location_id', $user->id);
                });
            });
        }

        return $query;
    }

    /**
     * Get location name from type and ID
     */
    protected function getLocationName(?string $type, ?int $id): string
    {
        if (!$type || !$id) {
            return '-';
        }

        $model = match ($type) {
            'depot' => Depot::find($id),
            'technician' => User::find($id),
            default => null,
        };

        if (!$model) {
            return ucfirst($type) . " #{$id}";
        }

        return match ($type) {
            'depot' => $model->depot_name ?? $model->depot_code ?? "Depot #{$id}",
            'technician' => $model->name ?? "Tech #{$id}",
            default => ucfirst($type) . " #{$id}",
        };
    }

    /**
     * Get date range options for quick filters
     */
    public static function getDateRangeOptions(): array
    {
        return [
            'today' => [
                'label' => 'Today',
                'from' => Carbon::today(),
                'to' => Carbon::today(),
            ],
            'yesterday' => [
                'label' => 'Yesterday',
                'from' => Carbon::yesterday(),
                'to' => Carbon::yesterday(),
            ],
            'this_week' => [
                'label' => 'This Week',
                'from' => Carbon::now()->startOfWeek(),
                'to' => Carbon::now()->endOfWeek(),
            ],
            'last_week' => [
                'label' => 'Last Week',
                'from' => Carbon::now()->subWeek()->startOfWeek(),
                'to' => Carbon::now()->subWeek()->endOfWeek(),
            ],
            'this_month' => [
                'label' => 'This Month',
                'from' => Carbon::now()->startOfMonth(),
                'to' => Carbon::now()->endOfMonth(),
            ],
            'last_month' => [
                'label' => 'Last Month',
                'from' => Carbon::now()->subMonth()->startOfMonth(),
                'to' => Carbon::now()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [
                'label' => 'This Quarter',
                'from' => Carbon::now()->firstOfQuarter(),
                'to' => Carbon::now()->lastOfQuarter(),
            ],
            'this_year' => [
                'label' => 'This Year',
                'from' => Carbon::now()->startOfYear(),
                'to' => Carbon::now()->endOfYear(),
            ],
        ];
    }
}
