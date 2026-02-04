<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\Depot;
use App\Models\User;
use App\Services\Inventory\StockBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StockBalanceController extends Controller
{
    protected StockBalanceService $balanceService;

    public function __construct(StockBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Display stock balance dashboard
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StockBalance::class);

        if ($request->ajax()) {
            return $this->getDatatableData($request);
        }

        // Get summary statistics
        $summary = $this->balanceService->getStockSummary();

        $depotSummary = $this->balanceService->getStockSummary('depot');
        $technicianSummary = $this->balanceService->getStockSummary('technician');

        // Get filters data
        $categories = TerminalCategory::orderBy('category_name')->get();
        $models = TerminalModel::orderBy('model_name')->get();
        $depots = Depot::orderBy('depot_name')->get();

        // Get low stock count
        $lowStockCount = $this->balanceService->getLowStockItems()->count();
        $outOfStockCount = $this->balanceService->getOutOfStockItems()->count();

        return view('admin.stock-balance.index', compact(
            'summary',
            'depotSummary',
            'technicianSummary',
            'categories',
            'models',
            'depots',
            'lowStockCount',
            'outOfStockCount'
        ));
    }

    /**
     * Show specific location balance details
     */
    public function show(Request $request, string $locationType, int $locationId)
    {
        $balances = $this->balanceService->getLocationBalances($locationType, $locationId);

        // Get location name
        $locationName = $this->getLocationName($locationType, $locationId);

        return view('admin.stock-balance.show', compact('balances', 'locationType', 'locationId', 'locationName'));
    }

    /**
     * Show low stock alerts
     */
    public function alerts(Request $request)
    {
        Gate::authorize('viewAlerts', StockBalance::class);

        $lowStockItems = $this->balanceService->getLowStockItems();
        $outOfStockItems = $this->balanceService->getOutOfStockItems();

        return view('admin.stock-balance.alerts', compact('lowStockItems', 'outOfStockItems'));
    }

    /**
     * Recalculate all balances
     */
    public function recalculate(Request $request)
    {
        Gate::authorize('recalculate', StockBalance::class);

        try {
            $results = $this->balanceService->recalculateAllBalances($request->model_id);

            return response()->json([
                'success' => true,
                'message' => 'Stock balances recalculated successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recalculation failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reserve stock
     */
    public function reserve(Request $request)
    {
        Gate::authorize('reserve', StockBalance::class);

        $request->validate([
            'model_id' => 'required|exists:terminal_models,id',
            'location_type' => 'required|in:depot,technician',
            'location_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $balance = $this->balanceService->reserveStock(
                $request->model_id,
                $request->location_type,
                $request->location_id,
                $request->quantity,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock reserved successfully',
                'data' => [
                    'quantity_on_hand' => $balance->quantity_on_hand,
                    'quantity_reserved' => $balance->quantity_reserved,
                    'quantity_available' => $balance->quantity_available,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Release stock reservation
     */
    public function releaseReservation(Request $request)
    {
        Gate::authorize('releaseReservation', StockBalance::class);

        $request->validate([
            'model_id' => 'required|exists:terminal_models,id',
            'location_type' => 'required|in:depot,technician',
            'location_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $balance = $this->balanceService->releaseReservation(
                $request->model_id,
                $request->location_type,
                $request->location_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'message' => 'Reservation released successfully',
                'data' => [
                    'quantity_on_hand' => $balance->quantity_on_hand,
                    'quantity_reserved' => $balance->quantity_reserved,
                    'quantity_available' => $balance->quantity_available,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Release failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Export stock balance
     */
    public function export(Request $request)
    {
        Gate::authorize('export', StockBalance::class);

        // Export implementation here
        return response()->json([
            'success' => false,
            'message' => 'Export feature not yet implemented'
        ]);
    }

    /**
     * Get DataTable data
     */
    protected function getDatatableData(Request $request)
    {
        $query = StockBalance::with(['model.category']);

        // Apply filters
        $this->applyFilters($query, $request);

        // Get total count
        $totalRecords = StockBalance::count();
        $filteredRecords = $query->count();

        // Apply sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns = [
            'model_id',
            'category',
            'location_type',
            'location_id',
            'quantity_on_hand',
            'quantity_reserved',
            'quantity_available',
            'last_movement_date',
        ];

        if (isset($columns[$orderColumn])) {
            if ($orderColumn == 1) { // Category
                $query->join('terminal_models', 'stock_balances.model_id', '=', 'terminal_models.id')
                    ->join('terminal_categories', 'terminal_models.category_id', '=', 'terminal_categories.id')
                    ->orderBy('terminal_categories.category_name', $orderDir)
                    ->select('stock_balances.*');
            } else {
                $query->orderBy($columns[$orderColumn], $orderDir);
            }
        } else {
            $query->orderBy('model_id', 'asc');
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);

        $balances = $query->skip($start)->take($length)->get();

        // Format data
        $data = $balances->map(function ($balance) {
            $model = $balance->model;
            $minStock = $model->min_stock_level ?? 5;

            // Determine stock status
            $stockStatus = 'normal';
            $stockBadge = '<span class="badge bg-success">Normal</span>';

            if ($balance->quantity_available <= 0) {
                $stockStatus = 'out_of_stock';
                $stockBadge = '<span class="badge bg-danger">Out of Stock</span>';
            } elseif ($balance->quantity_available <= $minStock) {
                $stockStatus = 'low_stock';
                $stockBadge = '<span class="badge bg-warning text-dark">Low Stock</span>';
            }

            return [
                'id' => $balance->id,
                'model_id' => $balance->model_id,
                'model_name' => $model->model_name ?? '-',
                'model_code' => $model->model_code ?? '-',
                'category' => $model->category->category_name ?? '-',
                'location_type' => ucfirst($balance->location_type),
                'location_name' => $this->getLocationName($balance->location_type, $balance->location_id),
                'quantity_on_hand' => number_format($balance->quantity_on_hand, 2),
                'quantity_reserved' => number_format($balance->quantity_reserved, 2),
                'quantity_available' => number_format($balance->quantity_available, 2),
                'min_stock_level' => number_format($minStock, 2),
                'stock_status' => $stockStatus,
                'stock_badge' => $stockBadge,
                'last_movement_date' => $balance->last_movement_date?->format('Y-m-d') ?? '-',
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('model', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status == 'low_stock') {
                // This would need custom filtering
            } elseif ($request->stock_status == 'out_of_stock') {
                $query->where('quantity_available', '<=', 0);
            }
        }
    }

    /**
     * Get location name
     */
    protected function getLocationName(string $type, int $id): string
    {
        return match ($type) {
            'depot' => Depot::find($id)?->depot_name ?? "Depot #{$id}",
            'technician' => User::find($id)?->name ?? "Technician #{$id}",
            default => ucfirst($type) . " #{$id}",
        };
    }
}
