<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Services\ReplacementService;
use App\Services\AccessoryUsageService;
use App\Models\Replacement;
use App\Models\AccessoryUsage;
use App\Models\InventorySerial;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\StockBalance;
use App\Models\Depot;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Exception;

class InventoryManagementController extends Controller implements HasMiddleware
{
    protected ReplacementService $replacementService;
    protected AccessoryUsageService $accessoryService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:technician'),
        ];
    }

    public function __construct(
        ReplacementService $replacementService,
        AccessoryUsageService $accessoryService
    ) {
        $this->replacementService = $replacementService;
        $this->accessoryService  = $accessoryService;
    }

    /**
     * Technician Inventory Management Hub — own stock only.
     */
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('view_inventory_management'), 403);

        $techId = auth()->id();

        // My router inventory
        $routerCategoryIds = TerminalCategory::where('category_type', 'router')
            ->where('status', 'active')
            ->pluck('id');

        $myRouters = InventorySerial::with('model.category')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $techId)
            ->whereHas('model', fn($q) => $q->whereIn('category_id', $routerCategoryIds))
            ->whereIn('current_status', ['issued_to_tech', 'installed', 'under_service'])
            ->orderBy('serial_no')
            ->get();

        // My accessory stock
        $accessoryCategoryIds = TerminalCategory::whereIn('category_type', ['sim', 'accessory'])
            ->where('status', 'active')
            ->pluck('id');

        $myAccessories = StockBalance::with('model.category')
            ->where('location_type', 'technician')
            ->where('location_id', $techId)
            ->whereHas('model', fn($q) => $q->whereIn('category_id', $accessoryCategoryIds))
            ->where('quantity_on_hand', '>', 0)
            ->get();

        // My replacements
        $myReplacements = Replacement::with(['oldModel', 'newModel', 'ticket'])
            ->where('technician_id', $techId)
            ->latest()
            ->take(10)
            ->get();

        // My accessory usage
        $myAccessoryUsage = AccessoryUsage::with(['ticket', 'model'])
            ->where('created_by', $techId)
            ->latest()
            ->take(10)
            ->get();

        return view('technician.inventory-management.index', compact(
            'myRouters', 'myAccessories', 'myReplacements', 'myAccessoryUsage'
        ));
    }

    // ── Replacement ──

    public function replacementForm(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('create_replacements'), 403);

        $techId = auth()->id();
        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $models = TerminalModel::with('category')->where('status', 'active')->orderBy('model_name')->get();

        $tickets = Ticket::where('technician_id', $techId)
            ->whereIn('status', ['assigned', 'accepted', 'in_progress', 'scheduled'])
            ->orderBy('ticket_no', 'desc')
            ->get(['id', 'ticket_no', 'merchant_name', 'router_id', 'terminal_id']);

        // My available serials for replacement
        $mySerials = InventorySerial::with('model')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $techId)
            ->where('current_status', InventorySerial::STATUS_ISSUED_TO_TECH)
            ->orderBy('serial_no')
            ->get();

        return view('technician.inventory-management.replacement', compact(
            'depots', 'models', 'tickets', 'mySerials'
        ));
    }

    public function storeReplacement(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('create_replacements'), 403);

        $request->validate([
            'replacement_date'      => 'required|date',
            'new_serial_id'         => 'required|exists:inventory_serials,id',
            'new_model_id'          => 'required|exists:terminal_models,id',
            'old_device_condition'   => 'required|in:good,damaged,defective,wasted',
            'old_device_destination' => 'required|in:return_to_depot,return_to_vendor,wastage',
            'reason'                => 'required|string|max:1000',
        ]);

        $data = $request->all();
        $data['technician_id'] = auth()->id();

        try {
            $replacement = $this->replacementService->createReplacement($data);
            return response()->json(['success' => true, 'message' => "Replacement #{$replacement->replacement_no} created."]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Accessory Usage ──

    public function myAccessories(Request $request): View
    {
        abort_unless(auth()->user()->hasPermissionTo('view_accessory_usage'), 403);

        $techId = auth()->id();

        $usages = AccessoryUsage::with(['ticket', 'model.category'])
            ->where('created_by', $techId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('technician.inventory-management.my-accessories', compact('usages'));
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

        $data = $request->all();
        $data['deducted_from_location_type'] = 'technician';
        $data['deducted_from_location_id']   = auth()->id();

        try {
            $usage = $this->accessoryService->recordUsage($data);
            return response()->json(['success' => true, 'message' => 'Accessory usage recorded.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function returnAccessory(AccessoryUsage $accessoryUsage, Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('return_accessory'), 403);

        // Ensure technician owns this usage
        if ($accessoryUsage->created_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

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
     * AJAX: Get available serials from technician's stock.
     */
    public function getMySerials(Request $request): JsonResponse
    {
        $techId = auth()->id();

        $query = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $techId)
            ->where('current_status', InventorySerial::STATUS_ISSUED_TO_TECH);

        if ($modelId = $request->input('model_id')) {
            $query->where('model_id', $modelId);
        }

        return response()->json(['serials' => $query->orderBy('serial_no')->get(['id', 'serial_no'])]);
    }
}
