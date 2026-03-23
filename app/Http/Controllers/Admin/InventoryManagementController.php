<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryManagement\StoreStockInRequest;
use App\Http\Requests\Admin\InventoryManagement\StoreStockOutRequest;
use App\Http\Requests\Admin\InventoryManagement\ProcessReplacementRequest;
use App\Http\Requests\Admin\InventoryManagement\StoreAccessoryUsageRequest;
use App\Services\InventoryManagementService;
use App\Services\ReplacementService;
use App\Services\AccessoryUsageService;
use App\Exports\AccessoryUsageExport;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Replacement;
use App\Models\AccessoryUsage;
use App\Models\InventorySerial;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\Depot;
use App\Models\Site;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class InventoryManagementController extends Controller implements HasMiddleware
{
    protected InventoryManagementService $inventoryService;
    protected ReplacementService $replacementService;
    protected AccessoryUsageService $accessoryService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:admin'),
        ];
    }

    public function __construct(
        InventoryManagementService $inventoryService,
        ReplacementService $replacementService,
        AccessoryUsageService $accessoryService
    ) {
        $this->inventoryService  = $inventoryService;
        $this->replacementService = $replacementService;
        $this->accessoryService  = $accessoryService;
    }

    // =========================================================================
    // HUB / INDEX
    // =========================================================================

    /**
     * Inventory Management Hub - Router vs Accessories split view.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasPermissionTo('view_inventory_management'), 403);

        $summary = $this->inventoryService->getInventorySummary();

        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();

        $recentStockIns  = StockIn::with('depot')->latest()->take(5)->get();
        $recentStockOuts = StockOut::with('fromDepot')->latest()->take(5)->get();
        $recentReplacements = Replacement::with(['technician', 'oldModel', 'newModel'])->latest()->take(5)->get();

        return view('admin.inventory-management.index', compact(
            'summary', 'depots', 'recentStockIns', 'recentStockOuts', 'recentReplacements'
        ));
    }

    /**
     * AJAX: Router inventory DataTable.
     */
    public function routerDatatable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('view_inventory_management'), 403);

        $query = $this->inventoryService->getRouterInventory($request);

        $totalRecords = (clone $query)->count();
        $filteredRecords = $totalRecords;

        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);
        $items  = $query->orderBy('serial_no')->skip($start)->take($length)->get();

        $data = $items->map(function ($serial) {
            $statusBadge = InventorySerial::STATUS_BADGES[$serial->current_status] ?? 'secondary';
            $statusLabel = InventorySerial::STATUS_OPTIONS[$serial->current_status] ?? ucfirst($serial->current_status);

            return [
                'id'         => $serial->id,
                'serial_no'  => $serial->serial_no,
                'model_name' => $serial->model->model_name ?? 'N/A',
                'category'   => $serial->model->category->category_name ?? 'N/A',
                'status'     => '<span class="badge bg-' . $statusBadge . '">' . $statusLabel . '</span>',
                'location'   => ucfirst($serial->current_location_type) . ' #' . $serial->current_location_id,
                'action'     => '<a href="' . route('admin.inventory-serials.show', $serial->id) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>',
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * AJAX: Accessory inventory DataTable.
     */
    public function accessoryDatatable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('view_inventory_management'), 403);

        $query = $this->inventoryService->getAccessoryInventory($request);

        $totalRecords = (clone $query)->count();
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);
        $items  = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($balance) {
            return [
                'model_name'  => $balance->model->model_name ?? 'N/A',
                'category'    => $balance->model->category->category_name ?? 'N/A',
                'location'    => ucfirst($balance->location_type) . ' #' . $balance->location_id,
                'on_hand'     => number_format($balance->quantity_on_hand),
                'reserved'    => number_format($balance->quantity_reserved),
                'available'   => number_format($balance->quantity_available),
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data'            => $data,
        ]);
    }

    // =========================================================================
    // STOCK IN
    // =========================================================================

    public function stockInForm(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('create_stock_in'), 403);

        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $models = TerminalModel::with('category')
            ->where('status', 'active')
            ->orderBy('model_name')
            ->get();

        return view('admin.inventory-management.stock-in', compact('depots', 'models'));
    }

    public function storeStockIn(StoreStockInRequest $request): JsonResponse
    {
        try {
            $stockIn = $this->inventoryService->createStockIn($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Stock In #{$stockIn->stock_in_no} created successfully.",
                'data'    => $stockIn,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function postStockIn(StockIn $stockIn): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('post_stock_in'), 403);

        try {
            $stockIn = $this->inventoryService->postStockIn($stockIn);

            return response()->json([
                'success' => true,
                'message' => "Stock In #{$stockIn->stock_in_no} posted successfully. Inventory updated.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancelStockIn(StockIn $stockIn): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('cancel_stock_in'), 403);

        try {
            $this->inventoryService->cancelStockIn($stockIn);
            return response()->json(['success' => true, 'message' => 'Stock In cancelled.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // STOCK OUT
    // =========================================================================

    public function stockOutForm(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('create_stock_out'), 403);

        $depots     = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $sites      = Site::where('status', 'active')->orderBy('site_name')->get();
        $vendors    = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $technicians = User::role('technician')->where('status', 'active')->orderBy('name')->get();
        $models     = TerminalModel::with('category')->where('status', 'active')->orderBy('model_name')->get();

        return view('admin.inventory-management.stock-out', compact(
            'depots', 'sites', 'vendors', 'technicians', 'models'
        ));
    }

    public function storeStockOut(StoreStockOutRequest $request): JsonResponse
    {
        try {
            $stockOut = $this->inventoryService->createStockOut($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Stock Out #{$stockOut->stock_out_no} created successfully.",
                'data'    => $stockOut,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function postStockOut(StockOut $stockOut): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('post_stock_out'), 403);

        try {
            $stockOut = $this->inventoryService->postStockOut($stockOut);

            return response()->json([
                'success' => true,
                'message' => "Stock Out #{$stockOut->stock_out_no} posted. Inventory updated.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancelStockOut(StockOut $stockOut): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('cancel_stock_out'), 403);

        try {
            $this->inventoryService->cancelStockOut($stockOut);
            return response()->json(['success' => true, 'message' => 'Stock Out cancelled.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // REPLACEMENT
    // =========================================================================

    public function replacementForm(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('create_replacements'), 403);

        $technicians = User::role('technician')->where('status', 'active')->orderBy('name')->get();
        $depots      = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $sites       = Site::where('status', 'active')->orderBy('site_name')->get();
        $models      = TerminalModel::with('category')->where('status', 'active')->orderBy('model_name')->get();
        $tickets     = Ticket::whereIn('status', ['assigned', 'accepted', 'in_progress', 'scheduled'])
            ->orderBy('ticket_no', 'desc')
            ->take(100)
            ->get(['id', 'ticket_no', 'merchant_name', 'router_id', 'terminal_id']);

        return view('admin.inventory-management.replacement', compact(
            'technicians', 'depots', 'sites', 'models', 'tickets'
        ));
    }

    public function storeReplacement(ProcessReplacementRequest $request): JsonResponse
    {
        try {
            $replacement = $this->replacementService->createReplacement($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Replacement #{$replacement->replacement_no} created.",
                'data'    => $replacement,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function completeReplacement(Replacement $replacement): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('complete_replacements'), 403);

        try {
            $replacement = $this->replacementService->completeReplacement($replacement);

            return response()->json([
                'success' => true,
                'message' => "Replacement #{$replacement->replacement_no} completed. Inventory updated.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancelReplacement(Replacement $replacement): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('cancel_replacements'), 403);

        try {
            $this->replacementService->cancelReplacement($replacement);
            return response()->json(['success' => true, 'message' => 'Replacement cancelled.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // ACCESSORY USAGE
    // =========================================================================

    public function accessoriesIndex(Request $request): View
    {
        abort_unless(auth()->user()->hasPermissionTo('view_accessory_usage'), 403);

        $usages = $this->accessoryService->getUsageList($request)->paginate(20);

        return view('admin.inventory-management.accessories', compact('usages'));
    }

    public function storeAccessoryUsage(StoreAccessoryUsageRequest $request): JsonResponse
    {
        try {
            $usage = $this->accessoryService->recordUsage($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Accessory usage recorded successfully.',
                'data'    => $usage,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function returnAccessory(AccessoryUsage $accessoryUsage, Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('return_accessory'), 403);

        $request->validate([
            'return_depot_id'  => 'required|exists:depots,id',
            'return_condition' => 'required|in:good,damaged,defective',
        ]);

        try {
            $usage = $this->accessoryService->returnAccessory($accessoryUsage, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Accessory returned successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function exportAccessoryUsage(Request $request)
    {
        abort_unless(auth()->user()->hasPermissionTo('export_accessory_usage'), 403);

        $filters = $request->only(['accessory_type', 'action', 'date_from', 'date_to', 'ticket_id']);

        return Excel::download(
            new AccessoryUsageExport($filters),
            'accessory_usage_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    // =========================================================================
    // AJAX HELPERS
    // =========================================================================

    /**
     * Get available serials for a model at a depot.
     */
    public function getAvailableSerials(Request $request): JsonResponse
    {
        $modelId    = $request->input('model_id');
        $depotId    = $request->input('depot_id');
        $techId     = $request->input('technician_id');

        $query = InventorySerial::where('model_id', $modelId)
            ->where('current_status', InventorySerial::STATUS_IN_STOCK);

        if ($depotId) {
            $query->where('current_location_type', 'depot')
                  ->where('current_location_id', $depotId);
        }
        if ($techId) {
            $query->where('current_location_type', 'technician')
                  ->where('current_location_id', $techId);
        }

        $serials = $query->orderBy('serial_no')->get(['id', 'serial_no']);

        return response()->json(['serials' => $serials]);
    }

    /**
     * Get replacement data for a ticket.
     */
    public function getTicketDeviceInfo(Request $request): JsonResponse
    {
        $ticket = Ticket::find($request->input('ticket_id'));

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'ticket_no'     => $ticket->ticket_no,
                'terminal_id'   => $ticket->terminal_id,
                'router_id'     => $ticket->router_id,
                'merchant_name' => $ticket->merchant_name,
                'technician_id' => $ticket->technician_id,
                'site_id'       => $ticket->site_id ?? null,
            ],
        ]);
    }
}
