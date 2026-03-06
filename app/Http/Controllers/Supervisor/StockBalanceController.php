<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
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
     * Display stock balance dashboard (scoped to supervisor's team)
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StockBalance::class);

        if ($request->ajax()) {
            return $this->getDatatableData($request);
        }

        // Get team technician summary
        $teamTechIds = User::where('supervisor_id', auth()->id())->pluck('id')->toArray();

        $technicianSummary = StockBalance::select(
            \DB::raw('COUNT(DISTINCT model_id) as unique_models'),
            \DB::raw('SUM(quantity_on_hand) as total_quantity'),
            \DB::raw('SUM(quantity_reserved) as total_reserved'),
            \DB::raw('SUM(quantity_on_hand - quantity_reserved) as total_available')
        )
        ->where('location_type', 'technician')
        ->whereIn('location_id', $teamTechIds)
        ->where('quantity_on_hand', '>', 0)
        ->first();

        // Get filters data
        $categories = TerminalCategory::orderBy('category_name')->get();
        $models = TerminalModel::orderBy('model_name')->get();

        // Get supervisor's team technicians
        $technicians = User::where('supervisor_id', auth()->id())
            ->orderBy('name')
            ->get();

        // Get low stock count for team
        $lowStockItems = $this->balanceService->getLowStockItems('technician', null)
            ->whereIn('location_id', $teamTechIds);
        $lowStockCount = $lowStockItems->count();

        $outOfStockItems = $this->balanceService->getOutOfStockItems('technician', null)
            ->whereIn('location_id', $teamTechIds);
        $outOfStockCount = $outOfStockItems->count();

        return view('supervisor.stock-balance.index', compact(
            'technicianSummary',
            'categories',
            'models',
            'technicians',
            'lowStockCount',
            'outOfStockCount'
        ));
    }

    /**
     * Show specific technician balance details
     */
    public function show(Request $request, int $technicianId)
    {
        // Verify technician belongs to supervisor's team
        $technician = User::where('id', $technicianId)
            ->where('supervisor_id', auth()->id())
            ->firstOrFail();

        $balances = $this->balanceService->getLocationBalances('technician', $technicianId);

        return view('supervisor.stock-balance.show', compact('balances', 'technician'));
    }

    /**
     * Get DataTable data (scoped to supervisor's team)
     */
    protected function getDatatableData(Request $request)
    {
        // Get supervisor's team technician IDs
        $teamTechIds = User::where('supervisor_id', auth()->id())->pluck('id')->toArray();

        $query = StockBalance::with(['model.category'])
            ->where('location_type', 'technician')
            ->whereIn('location_id', $teamTechIds)
            ->where('quantity_on_hand', '>', 0);

        // Apply filters
        $this->applyFilters($query, $request);

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('model', function ($mq) use ($search) {
                    $mq->where('model_name', 'like', "%{$search}%")
                       ->orWhere('model_code', 'like', "%{$search}%");
                });
            });
        }

        $totalRecords = StockBalance::where('location_type', 'technician')
            ->whereIn('location_id', $teamTechIds)
            ->where('quantity_on_hand', '>', 0)
            ->count();

        $filteredRecords = $query->count();

        // Sorting
        $sortColumn = $request->input('order.0.column', 0);
        $sortDir = $request->input('order.0.dir', 'asc');
        $columns = ['id', 'model_id', 'model_id', 'model_id', 'location_id', 'quantity_on_hand', 'quantity_reserved', 'quantity_on_hand', 'quantity_on_hand', 'quantity_on_hand', 'last_movement_date'];
        $orderBy = $columns[$sortColumn] ?? 'model_id';
        $query->orderBy($orderBy, $sortDir);

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        $balances = $query->skip($start)->take($length)->get();

        // Pre-load technician names
        $technicianNames = User::whereIn('id', $teamTechIds)->pluck('name', 'id');

        $data = $balances->map(function ($balance) use ($technicianNames) {
            $model = $balance->model;
            $minStock = $model->min_stock_level ?? 5;
            $available = $balance->quantity_on_hand - $balance->quantity_reserved;

            // Determine stock status
            $stockBadge = '<span class="badge bg-success">Normal</span>';
            if ($available <= 0) {
                $stockBadge = '<span class="badge bg-danger">Out of Stock</span>';
            } elseif ($available <= $minStock) {
                $stockBadge = '<span class="badge bg-warning text-dark">Low Stock</span>';
            }

            return [
                'id' => $balance->id,
                'model_id' => $balance->model_id,
                'model_name' => $model->model_name ?? '-',
                'model_code' => $model->model_code ?? '-',
                'category' => $model->category->category_name ?? '-',
                'technician_name' => $technicianNames[$balance->location_id] ?? "Technician #{$balance->location_id}",
                'quantity_on_hand' => number_format($balance->quantity_on_hand, 2),
                'quantity_reserved' => number_format($balance->quantity_reserved, 2),
                'quantity_available' => number_format($available, 2),
                'min_stock_level' => number_format($minStock, 2),
                'stock_badge' => $stockBadge,
                'last_movement_date' => $balance->last_movement_date ? date('Y-m-d', strtotime($balance->last_movement_date)) : '-',
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

        if ($request->filled('technician_id')) {
            $query->where('location_id', $request->technician_id);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status == 'out_of_stock') {
                $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 0');
            } elseif ($request->stock_status == 'low_stock') {
                $query->whereRaw('(quantity_on_hand - quantity_reserved) > 0')
                      ->whereRaw('(quantity_on_hand - quantity_reserved) <= COALESCE((SELECT min_stock_level FROM terminal_models WHERE id = stock_balances.model_id), 5)');
            }
        }
    }
}
