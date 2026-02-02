<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\User;
use App\Models\StockBalance;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DepotService
{
    /**
     * Get DataTables data for depots.
     */
    public function getDataTable(Request $request, bool $readOnly = false)
    {
        $query = Depot::query()
            ->withCount(['stockBalances as total_items' => function($query) {
                $query->where('quantity_on_hand', '>', 0);
            }])
            ->with(['stockBalances' => function($query) {
                $query->where('quantity_on_hand', '>', 0);
            }]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('depot_code', function ($depot) {
                return '<span class="badge bg-secondary">' . $depot->depot_code . '</span>';
            })
            ->addColumn('type_badge', function ($depot) {
                $badges = [
                    'main' => '<span class="badge bg-danger">Main</span>',
                    'regional' => '<span class="badge bg-primary">Regional</span>',
                    'technician' => '<span class="badge bg-info">Technician</span>',
                ];
                return $badges[$depot->depot_type] ?? '';
            })
            ->addColumn('location', function ($depot) {
                $parts = array_filter([
                    $depot->city,
                    $depot->state,
                    $depot->country
                ]);
                return implode(', ', $parts) ?: '-';
            })
            ->addColumn('pic_info', function ($depot) {
                if ($depot->pic_name) {
                    $html = '<strong>' . e($depot->pic_name) . '</strong><br>';
                    if ($depot->pic_phone) {
                        $html .= '<small class="text-muted">' . e($depot->pic_phone) . '</small>';
                    }
                    return $html;
                }
                return '-';
            })
            ->addColumn('stock_summary', function ($depot) {
                $totalQty = $depot->stockBalances->sum('quantity_on_hand');
                $totalItems = $depot->stockBalances->count();
                
                return '<div class="text-center">' .
                       '<strong>' . number_format($totalQty, 0) . '</strong> units<br>' .
                       '<small class="text-muted">' . $totalItems . ' models</small>' .
                       '</div>';
            })
            ->addColumn('default_badge', function ($depot) {
                return $depot->is_default 
                    ? '<span class="badge bg-success"><i class="bi bi-star-fill"></i> Default</span>' 
                    : '';
            })
            ->addColumn('status', function ($depot) {
                $class = $depot->status === 'active' ? 'success' : 'danger';
                return '<span class="badge bg-' . $class . '">' . ucfirst($depot->status) . '</span>';
            })
            ->addColumn('action', function ($depot) use ($readOnly) {
                $btn = '<div class="btn-group" role="group">';
                
                // View button
                $routePrefix = request()->segment(1); // admin, supervisor, or technician
                $btn .= '<a href="' . route($routePrefix . '.depots.show', $depot->id) . '" 
                            class="btn btn-sm btn-info" title="View">
                            <i class="bi bi-eye"></i>
                        </a>';
                
                // Edit and Delete only for admin
                if (!$readOnly) {
                    $btn .= '<a href="' . route('admin.depots.edit', $depot->id) . '" 
                                class="btn btn-sm btn-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>';
                    
                    if (!$depot->is_default) {
                        $btn .= '<button type="button" 
                                    class="btn btn-sm btn-danger btn-delete" 
                                    data-id="' . $depot->id . '" 
                                    title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>';
                    }
                }
                
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['depot_code', 'type_badge', 'pic_info', 'stock_summary', 'default_badge', 'status', 'action'])
            ->make(true);
    }

    /**
     * Get stock summary for a depot.
     */
    public function getStockSummary(int $depotId): array
    {
        $stockBalances = StockBalance::where('location_type', 'depot')
            ->where('location_id', $depotId)
            ->where('quantity_on_hand', '>', 0)
            ->with('model.category')
            ->get();

        $totalQty = $stockBalances->sum('quantity_on_hand');
        $totalAvailable = $stockBalances->sum('quantity_available');
        $totalReserved = $stockBalances->sum('quantity_reserved');
        $totalModels = $stockBalances->count();

        // Group by category
        $byCategory = $stockBalances->groupBy(function($item) {
            return $item->model->category->category_name ?? 'Uncategorized';
        })->map(function($items) {
            return [
                'quantity' => $items->sum('quantity_on_hand'),
                'models' => $items->count()
            ];
        });

        return [
            'total_quantity' => $totalQty,
            'total_available' => $totalAvailable,
            'total_reserved' => $totalReserved,
            'total_models' => $totalModels,
            'by_category' => $byCategory,
            'items' => $stockBalances
        ];
    }

    /**
     * Get recent stock movements for a depot.
     */
    public function getRecentMovements(int $depotId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return StockLedger::where(function($query) use ($depotId) {
                $query->where(function($q) use ($depotId) {
                    $q->where('from_location_type', 'depot')
                      ->where('from_location_id', $depotId);
                })->orWhere(function($q) use ($depotId) {
                    $q->where('to_location_type', 'depot')
                      ->where('to_location_id', $depotId);
                });
            })
            ->with(['model.category'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get assigned technicians for a regional depot.
     */
    public function getAssignedTechnicians(int $depotId): \Illuminate\Database\Eloquent\Collection
    {
        // This is a placeholder - adjust based on your user-depot relationship
        // For now, we'll get technicians who have stock from this depot
        return User::role('technician')
            ->whereHas('stockBalances', function($query) use ($depotId) {
                $query->where('location_type', 'depot')
                      ->where('location_id', $depotId);
            })
            ->with(['stockBalances' => function($query) use ($depotId) {
                $query->where('location_type', 'depot')
                      ->where('location_id', $depotId)
                      ->where('quantity_on_hand', '>', 0);
            }])
            ->get();
    }

    /**
     * Get depot options for select dropdowns.
     */
    public function getDepotOptions(string $type = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Depot::active()->orderBy('depot_name');
        
        if ($type) {
            $query->where('depot_type', $type);
        }
        
        return $query->get(['id', 'depot_code', 'depot_name', 'depot_type']);
    }

    /**
     * Get default depot.
     */
    public function getDefaultDepot(): ?Depot
    {
        return Depot::default()->active()->first();
    }

    /**
     * Set depot as default.
     */
    public function setAsDefault(int $depotId): bool
    {
        DB::beginTransaction();
        try {
            // Remove default from all depots
            Depot::where('is_default', true)->update(['is_default' => false]);
            
            // Set new default
            Depot::where('id', $depotId)->update(['is_default' => true]);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
}
