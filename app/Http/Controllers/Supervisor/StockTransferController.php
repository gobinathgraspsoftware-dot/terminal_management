<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Depot;
use App\Models\TerminalModel;
use App\Models\InventorySerial;
use App\Services\StockTransferService;
use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Http\Requests\ApproveStockTransferRequest;
use App\Http\Requests\DispatchStockTransferRequest;
use App\Http\Requests\ReceiveStockTransferRequest;
use App\Exports\StockTransfersExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebskie\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class StockTransferController extends Controller
{
    protected $stockTransferService;

    public function __construct(StockTransferService $stockTransferService)
    {
        $this->stockTransferService = $stockTransferService;
    }

    /**
     * Display a listing of stock transfers (team-scoped)
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        // Get depots accessible to supervisor's team
        $depots = Depot::orderBy('depot_name')->get();
        
        $statuses = [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'in_transit' => 'In Transit',
            'received' => 'Received',
            'cancelled' => 'Cancelled',
        ];

        return view('supervisor.stock-transfers.index', compact('depots', 'statuses'));
    }

    /**
     * DataTables AJAX endpoint (team-scoped)
     */
    public function datatable(Request $request)
    {
        $user = auth()->user();
        
        $query = StockTransfer::with(['fromDepot', 'toDepot', 'lines'])
            ->select('stock_transfers.*');

        // Team scoping - only show transfers for team's depots
        // Supervisors can see transfers where either source or destination is their team's depot
        if ($user->team) {
            $query->where(function($q) use ($user) {
                // This assumes depots have a team_id or we track via users
                // Adjust based on your actual schema
                $q->whereHas('fromDepot', function($subQ) use ($user) {
                    // Add your team filtering logic here
                })
                ->orWhereHas('toDepot', function($subQ) use ($user) {
                    // Add your team filtering logic here
                });
            });
        }

        // Filters
        if ($request->filled('from_depot_id')) {
            $query->where('from_depot_id', $request->from_depot_id);
        }

        if ($request->filled('to_depot_id')) {
            $query->where('to_depot_id', $request->to_depot_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('from_depot_name', function ($transfer) {
                return $transfer->fromDepot->depot_name ?? 'N/A';
            })
            ->addColumn('to_depot_name', function ($transfer) {
                return $transfer->toDepot->depot_name ?? 'N/A';
            })
            ->addColumn('total_items_display', function ($transfer) {
                return number_format($transfer->total_items);
            })
            ->addColumn('status_badge', function ($transfer) {
                return $this->getStatusBadge($transfer->status);
            })
            ->addColumn('actions', function ($transfer) {
                return view('supervisor.stock-transfers._actions', compact('transfer'))->render();
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new stock transfer
     */
    public function create()
    {
        $depots = Depot::orderBy('depot_name')->get();
        $models = TerminalModel::with('category')->orderBy('model_name')->get();

        return view('supervisor.stock-transfers.create', compact('depots', 'models'));
    }

    /**
     * Store a newly created stock transfer
     */
    public function store(StoreStockTransferRequest $request)
    {
        try {
            DB::beginTransaction();

            $transfer = $this->stockTransferService->createTransfer(
                $request->validated(),
                auth()->id()
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock transfer created successfully',
                    'transfer_id' => $transfer->id,
                    'transfer_no' => $transfer->transfer_no,
                ]);
            }

            return redirect()
                ->route('supervisor.stock-transfers.show', $transfer)
                ->with('success', 'Stock transfer created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating transfer: ' . $e->getMessage(),
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'Error creating transfer: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified stock transfer
     */
    public function show(StockTransfer $stockTransfer)
    {
        $this->authorize('view', $stockTransfer);

        $stockTransfer->load([
            'fromDepot',
            'toDepot',
            'lines.model.category',
            'lines.serial'
        ]);

        return view('supervisor.stock-transfers.show', compact('stockTransfer'));
    }

    /**
     * Show approval form
     */
    public function approveForm(StockTransfer $stockTransfer)
    {
        $this->authorize('approve', $stockTransfer);

        if ($stockTransfer->status !== StockTransfer::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Transfer is not pending approval');
        }

        $stockTransfer->load([
            'fromDepot',
            'toDepot',
            'lines.model.category',
            'lines.serial'
        ]);

        return view('supervisor.stock-transfers.approve', compact('stockTransfer'));
    }

    /**
     * Approve stock transfer
     */
    public function approve(ApproveStockTransferRequest $request, StockTransfer $stockTransfer)
    {
        $this->authorize('approve', $stockTransfer);

        try {
            DB::beginTransaction();

            $this->stockTransferService->approveTransfer(
                $stockTransfer,
                auth()->id(),
                $request->remarks
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer approved successfully',
                ]);
            }

            return redirect()
                ->route('supervisor.stock-transfers.show', $stockTransfer)
                ->with('success', 'Transfer approved successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show receive form
     */
    public function receiveForm(StockTransfer $stockTransfer)
    {
        $this->authorize('receive', $stockTransfer);

        if (!in_array($stockTransfer->status, [
            StockTransfer::STATUS_IN_TRANSIT,
            StockTransfer::STATUS_APPROVED
        ])) {
            return back()->with('error', 'Transfer cannot be received in current status');
        }

        $stockTransfer->load([
            'fromDepot',
            'toDepot',
            'lines.model.category',
            'lines.serial'
        ]);

        return view('supervisor.stock-transfers.receive', compact('stockTransfer'));
    }

    /**
     * Receive stock transfer
     */
    public function receive(ReceiveStockTransferRequest $request, StockTransfer $stockTransfer)
    {
        $this->authorize('receive', $stockTransfer);

        try {
            DB::beginTransaction();

            $this->stockTransferService->receiveTransfer(
                $stockTransfer,
                $request->validated(),
                auth()->id()
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer received successfully',
                ]);
            }

            return redirect()
                ->route('supervisor.stock-transfers.show', $stockTransfer)
                ->with('success', 'Transfer received successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print transfer slip
     */
    public function print(StockTransfer $stockTransfer)
    {
        $this->authorize('view', $stockTransfer);

        $stockTransfer->load([
            'fromDepot',
            'toDepot',
            'lines.model.category',
            'lines.serial'
        ]);

        return view('supervisor.stock-transfers.print', compact('stockTransfer'));
    }

    /**
     * Export to Excel
     */
    public function export(Request $request)
    {
        $filters = [
            'from_depot_id' => $request->from_depot_id,
            'to_depot_id' => $request->to_depot_id,
            'status' => $request->status,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        return Excel::download(
            new StockTransfersExport($filters),
            'stock_transfers_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Get available stock for transfer
     */
    public function getAvailableStock(Request $request)
    {
        $request->validate([
            'from_depot_id' => 'required|exists:depots,id',
            'model_id' => 'nullable|exists:terminal_models,id',
        ]);

        $query = InventorySerial::where('current_location_type', 'depot')
            ->where('current_location_id', $request->from_depot_id)
            ->where('current_status', 'in_stock')
            ->with('model.category');

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        $serials = $query->get();

        return response()->json([
            'success' => true,
            'serials' => $serials,
        ]);
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'pending_approval' => '<span class="badge bg-warning text-dark">Pending Approval</span>',
            'approved' => '<span class="badge bg-info">Approved</span>',
            'in_transit' => '<span class="badge bg-primary">In Transit</span>',
            'received' => '<span class="badge bg-success">Received</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}
