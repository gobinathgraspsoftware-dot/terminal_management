<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockReturnRequest;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    protected InventoryService $service;

    public function __construct(InventoryService $service)
    {
        $this->service = $service;
    }

    // ══════════════════════════════════════════════════════════
    // INDEX + DATATABLE
    // ══════════════════════════════════════════════════════════

    public function index()
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $stats = $this->service->getSummaryStats();
        return view('supervisor.inventory.index', compact('stats'));
    }

    public function datatable(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);
        return response()->json($this->service->getDatatable($request->all()));
    }

    public function getItemStock(Request $request)
    {
        $request->validate(['item_id' => 'required|exists:inventory_items,id']);
        return response()->json($this->service->getItemStock($request->item_id));
    }

    // ══════════════════════════════════════════════════════════
    // STOCK IN
    // ══════════════════════════════════════════════════════════

    public function stockInForm()
    {
        Gate::authorize('stockIn', InventoryItem::class);
        $routerItems    = InventoryItem::routers()->active()->orderBy('item_name')->get();
        $accessoryItems = InventoryItem::accessories()->active()->orderBy('item_name')->get();
        return view('supervisor.inventory.stock-in', compact('routerItems', 'accessoryItems'));
    }

    public function stockIn(StockInRequest $request)
    {
        Gate::authorize('stockIn', InventoryItem::class);
        try {
            $data = $request->validated();
            if ($data['stock_type'] === 'router' && empty($data['inventory_item_id'])) {
                $item = $this->service->createItem([
                    'item_name'     => $data['item_name'],
                    'item_type'     => InventoryItem::TYPE_ROUTER,
                    'brand'         => $data['brand'] ?? null,
                    'model'         => $data['model'] ?? null,
                    'reorder_level' => 1,
                    'status'        => 'active',
                ]);
                $data['inventory_item_id'] = $item->id;
            }
            $movement = $this->service->stockIn($data);
            return redirect()->route('supervisor.inventory.stock-in')
                ->with('success', "Stock In {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Stock In failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK OUT (List-Only View)
    // ══════════════════════════════════════════════════════════

    public function stockOutIndex()
    {
        Gate::authorize('stockOut', InventoryItem::class);
        $items  = InventoryItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name', 'item_type', 'brand', 'model']);
        $brands = InventoryItem::whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand');
        $models = InventoryItem::whereNotNull('model')->where('model', '!=', '')->distinct()->orderBy('model')->pluck('model');
        return view('supervisor.inventory.stock-out', compact('items', 'brands', 'models'));
    }

    public function stockOutDatatable(Request $request)
    {
        Gate::authorize('stockOut', InventoryItem::class);
        return response()->json($this->service->getStockOutDatatable($request->all()));
    }

    // ══════════════════════════════════════════════════════════
    // STOCK RETURN
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Return list page (filter + datatable only — no form here).
     */
    public function stockReturnIndex()
    {
        Gate::authorize('stockReturn', InventoryItem::class);
        $brands = InventoryItem::whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand');
        $models = InventoryItem::whereNotNull('model')->where('model', '!=', '')->distinct()->orderBy('model')->pluck('model');
        $items  = InventoryItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name', 'item_type', 'brand', 'model']);
        return view('supervisor.inventory.stock-return', compact('items', 'brands', 'models'));
    }

    /**
     * Stock Return dedicated create form.
     */
    public function stockReturnCreate()
    {
        Gate::authorize('stockReturn', InventoryItem::class);
        $allItems = InventoryItem::active()->orderBy('item_name')->get();
        return view('supervisor.inventory.stock-return-create', compact('allItems'));
    }

    public function stockReturnDatatable(Request $request)
    {
        Gate::authorize('stockReturn', InventoryItem::class);
        return response()->json($this->service->getStockReturnDatatable($request->all()));
    }

    /**
     * Process manual Stock Return (handles both router_ids and plain quantity).
     */
    public function stockReturn(StockReturnRequest $request)
    {
        Gate::authorize('stockReturn', InventoryItem::class);
        try {
            $movement = $this->service->stockReturn($request->validated());
            return redirect()->route('supervisor.inventory.stock-return')
                ->with('success', "Stock Return {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Stock Return failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // MOVEMENTS (Read-Only)
    // ══════════════════════════════════════════════════════════

    public function movementsIndex()
    {
        Gate::authorize('viewMovements', InventoryItem::class);
        $movementTypes = StockMovement::getMovementTypes();
        $items  = InventoryItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name', 'item_type', 'brand', 'model']);
        $brands = InventoryItem::whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand');
        $models = InventoryItem::whereNotNull('model')->where('model', '!=', '')->distinct()->orderBy('model')->pluck('model');
        return view('supervisor.inventory.movements', compact('movementTypes', 'items', 'brands', 'models'));
    }

    public function movementsDatatable(Request $request)
    {
        Gate::authorize('viewMovements', InventoryItem::class);
        return response()->json($this->service->getMovementsDatatable($request->all()));
    }
}
