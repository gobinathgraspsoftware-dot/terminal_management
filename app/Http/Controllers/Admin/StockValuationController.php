<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockValuationService;
use App\Exports\StockValuationSummaryExport;
use App\Exports\StockValuationDetailedExport;
use App\Exports\StockMovementValueExport;
use App\Models\TerminalCategory;
use App\Models\TerminalModel;
use App\Models\Depot;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockValuationController extends Controller
{
    protected StockValuationService $valuationService;

    public function __construct(StockValuationService $valuationService)
    {
        $this->valuationService = $valuationService;
    }

    /**
     * Display stock valuation summary
     */
    public function index(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_stock_valuation')) {
            abort(403, 'Unauthorized access to stock valuation');
        }

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'model_id' => $request->input('model_id'),
        ];

        $summary = $this->valuationService->getValuationSummary($filters);
        $categories = TerminalCategory::active()->orderBy('category_name')->get();
        $depots = Depot::active()->orderBy('depot_name')->get();
        $models = TerminalModel::active()->orderBy('model_name')->get();

        // Get top valued models
        $topModels = $this->valuationService->getTopValuedModels(10, $filters);

        return view('admin.stock-valuation.index', compact(
            'summary',
            'categories',
            'depots',
            'models',
            'topModels',
            'filters'
        ));
    }

    /**
     * Display detailed valuation by location
     */
    public function detailed(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_stock_valuation')) {
            abort(403, 'Unauthorized access to stock valuation');
        }

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'location_type' => $request->input('location_type'),
        ];

        $valuation = $this->valuationService->getDetailedValuation($filters);
        $categories = TerminalCategory::active()->orderBy('category_name')->get();
        $depots = Depot::active()->orderBy('depot_name')->get();

        return view('admin.stock-valuation.detailed', compact(
            'valuation',
            'categories',
            'depots',
            'filters'
        ));
    }

    /**
     * Display movement value tracking
     */
    public function movementValue(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_stock_valuation')) {
            abort(403, 'Unauthorized access to stock valuation');
        }

        $filters = [
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString()),
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'model_id' => $request->input('model_id'),
        ];

        $movementTracking = $this->valuationService->getMovementValueTracking($filters);
        $categories = TerminalCategory::active()->orderBy('category_name')->get();
        $depots = Depot::active()->orderBy('depot_name')->get();
        $models = TerminalModel::active()->orderBy('model_name')->get();

        return view('admin.stock-valuation.movement-value', compact(
            'movementTracking',
            'categories',
            'depots',
            'models',
            'filters'
        ));
    }

    /**
     * Export valuation summary
     */
    public function exportSummary(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('export_stock_valuation')) {
            abort(403, 'Unauthorized access to export stock valuation');
        }

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'model_id' => $request->input('model_id'),
        ];

        return Excel::download(
            new StockValuationSummaryExport($filters),
            'stock_valuation_summary_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Export detailed valuation
     */
    public function exportDetailed(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('export_stock_valuation')) {
            abort(403, 'Unauthorized access to export stock valuation');
        }

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'location_type' => $request->input('location_type'),
        ];

        return Excel::download(
            new StockValuationDetailedExport($filters),
            'stock_valuation_detailed_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Export movement value
     */
    public function exportMovementValue(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('export_stock_valuation')) {
            abort(403, 'Unauthorized access to export stock valuation');
        }

        $filters = [
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString()),
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'model_id' => $request->input('model_id'),
        ];

        return Excel::download(
            new StockMovementValueExport($filters),
            'stock_movement_value_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Display stock aging report
     */
    public function aging(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_stock_valuation')) {
            abort(403, 'Unauthorized access to stock valuation');
        }

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
        ];

        $aging = $this->valuationService->getStockAging($filters);
        $categories = TerminalCategory::active()->orderBy('category_name')->get();
        $depots = Depot::active()->orderBy('depot_name')->get();

        // Group by aging bracket
        $agingByBracket = $aging->groupBy('aging_bracket')->map(function ($items) {
            return [
                'count' => $items->count(),
                'value' => $items->sum('purchase_price'),
            ];
        });

        return view('admin.stock-valuation.aging', compact(
            'aging',
            'agingByBracket',
            'categories',
            'depots',
            'filters'
        ));
    }
}
