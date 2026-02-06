<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\StockIssue;
use App\Models\Depot;
use App\Models\User;
use App\Models\TerminalModel;
use App\Http\Requests\StoreStockReturnRequest;
use App\Http\Requests\UpdateStockReturnRequest;
use App\Services\StockReturnService;
use App\Exports\StockReturnsExport;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class StockReturnController extends Controller
{
    use AuthorizesRequests;

    protected $stockReturnService;

    public function __construct(StockReturnService $stockReturnService)
    {
        $this->stockReturnService = $stockReturnService;
    }

    /**
     * Display a listing of stock returns (team scope)
     */
    public function index(Request $request)
    {
        $this->authorize('view_stock_returns');

        if ($request->ajax()) {
            $query = $this->stockReturnService->getFilteredStockReturns(
                $request,
                'supervisor',
                auth()->id()
            );

            return datatables()->eloquent($query)
                ->addColumn('from_technician', function ($return) {
                    return $return->fromTechnician ? 
                        '<i class="bi bi-person"></i> ' . $return->fromTechnician->name : '-';
                })
                ->addColumn('to_depot', function ($return) {
                    return $return->toDepot ? 
                        '<i class="bi bi-building"></i> ' . $return->toDepot->depot_name : '-';
                })
                ->addColumn('status_badge', function ($return) {
                    $badges = [
                        'draft' => '<span class="badge bg-secondary">Draft</span>',
                        'posted' => '<span class="badge bg-success">Posted</span>',
                        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                    ];
                    return $badges[$return->status] ?? $return->status;
                })
                ->addColumn('has_damaged', function ($return) {
                    $hasDamaged = $return->lines()
                        ->whereIn('condition', ['damaged', 'defective'])
                        ->exists();
                    
                    return $hasDamaged ? 
                        '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Has Damaged</span>' : 
                        '<span class="badge bg-success"><i class="bi bi-check-circle"></i> All Good</span>';
                })
                ->addColumn('action', function ($return) {
                    $actions = '<div class="btn-group" role="group">';

                    // View
                    $actions .= '<a href="' . route('supervisor.stock-returns.show', $return->id) . '" 
                                   class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>';

                    // Edit (only for draft)
                    if ($return->status === StockIssue::STATUS_DRAFT && auth()->user()->can('edit_stock_returns')) {
                        $actions .= '<a href="' . route('supervisor.stock-returns.edit', $return->id) . '" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>';
                    }

                    // Post (only for draft)
                    if ($return->status === StockIssue::STATUS_DRAFT && auth()->user()->can('post_stock_returns')) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success" 
                                            onclick="postReturn(' . $return->id . ')" title="Post">
                                        <i class="bi bi-check-circle"></i>
                                    </button>';
                    }

                    // Cancel (only for posted)
                    if ($return->status === StockIssue::STATUS_POSTED && auth()->user()->can('cancel_stock_returns')) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" 
                                            onclick="cancelReturn(' . $return->id . ')" title="Cancel">
                                        <i class="bi bi-x-circle"></i>
                                    </button>';
                    }

                    // Print
                    $actions .= '<a href="' . route('supervisor.stock-returns.print', $return->id) . '" 
                                   target="_blank" class="btn btn-sm btn-secondary" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>';

                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['from_technician', 'to_depot', 'status_badge', 'has_damaged', 'action'])
                ->make(true);
        }

        // Get team technicians
        $technicians = auth()->user()->teamMembers()->orderBy('name')->get();
        $depots = Depot::orderBy('depot_name')->get();

        return view('supervisor.stock-returns.index', compact('depots', 'technicians'));
    }

    /**
     * Show the form for creating a new stock return
     */
    public function create()
    {
        $this->authorize('create_stock_returns');

        $depots = Depot::orderBy('depot_name')->get();
        $technicians = auth()->user()->teamMembers()->orderBy('name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();

        return view('supervisor.stock-returns.create', compact('depots', 'technicians', 'models'));
    }

    /**
     * Store a newly created stock return
     */
    public function store(StoreStockReturnRequest $request)
    {
        try {
            $stockReturn = $this->stockReturnService->createStockReturn($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stock return created successfully',
                'redirect' => route('supervisor.stock-returns.show', $stockReturn->id)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating stock return: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified stock return
     */
    public function show(StockIssue $stockReturn)
    {
        $this->authorize('view_stock_returns');

        // Ensure it's a return type and accessible by supervisor
        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH) {
            abort(404);
        }

        $teamTechnicianIds = auth()->user()->teamMembers()->pluck('id');
        if (!$teamTechnicianIds->contains($stockReturn->from_technician_id)) {
            abort(403, 'This return is not from your team');
        }

        $stockReturn->load([
            'fromTechnician',
            'toDepot',
            'lines.model',
            'lines.serial'
        ]);

        return view('supervisor.stock-returns.show', compact('stockReturn'));
    }

    /**
     * Show the form for editing the specified stock return
     */
    public function edit(StockIssue $stockReturn)
    {
        $this->authorize('edit_stock_returns');

        // Ensure it's a return type
        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH) {
            abort(404);
        }

        if ($stockReturn->status !== StockIssue::STATUS_DRAFT) {
            return redirect()->route('supervisor.stock-returns.show', $stockReturn->id)
                ->with('error', 'Only draft stock returns can be edited');
        }

        $stockReturn->load('lines.model', 'lines.serial');
        $depots = Depot::orderBy('depot_name')->get();
        $technicians = auth()->user()->teamMembers()->orderBy('name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();

        return view('supervisor.stock-returns.edit', compact('stockReturn', 'depots', 'technicians', 'models'));
    }

    /**
     * Update the specified stock return
     */
    public function update(UpdateStockReturnRequest $request, StockIssue $stockReturn)
    {
        // Ensure it's a return type
        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH) {
            abort(404);
        }

        try {
            $stockReturn = $this->stockReturnService->updateStockReturn($stockReturn, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stock return updated successfully',
                'redirect' => route('supervisor.stock-returns.show', $stockReturn->id)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating stock return: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Post stock return (create ledger entries)
     */
    public function post(StockIssue $stockReturn)
    {
        $this->authorize('post_stock_returns');

        // Ensure it's a return type
        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH) {
            abort(404);
        }

        try {
            $stockReturn = $this->stockReturnService->postStockReturn($stockReturn);

            return response()->json([
                'success' => true,
                'message' => 'Stock return posted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error posting stock return: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel stock return (reverse ledger entries)
     */
    public function cancel(StockIssue $stockReturn)
    {
        $this->authorize('cancel_stock_returns');

        // Ensure it's a return type
        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH) {
            abort(404);
        }

        try {
            $stockReturn = $this->stockReturnService->cancelStockReturn($stockReturn);

            return response()->json([
                'success' => true,
                'message' => 'Stock return cancelled successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling stock return: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Print stock return
     */
    public function print(StockIssue $stockReturn)
    {
        $this->authorize('view_stock_returns');

        // Ensure it's a return type
        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH) {
            abort(404);
        }

        $stockReturn->load([
            'fromTechnician',
            'toDepot',
            'lines.model',
            'lines.serial'
        ]);

        return view('supervisor.stock-returns.print', compact('stockReturn'));
    }

    /**
     * Get technician's current inventory
     */
    public function getTechnicianInventory(Request $request)
    {
        $this->authorize('view_technician_inventory_stock_returns');

        $technicianId = $request->technician_id;
        
        // Verify technician is in supervisor's team
        $teamTechnicianIds = auth()->user()->teamMembers()->pluck('id');
        if (!$teamTechnicianIds->contains($technicianId)) {
            return response()->json([
                'success' => false,
                'message' => 'Technician not in your team'
            ], 403);
        }

        $modelId = $request->model_id ?? null;
        $inventory = $this->stockReturnService->getTechnicianInventory($technicianId, $modelId);

        return response()->json([
            'success' => true,
            'inventory' => $inventory
        ]);
    }

    /**
     * Get technician inventory summary
     */
    public function getTechnicianInventorySummary(Request $request)
    {
        $this->authorize('view_technician_inventory_stock_returns');

        $technicianId = $request->technician_id;
        
        // Verify technician is in supervisor's team
        $teamTechnicianIds = auth()->user()->teamMembers()->pluck('id');
        if (!$teamTechnicianIds->contains($technicianId)) {
            return response()->json([
                'success' => false,
                'message' => 'Technician not in your team'
            ], 403);
        }

        $summary = $this->stockReturnService->getTechnicianInventorySummary($technicianId);

        return response()->json([
            'success' => true,
            'summary' => $summary
        ]);
    }

    /**
     * Export stock returns
     */
    public function export(Request $request)
    {
        $this->authorize('view_stock_returns');

        $query = $this->stockReturnService->getFilteredStockReturns(
            $request,
            'supervisor',
            auth()->id()
        );

        return Excel::download(
            new StockReturnsExport($query),
            'stock-returns-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
