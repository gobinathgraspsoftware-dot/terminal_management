<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Services\Inventory\StockBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StockBalanceController extends Controller
{
    use AuthorizesRequests;

    protected $balanceService;

    public function __construct(StockBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Display technician's own stock balance
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', StockBalance::class);

        $technicianId = Auth::id();

        if ($request->ajax()) {
            return $this->getDataTableData($request, $technicianId);
        }

        // Get summary data for technician
        $summary = $this->getTechnicianSummary($technicianId);

        // Get categories for filter
        $categories = TerminalCategory::select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        // Get models for filter
        $models = TerminalModel::select('id', 'model_name', 'model_code')
            ->orderBy('model_name')
            ->get();

        // Count low stock items (using threshold of 5)
        $lowStockCount = StockBalance::where('location_type', 'technician')
            ->where('location_id', $technicianId)
            ->where('quantity_available', '>', 0)
            ->where('quantity_available', '<=', 5)
            ->count();

        // Count out of stock items
        $outOfStockCount = StockBalance::where('location_type', 'technician')
            ->where('location_id', $technicianId)
            ->where('quantity_available', '<=', 0)
            ->count();

        return view('technician.stock-balance.index', compact(
            'summary',
            'categories',
            'models',
            'lowStockCount',
            'outOfStockCount'
        ));
    }

    /**
     * Get technician's stock summary
     */
    protected function getTechnicianSummary($technicianId)
    {
        $balances = StockBalance::where('location_type', 'technician')
            ->where('location_id', $technicianId)
            ->get();

        return (object) [
            'total_items' => $balances->sum('quantity_on_hand'),
            'total_available' => $balances->sum('quantity_available'),
            'total_reserved' => $balances->sum('quantity_reserved'),
            'unique_models' => $balances->pluck('model_id')->unique()->count(),
        ];
    }

    /**
     * Get DataTable data for technician's stock
     */
    protected function getDataTableData(Request $request, $technicianId)
    {
        $query = StockBalance::with(['model.category'])
            ->where('location_type', 'technician')
            ->where('location_id', $technicianId);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->whereHas('model', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low_stock') {
                $query->where('quantity_available', '>', 0)
                    ->where('quantity_available', '<=', 5);
            } elseif ($request->stock_status === 'out_of_stock') {
                $query->where('quantity_available', '<=', 0);
            }
        }

        // Get total count
        $totalRecords = $query->count();

        // Apply sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns = [
            'model_id',
            'category_id',
            'quantity_on_hand',
            'quantity_reserved',
            'quantity_available',
            'last_movement_date',
        ];

        if (isset($columns[$orderColumn])) {
            if ($orderColumn === 1) { // Category
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
        $filteredRecords = $query->count();

        $data = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $formattedData = $data->map(function ($balance) {
            return [
                'id' => $balance->id,
                'model_name' => $balance->model->model_name ?? '-',
                'model_code' => $balance->model->model_code ?? '-',
                'category' => $balance->model->category->category_name ?? '-',
                'quantity_on_hand' => number_format($balance->quantity_on_hand, 2),
                'quantity_reserved' => number_format($balance->quantity_reserved, 2),
                'quantity_available' => number_format($balance->quantity_available, 2),
                'min_stock_level' => 5, // Fixed threshold
                'last_movement_date' => $balance->last_movement_date
                    ? $balance->last_movement_date->format('d M Y')
                    : '-',
                'stock_badge' => $balance->stock_status_badge,
                'is_low_stock' => $balance->is_low_stock,
                'is_out_of_stock' => $balance->is_out_of_stock,
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $formattedData,
        ]);
    }
}
