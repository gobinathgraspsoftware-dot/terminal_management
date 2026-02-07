<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\Depot;
use App\Models\TerminalModel;
use App\Services\StockAdjustmentService;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\UpdateStockAdjustmentRequest;
use App\Exports\StockAdjustmentsExport;
use App\Exports\StockVarianceReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class StockAdjustmentController extends Controller
{
    protected $adjustmentService;

    public function __construct(StockAdjustmentService $adjustmentService)
    {
        $this->adjustmentService = $adjustmentService;
    }

    /**
     * Display a listing of stock adjustments (team-scoped)
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Supervisor can only see adjustments they created
            $query = StockAdjustment::with(['depot', 'creator', 'approver'])
                ->where('created_by', auth()->id())
                ->orderBy('adjustment_date', 'desc')
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('depot_id')) {
                $query->where('depot_id', $request->depot_id);
            }

            if ($request->filled('adjustment_type')) {
                $query->where('adjustment_type', $request->adjustment_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->where('adjustment_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('adjustment_date', '<=', $request->date_to);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('depot_name', function ($row) {
                    return $row->depot ? $row->depot->depot_name : 'N/A';
                })
                ->addColumn('adjustment_type_badge', function ($row) {
                    $badges = [
                        'count' => 'primary',
                        'correction' => 'info',
                        'write_off' => 'danger',
                        'other' => 'secondary',
                    ];
                    $color = $badges[$row->adjustment_type] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucwords(str_replace('_', ' ', $row->adjustment_type)) . '</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    $badges = [
                        'draft' => 'secondary',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'dark',
                    ];
                    $color = $badges[$row->status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucwords(str_replace('_', ' ', $row->status)) . '</span>';
                })
                ->addColumn('approved_by_name', function ($row) {
                    return $row->approver ? $row->approver->name : '-';
                })
                ->addColumn('action', function ($row) {
                    $actions = '<div class="btn-group" role="group">';
                    
                    // View button
                    $actions .= '<a href="' . route('supervisor.stock-adjustments.show', $row->id) . '" class="btn btn-sm btn-info" title="View">
                        <i class="bi bi-eye"></i>
                    </a>';

                    // Edit button (only for draft)
                    if ($row->status === StockAdjustment::STATUS_DRAFT && auth()->user()->can('update', $row)) {
                        $actions .= '<a href="' . route('supervisor.stock-adjustments.edit', $row->id) . '" class="btn btn-sm btn-warning" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>';
                    }

                    // Delete button (only for draft)
                    if ($row->status === StockAdjustment::STATUS_DRAFT && auth()->user()->can('delete', $row)) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['adjustment_type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();
        
        return view('supervisor.stock-adjustments.index', compact('depots'));
    }

    /**
     * Show the form for creating a new stock adjustment
     */
    public function create()
    {
        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        
        return view('supervisor.stock-adjustments.create', compact('depots', 'models'));
    }

    /**
     * Store a newly created stock adjustment
     */
    public function store(StoreStockAdjustmentRequest $request)
    {
        try {
            $adjustment = $this->adjustmentService->createAdjustment(
                $request->validated(),
                auth()->id()
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock adjustment created successfully.',
                    'redirect' => route('supervisor.stock-adjustments.show', $adjustment->id)
                ]);
            }

            return redirect()
                ->route('supervisor.stock-adjustments.show', $adjustment->id)
                ->with('success', 'Stock adjustment created successfully.');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating stock adjustment: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Error creating stock adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified stock adjustment
     */
    public function show(StockAdjustment $stockAdjustment)
    {
        $this->authorize('view', $stockAdjustment);

        $stockAdjustment->load([
            'depot',
            'lines.model',
            'lines.serial',
            'creator',
            'approver'
        ]);

        return view('supervisor.stock-adjustments.show', compact('stockAdjustment'));
    }

    /**
     * Show the form for editing the specified stock adjustment
     */
    public function edit(StockAdjustment $stockAdjustment)
    {
        $this->authorize('update', $stockAdjustment);

        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        
        $stockAdjustment->load('lines.model');

        return view('supervisor.stock-adjustments.edit', compact('stockAdjustment', 'depots', 'models'));
    }

    /**
     * Update the specified stock adjustment
     */
    public function update(UpdateStockAdjustmentRequest $request, StockAdjustment $stockAdjustment)
    {
        try {
            $adjustment = $this->adjustmentService->updateAdjustment(
                $stockAdjustment,
                $request->validated(),
                auth()->id()
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock adjustment updated successfully.',
                    'redirect' => route('supervisor.stock-adjustments.show', $adjustment->id)
                ]);
            }

            return redirect()
                ->route('supervisor.stock-adjustments.show', $adjustment->id)
                ->with('success', 'Stock adjustment updated successfully.');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating stock adjustment: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Error updating stock adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified stock adjustment
     */
    public function destroy(StockAdjustment $stockAdjustment)
    {
        $this->authorize('delete', $stockAdjustment);

        try {
            $this->adjustmentService->cancelAdjustment($stockAdjustment, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting stock adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit for approval
     */
    public function submit(StockAdjustment $stockAdjustment)
    {
        $this->authorize('update', $stockAdjustment);

        try {
            $this->adjustmentService->submitForApproval($stockAdjustment, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment submitted for approval.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get depot stock for AJAX
     */
    public function getDepotStock(Request $request)
    {
        $depotId = $request->depot_id;
        $modelId = $request->model_id;

        if (!$depotId) {
            return response()->json([
                'success' => false,
                'message' => 'Depot is required.'
            ], 400);
        }

        $stock = $this->adjustmentService->getDepotStock($depotId, $modelId);

        return response()->json([
            'success' => true,
            'data' => $stock
        ]);
    }

    /**
     * Variance report
     */
    public function varianceReport(Request $request)
    {
        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();

        if ($request->ajax() && $request->has('generate')) {
            $filters = $request->only(['depot_id', 'adjustment_type', 'date_from', 'date_to']);
            // Supervisor can only see their own adjustments in the report
            $filters['created_by'] = auth()->id();
            
            $variances = $this->adjustmentService->getVarianceReport($filters);

            return response()->json([
                'success' => true,
                'data' => $variances
            ]);
        }

        return view('supervisor.stock-adjustments.variance-report', compact('depots'));
    }

    /**
     * Export adjustments to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['depot_id', 'adjustment_type', 'status', 'date_from', 'date_to']);
        $filters['created_by'] = auth()->id(); // Only export supervisor's own adjustments
        
        return Excel::download(
            new StockAdjustmentsExport($filters),
            'stock-adjustments-' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export variance report to Excel
     */
    public function exportVariance(Request $request)
    {
        $filters = $request->only(['depot_id', 'adjustment_type', 'date_from', 'date_to']);
        $filters['created_by'] = auth()->id(); // Only export supervisor's own data
        
        return Excel::download(
            new StockVarianceReportExport($filters),
            'stock-variance-report-' . date('Y-m-d') . '.xlsx'
        );
    }
}
