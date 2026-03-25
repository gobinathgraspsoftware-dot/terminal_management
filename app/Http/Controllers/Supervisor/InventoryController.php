<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockReturnRequest;
use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Inventory items list (supervisor view).
     */
    public function index()
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $stats = $this->service->getSummaryStats();

        return view('supervisor.inventory.index', compact('stats'));
    }

    /**
     * DataTable AJAX.
     */
    public function datatable(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $result = $this->service->getDatatable($request->all());
        return response()->json($result);
    }

    /**
     * AJAX - Get stock for a specific item.
     */
    public function getItemStock(Request $request)
    {
        $request->validate(['item_id' => 'required|exists:inventory_items,id']);

        $stock = $this->service->getItemStock($request->item_id);
        return response()->json($stock);
    }

    // ══════════════════════════════════════════════════════════
    // STOCK IN
    // ══════════════════════════════════════════════════════════

    /**
     * Stock In form.
     */
    public function stockInForm()
    {
        Gate::authorize('stockIn', InventoryItem::class);

        $routerItems = InventoryItem::routers()->active()->orderBy('item_name')->get();
        $accessoryItems = InventoryItem::accessories()->active()->orderBy('item_name')->get();

        return view('supervisor.inventory.stock-in', compact('routerItems', 'accessoryItems'));
    }

    /**
     * Process Stock In.
     */
    public function stockIn(StockInRequest $request)
    {
        Gate::authorize('stockIn', InventoryItem::class);

        try {
            $data = $request->validated();

            // If stock type is router and creating a new router item
            if ($data['stock_type'] === 'router' && empty($data['inventory_item_id'])) {
                $item = $this->service->createItem([
                    'item_name' => $data['item_name'],
                    'item_type' => InventoryItem::TYPE_ROUTER,
                    'brand' => $data['brand'] ?? null,
                    'model' => $data['model'] ?? null,
                    'reorder_level' => 1,
                    'status' => 'active',
                ]);
                $data['inventory_item_id'] = $item->id;
            }

            $movement = $this->service->stockIn($data);

            return redirect()
                ->route('supervisor.inventory.stock-in')
                ->with('success', "Stock In {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Stock In failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK OUT (List-Only View)
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Out list view (no manual create).
     */
    public function stockOutIndex()
    {
        Gate::authorize('stockOut', InventoryItem::class);

        return view('supervisor.inventory.stock-out');
    }

    /**
     * Stock Out DataTable AJAX.
     */
    public function stockOutDatatable(Request $request)
    {
        Gate::authorize('stockOut', InventoryItem::class);

        $result = $this->service->getStockOutDatatable($request->all());
        return response()->json($result);
    }

    // ══════════════════════════════════════════════════════════
    // STOCK RETURN (Manual + List)
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Return form + list.
     */
    public function stockReturnIndex()
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        $allItems = InventoryItem::active()->orderBy('item_name')->get();

        return view('supervisor.inventory.stock-return', compact('allItems'));
    }

    /**
     * Stock Return DataTable AJAX.
     */
    public function stockReturnDatatable(Request $request)
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        $result = $this->service->getStockReturnDatatable($request->all());
        return response()->json($result);
    }

    /**
     * Process manual Stock Return.
     */
    public function stockReturn(StockReturnRequest $request)
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        try {
            $movement = $this->service->stockReturn($request->validated());

            return redirect()
                ->route('supervisor.inventory.stock-return')
                ->with('success', "Stock Return {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Stock Return failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // MOVEMENTS (unchanged)
    // ══════════════════════════════════════════════════════════

    /**
     * Stock movements list.
     */
    public function movementsIndex()
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $items = InventoryItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);
        $movementTypes = StockMovement::getMovementTypes();

        return view('supervisor.inventory.movements', compact('items', 'movementTypes'));
    }

    /**
     * Movements DataTable AJAX.
     */
    public function movementsDatatable(Request $request)
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $result = $this->service->getMovementsDatatable($request->all());
        return response()->json($result);
    }
}
