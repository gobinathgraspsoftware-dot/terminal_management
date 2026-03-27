<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockReturnRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Exports\InventoryExport;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

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
     * List inventory items.
     */
    public function index()
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $stats = $this->service->getSummaryStats();

        return view('admin.inventory.index', compact('stats'));
    }

    /**
     * DataTable AJAX endpoint.
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
    // CRUD
    // ══════════════════════════════════════════════════════════

    /**
     * Show create form.
     */
    public function create()
    {
        Gate::authorize('create', InventoryItem::class);

        return view('admin.inventory.create');
    }

    /**
     * Store new inventory item.
     */
    public function store(StoreInventoryItemRequest $request)
    {
        Gate::authorize('create', InventoryItem::class);

        try {
            $item = $this->service->createItem($request->validated());

            return redirect()
                ->route('admin.inventory.index')
                ->with('success', "Inventory item {$item->item_code} created successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create item: ' . $e->getMessage());
        }
    }

    /**
     * Show item details.
     */
    public function show(InventoryItem $inventory_item)
    {
        Gate::authorize('view', $inventory_item);

        $inventory_item->load(['creator', 'updater']);

        $warehouseStock = $inventory_item->getWarehouseStock();
        $totalStock = $inventory_item->getTotalStock();

        // Recent movements for this item
        $recentMovements = $inventory_item->stockMovements()
            ->with(['performer', 'ticket'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Stock balances across all locations
        $balances = $inventory_item->stockBalances()
            ->with('holder')
            ->where('quantity', '>', 0)
            ->get();

        // Adjustments history
        $adjustments = $inventory_item->stockAdjustments()
            ->with('adjustedBy')
            ->orderBy('adjusted_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.inventory.show', compact(
            'inventory_item', 'warehouseStock', 'totalStock',
            'recentMovements', 'balances', 'adjustments'
        ));
    }

    /**
     * Show edit form.
     */
    public function edit(InventoryItem $inventory_item)
    {
        Gate::authorize('update', $inventory_item);

        return view('admin.inventory.edit', compact('inventory_item'));
    }

    /**
     * Update inventory item.
     */
    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventory_item)
    {
        Gate::authorize('update', $inventory_item);

        try {
            $this->service->updateItem($inventory_item, $request->validated());

            return redirect()
                ->route('admin.inventory.show', $inventory_item)
                ->with('success', 'Inventory item updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update item: ' . $e->getMessage());
        }
    }

    /**
     * Delete inventory item.
     */
    public function destroy(InventoryItem $inventory_item)
    {
        Gate::authorize('delete', $inventory_item);

        try {
            $this->service->deleteItem($inventory_item);

            return response()->json(['success' => true, 'message' => 'Item deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Toggle item status.
     */
    public function toggleStatus(InventoryItem $inventory_item)
    {
        Gate::authorize('update', $inventory_item);

        $item = $this->service->toggleStatus($inventory_item);

        return response()->json([
            'success' => true,
            'message' => "Item status changed to {$item->status}.",
            'status'  => $item->status,
        ]);
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

        return view('admin.inventory.stock-in', compact('routerItems', 'accessoryItems'));
    }

    /**
     * Process Stock In.
     *
     * FIX: router_ids[] are now included in validated data and passed
     *      directly to service. The service handles quantity derivation
     *      from router_ids count for router items.
     */
    public function stockIn(StockInRequest $request)
    {
        Gate::authorize('stockIn', InventoryItem::class);

        try {
            $data = $request->validated();

            // If stock type is router and creating a new router item
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

            // Service reads router_ids, stockin_date directly from $data
            $movement = $this->service->stockIn($data);

            return redirect()
                ->route('admin.inventory.stock-in')
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
     * Stock Out list (view only - no manual create form).
     * Stock out is auto-triggered when installation ticket is created.
     */
    public function stockOutIndex()
    {
        Gate::authorize('stockOut', InventoryItem::class);

        return view('admin.inventory.stock-out');
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
    // STOCK RETURN (Manual + Auto from Replacement)
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Return form + list.
     */
    public function stockReturnIndex()
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        $allItems = InventoryItem::active()->orderBy('item_name')->get();

        return view('admin.inventory.stock-return', compact('allItems'));
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
     *
     * FIX: router_ids[] and stockreturn_date are now included in
     *      validated data and passed directly to service.
     */
    public function stockReturn(StockReturnRequest $request)
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        try {
            $movement = $this->service->stockReturn($request->validated());

            return redirect()
                ->route('admin.inventory.stock-return')
                ->with('success', "Stock Return {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Stock Return failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK ADJUSTMENT
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Adjustment form.
     */
    public function stockAdjustmentForm()
    {
        Gate::authorize('stockAdjustment', InventoryItem::class);

        $allItems = InventoryItem::active()
            ->with(['stockBalances' => function ($q) {
                $q->warehouse();
            }])
            ->orderBy('item_name')
            ->get();

        return view('admin.inventory.stock-adjustment', compact('allItems'));
    }

    /**
     * Process Stock Adjustment.
     */
    public function stockAdjustment(StockAdjustmentRequest $request)
    {
        Gate::authorize('stockAdjustment', InventoryItem::class);

        try {
            $adjustment = $this->service->stockAdjustment($request->validated());

            return redirect()
                ->route('admin.inventory.stock-adjustment')
                ->with('success', "Stock Adjustment {$adjustment->adjustment_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Adjustment failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // EXPORT
    // ══════════════════════════════════════════════════════════

    /**
     * Export inventory to Excel.
     */
    public function export(Request $request)
    {
        Gate::authorize('export', InventoryItem::class);

        $filters = $request->only(['item_type', 'status']);

        return Excel::download(
            new InventoryExport($filters),
            'inventory_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
