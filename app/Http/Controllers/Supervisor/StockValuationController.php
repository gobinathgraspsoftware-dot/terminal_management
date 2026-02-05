<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockValuationService;
use App\Exports\StockValuationSummaryExport;
use App\Exports\StockValuationDetailedExport;
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
     * Display stock valuation summary (scoped to supervisor's depots)
     */
    public function index(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_stock_valuation')) {
            abort(403, 'Unauthorized access to stock valuation');
        }

        // Get depots accessible by this supervisor
        $accessibleDepots = $this->getAccessibleDepots();

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'model_id' => $request->input('model_id'),
        ];

        // Ensure depot_id is within accessible depots
        if ($filters['depot_id'] && !in_array($filters['depot_id'], $accessibleDepots->pluck('id')->toArray())) {
            abort(403, 'Unauthorized access to this depot');
        }

        // If no depot selected, show all accessible depots
        if (!$filters['depot_id']) {
            $filters['accessible_depot_ids'] = $accessibleDepots->pluck('id')->toArray();
        }

        $summary = $this->valuationService->getValuationSummary($filters);
        $categories = TerminalCategory::active()->orderBy('category_name')->get();
        $models = TerminalModel::active()->orderBy('model_name')->get();

        // Get top valued models within scope
        $topModels = $this->valuationService->getTopValuedModels(10, $filters);

        return view('supervisor.stock-valuation.index', compact(
            'summary',
            'categories',
            'accessibleDepots',
            'models',
            'topModels',
            'filters'
        ));
    }

    /**
     * Display detailed valuation by location (scoped)
     */
    public function detailed(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_stock_valuation')) {
            abort(403, 'Unauthorized access to stock valuation');
        }

        // Get depots accessible by this supervisor
        $accessibleDepots = $this->getAccessibleDepots();

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'location_type' => $request->input('location_type'),
        ];

        // Ensure depot_id is within accessible depots
        if ($filters['depot_id'] && !in_array($filters['depot_id'], $accessibleDepots->pluck('id')->toArray())) {
            abort(403, 'Unauthorized access to this depot');
        }

        // If no depot selected, filter by accessible depots
        if (!$filters['depot_id']) {
            $filters['accessible_depot_ids'] = $accessibleDepots->pluck('id')->toArray();
        }

        $valuation = $this->valuationService->getDetailedValuation($filters);
        $categories = TerminalCategory::active()->orderBy('category_name')->get();

        return view('supervisor.stock-valuation.detailed', compact(
            'valuation',
            'categories',
            'accessibleDepots',
            'filters'
        ));
    }

    /**
     * Export valuation summary (scoped)
     */
    public function exportSummary(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('export_stock_valuation')) {
            abort(403, 'Unauthorized access to export stock valuation');
        }

        // Get depots accessible by this supervisor
        $accessibleDepots = $this->getAccessibleDepots();

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'model_id' => $request->input('model_id'),
        ];

        // Ensure depot_id is within accessible depots
        if ($filters['depot_id'] && !in_array($filters['depot_id'], $accessibleDepots->pluck('id')->toArray())) {
            abort(403, 'Unauthorized access to this depot');
        }

        // If no depot selected, filter by accessible depots
        if (!$filters['depot_id']) {
            $filters['accessible_depot_ids'] = $accessibleDepots->pluck('id')->toArray();
        }

        return Excel::download(
            new StockValuationSummaryExport($filters),
            'stock_valuation_summary_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Export detailed valuation (scoped)
     */
    public function exportDetailed(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('export_stock_valuation')) {
            abort(403, 'Unauthorized access to export stock valuation');
        }

        // Get depots accessible by this supervisor
        $accessibleDepots = $this->getAccessibleDepots();

        $filters = [
            'depot_id' => $request->input('depot_id'),
            'category_id' => $request->input('category_id'),
            'location_type' => $request->input('location_type'),
        ];

        // Ensure depot_id is within accessible depots
        if ($filters['depot_id'] && !in_array($filters['depot_id'], $accessibleDepots->pluck('id')->toArray())) {
            abort(403, 'Unauthorized access to this depot');
        }

        // If no depot selected, filter by accessible depots
        if (!$filters['depot_id']) {
            $filters['accessible_depot_ids'] = $accessibleDepots->pluck('id')->toArray();
        }

        return Excel::download(
            new StockValuationDetailedExport($filters),
            'stock_valuation_detailed_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Get depots accessible by this supervisor
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getAccessibleDepots()
    {
        $user = auth()->user();

        // If admin, return all depots
        if ($user->hasRole('admin')) {
            return Depot::active()->orderBy('depot_name')->get();
        }

        // For supervisors, return depots based on coverage_states or assigned depots
        // This can be customized based on your business logic
        // For now, returning all active depots (you can modify this)
        return Depot::active()->orderBy('depot_name')->get();
    }
}
