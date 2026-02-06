<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\StockIssue;
use App\Models\Depot;
use App\Models\TerminalModel;
use App\Http\Requests\StoreStockReturnRequest;
use App\Http\Requests\UpdateStockReturnRequest;
use App\Services\StockReturnService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Exception;

class StockReturnController extends Controller
{
    use AuthorizesRequests;

    protected $stockReturnService;

    public function __construct(StockReturnService $stockReturnService)
    {
        $this->stockReturnService = $stockReturnService;
    }

    public function index(Request $request)
    {
        $this->authorize('view_stock_returns');

        if ($request->ajax()) {
            $query = $this->stockReturnService->getFilteredStockReturns(
                $request,
                'technician',
                auth()->id()
            );

            return datatables()->eloquent($query)
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
                ->addColumn('action', function ($return) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('technician.stock-returns.show', $return->id) . '" 
                                   class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>';

                    if ($return->status === StockIssue::STATUS_DRAFT && auth()->user()->can('edit_stock_returns')) {
                        $actions .= '<a href="' . route('technician.stock-returns.edit', $return->id) . '" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>';
                    }

                    $actions .= '<a href="' . route('technician.stock-returns.print', $return->id) . '" 
                                   target="_blank" class="btn btn-sm btn-secondary" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['to_depot', 'status_badge', 'action'])
                ->make(true);
        }

        return view('technician.stock-returns.index');
    }

    public function create()
    {
        $this->authorize('create_stock_returns');

        $depots = Depot::orderBy('depot_name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        
        $myInventory = $this->stockReturnService->getTechnicianInventorySummary(auth()->id());

        return view('technician.stock-returns.create', compact('depots', 'models', 'myInventory'));
    }

    public function store(StoreStockReturnRequest $request)
    {
        try {
            $data = $request->validated();
            $data['from_technician_id'] = auth()->id();
            
            $stockReturn = $this->stockReturnService->createStockReturn($data);

            return response()->json([
                'success' => true,
                'message' => 'Stock return created successfully',
                'redirect' => route('technician.stock-returns.show', $stockReturn->id)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating stock return: ' . $e->getMessage()
            ], 422);
        }
    }

    public function show(StockIssue $stockReturn)
    {
        $this->authorize('view_stock_returns');

        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH ||
            $stockReturn->from_technician_id != auth()->id()) {
            abort(403);
        }

        $stockReturn->load(['toDepot', 'lines.model', 'lines.serial']);
        return view('technician.stock-returns.show', compact('stockReturn'));
    }

    public function edit(StockIssue $stockReturn)
    {
        $this->authorize('edit_stock_returns');

        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH ||
            $stockReturn->from_technician_id != auth()->id()) {
            abort(403);
        }

        if ($stockReturn->status !== StockIssue::STATUS_DRAFT) {
            return redirect()->route('technician.stock-returns.show', $stockReturn->id)
                ->with('error', 'Only draft stock returns can be edited');
        }

        $stockReturn->load('lines.model', 'lines.serial');
        $depots = Depot::orderBy('depot_name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        $myInventory = $this->stockReturnService->getTechnicianInventorySummary(auth()->id());

        return view('technician.stock-returns.edit', compact('stockReturn', 'depots', 'models', 'myInventory'));
    }

    public function update(UpdateStockReturnRequest $request, StockIssue $stockReturn)
    {
        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH ||
            $stockReturn->from_technician_id != auth()->id()) {
            abort(403);
        }

        try {
            $data = $request->validated();
            $data['from_technician_id'] = auth()->id();
            
            $stockReturn = $this->stockReturnService->updateStockReturn($stockReturn, $data);

            return response()->json([
                'success' => true,
                'message' => 'Stock return updated successfully',
                'redirect' => route('technician.stock-returns.show', $stockReturn->id)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating stock return: ' . $e->getMessage()
            ], 422);
        }
    }

    public function print(StockIssue $stockReturn)
    {
        $this->authorize('view_stock_returns');

        if ($stockReturn->issue_type !== StockIssue::TYPE_RETURN_FROM_TECH ||
            $stockReturn->from_technician_id != auth()->id()) {
            abort(403);
        }

        $stockReturn->load(['toDepot', 'lines.model', 'lines.serial']);
        return view('technician.stock-returns.print', compact('stockReturn'));
    }

    public function getMyInventory(Request $request)
    {
        $modelId = $request->model_id ?? null;
        $inventory = $this->stockReturnService->getTechnicianInventory(auth()->id(), $modelId);

        return response()->json([
            'success' => true,
            'inventory' => $inventory
        ]);
    }
}
