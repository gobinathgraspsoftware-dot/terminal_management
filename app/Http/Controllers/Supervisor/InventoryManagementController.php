<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\InventoryManagementService;
use App\Services\ReplacementService;
use App\Services\AccessoryUsageService;
use App\Models\StockOut;
use App\Models\Replacement;
use App\Models\AccessoryUsage;
use App\Models\InventorySerial;
use App\Models\TerminalModel;
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
            new Middleware('role:supervisor'),
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

    /**
     * Inventory Management Hub — scoped to supervisor's team.
     */
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('view_inventory_management'), 403);

        $supervisorId = auth()->id();
        $teamTechIds  = User::where('supervisor_id', $supervisorId)->pluck('id')->toArray();

        $summary = $this->inventoryService->getInventorySummary();

        // Team-scoped recent replacements
        $recentReplacements = Replacement::with(['technician', 'oldModel', 'newModel'])
            ->whereIn('technician_id', $teamTechIds)
            ->latest()
            ->take(5)
            ->get();

        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();

        return view('supervisor.inventory-management.index', compact(
            'summary', 'recentReplacements', 'depots', 'teamTechIds'
        ));
    }

    /**
     * AJAX: Router inventory DataTable (team-scoped where applicable).
     */
    public function routerDatatable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('view_inventory_management'), 403);

        $query = $this->inventoryService->getRouterInventory($request);

        $totalRecords = (clone $query)->count();
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);
        $items  = $query->orderBy('serial_no')->skip($start)->take($length)->get();

        $data = $items->map(function ($serial) {
            $statusBadge = InventorySerial::STATUS_BADGES[$serial->current_status] ?? 'secondary';
            $statusLabel = InventorySerial::STATUS_OPTIONS[$serial->current_status] ?? ucfirst($serial->current_status);

            return [
                'serial_no'  => $serial->serial_no,
                'model_name' => $serial->model->model_name ?? 'N/A',
                'category'   => $serial->model->category->category_name ?? 'N/A',
                'status'     => '<span class="badge bg-' . $statusBadge . '">' . $statusLabel . '</span>',
                'location'   => ucfirst($serial->current_location_type) . ' #' . $serial->current_location_id,
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $totalRecords,
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
                'model_name' => $balance->model->model_name ?? 'N/A',
                'category'   => $balance->model->category->category_name ?? 'N/A',
                'location'   => ucfirst($balance->location_type) . ' #' . $balance->location_id,
                'on_hand'    => number_format($balance->quantity_on_hand),
                'available'  => number_format($balance->quantity_available),
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords, 'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    // ── Stock Out (Supervisor can create for team) ──

    public function stockOutForm(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('create_stock_out'), 403);

        $depots     = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $sites      = Site::where('status', 'active')->orderBy('site_name')->get();
        $vendors    = Vendor::where('status', 'active')->orderBy('vendor_name')->get();

        $supervisorId = auth()->id();
        $technicians = User::where('supervisor_id', $supervisorId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $models = TerminalModel::with('category')->where('status', 'active')->orderBy('model_name')->get();

        return view('supervisor.inventory-management.stock-out', compact(
            'depots', 'sites', 'vendors', 'technicians', 'models'
        ));
    }

    public function storeStockOut(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('create_stock_out'), 403);

        $request->validate([
            'stock_out_date' => 'required|date',
            'out_type'       => 'required|in:direct_to_site,wastage,return_to_vendor,donation,other',
            'from_depot_id'  => 'required_without:from_technician_id|nullable|exists:depots,id',
            'lines'          => 'required|array|min:1',
            'lines.*.model_id'  => 'required|exists:terminal_models,id',
            'lines.*.quantity'  => 'required|numeric|min:0.0001',
        ]);

        try {
            $stockOut = $this->inventoryService->createStockOut($request->all());
            return response()->json(['success' => true, 'message' => "Stock Out #{$stockOut->stock_out_no} created."]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Replacement ──

    public function replacementForm(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('create_replacements'), 403);

        $supervisorId = auth()->id();
        $technicians = User::where('supervisor_id', $supervisorId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $depots  = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $sites   = Site::where('status', 'active')->orderBy('site_name')->get();
        $models  = TerminalModel::with('category')->where('status', 'active')->orderBy('model_name')->get();

        $teamTechIds = $technicians->pluck('id')->toArray();
        $tickets = Ticket::whereIn('technician_id', $teamTechIds)
            ->whereIn('status', ['assigned', 'accepted', 'in_progress', 'scheduled'])
            ->orderBy('ticket_no', 'desc')
            ->take(100)
            ->get(['id', 'ticket_no', 'merchant_name', 'router_id', 'terminal_id', 'technician_id']);

        return view('supervisor.inventory-management.replacement', compact(
            'technicians', 'depots', 'sites', 'models', 'tickets'
        ));
    }

    public function storeReplacement(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('create_replacements'), 403);

        $request->validate([
            'replacement_date'     => 'required|date',
            'new_serial_id'        => 'required|exists:inventory_serials,id',
            'new_model_id'         => 'required|exists:terminal_models,id',
            'old_device_condition'  => 'required|in:good,damaged,defective,wasted',
            'old_device_destination' => 'required|in:return_to_depot,return_to_vendor,wastage',
            'reason'               => 'required|string|max:1000',
        ]);

        try {
            $replacement = $this->replacementService->createReplacement($request->all());
            return response()->json(['success' => true, 'message' => "Replacement #{$replacement->replacement_no} created."]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function completeReplacement(Replacement $replacement): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('complete_replacements'), 403);

        try {
            $replacement = $this->replacementService->completeReplacement($replacement);
            return response()->json(['success' => true, 'message' => "Replacement completed."]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Accessory Usage ──

    public function accessoriesIndex(Request $request): View
    {
        abort_unless(auth()->user()->hasPermissionTo('view_accessory_usage'), 403);

        $supervisorId = auth()->id();
        $teamTechIds  = User::where('supervisor_id', $supervisorId)->pluck('id')->toArray();

        $query = AccessoryUsage::with(['ticket', 'model.category', 'serial', 'createdBy'])
            ->whereIn('created_by', array_merge($teamTechIds, [$supervisorId]))
            ->orderBy('created_at', 'desc');

        if ($type = $request->input('accessory_type')) $query->where('accessory_type', $type);
        if ($action = $request->input('action')) $query->where('action', $action);

        $usages = $query->paginate(20);

        return view('supervisor.inventory-management.accessories', compact('usages'));
    }

    public function storeAccessoryUsage(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('create_accessory_usage'), 403);

        $request->validate([
            'ticket_id'      => 'required|exists:tickets,id',
            'model_id'       => 'required|exists:terminal_models,id',
            'accessory_type' => 'required|in:sim_card,antenna,cable,adapter,other',
            'quantity'        => 'required|numeric|min:1',
        ]);

        try {
            $usage = $this->accessoryService->recordUsage($request->all());
            return response()->json(['success' => true, 'message' => 'Accessory usage recorded.']);
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
            $this->accessoryService->returnAccessory($accessoryUsage, $request->all());
            return response()->json(['success' => true, 'message' => 'Accessory returned.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * AJAX: Get available serials.
     */
    public function getAvailableSerials(Request $request): JsonResponse
    {
        $query = InventorySerial::where('model_id', $request->input('model_id'))
            ->where('current_status', InventorySerial::STATUS_IN_STOCK);

        if ($depotId = $request->input('depot_id')) {
            $query->where('current_location_type', 'depot')->where('current_location_id', $depotId);
        }
        if ($techId = $request->input('technician_id')) {
            $query->where('current_location_type', 'technician')->where('current_location_id', $techId);
        }

        return response()->json(['serials' => $query->orderBy('serial_no')->get(['id', 'serial_no'])]);
    }
}
