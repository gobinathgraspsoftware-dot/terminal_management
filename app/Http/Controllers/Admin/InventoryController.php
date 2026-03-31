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

    public function index()
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $stats = $this->service->getSummaryStats();
        return view('admin.inventory.index', compact('stats'));
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
    // CRUD
    // ══════════════════════════════════════════════════════════

    public function create()
    {
        Gate::authorize('create', InventoryItem::class);
        return view('admin.inventory.create');
    }

    public function store(StoreInventoryItemRequest $request)
    {
        Gate::authorize('create', InventoryItem::class);
        try {
            $item = $this->service->createItem($request->validated());
            return redirect()->route('admin.inventory.index')
                ->with('success', "Inventory item {$item->item_code} created successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Failed to create item: ' . $e->getMessage());
        }
    }

    public function show(InventoryItem $inventory_item)
    {
        Gate::authorize('view', $inventory_item);
        $inventory_item->load(['creator', 'updater']);
        $warehouseStock  = $inventory_item->getWarehouseStock();
        $totalStock      = $inventory_item->getTotalStock();
        $recentMovements = $inventory_item->stockMovements()
            ->with(['performer', 'ticket'])->orderBy('created_at', 'desc')->take(20)->get();
        $balances    = $inventory_item->stockBalances()->with('holder')->where('quantity', '>', 0)->get();
        $adjustments = $inventory_item->stockAdjustments()->with('adjustedBy')
            ->orderBy('adjusted_at', 'desc')->take(10)->get();
        return view('admin.inventory.show', compact(
            'inventory_item', 'warehouseStock', 'totalStock',
            'recentMovements', 'balances', 'adjustments'
        ));
    }

    public function edit(InventoryItem $inventory_item)
    {
        Gate::authorize('update', $inventory_item);
        return view('admin.inventory.edit', compact('inventory_item'));
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventory_item)
    {
        Gate::authorize('update', $inventory_item);
        try {
            $this->service->updateItem($inventory_item, $request->validated());
            return redirect()->route('admin.inventory.show', $inventory_item)
                ->with('success', 'Inventory item updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Failed to update item: ' . $e->getMessage());
        }
    }

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

    public function stockInForm()
    {
        Gate::authorize('stockIn', InventoryItem::class);
        $routerItems    = InventoryItem::routers()->active()->orderBy('item_name')->get();
        $accessoryItems = InventoryItem::accessories()->active()->orderBy('item_name')->get();
        return view('admin.inventory.stock-in', compact('routerItems', 'accessoryItems'));
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
            return redirect()->route('admin.inventory.stock-in')
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
        return view('admin.inventory.stock-out', compact('items', 'brands', 'models'));
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
        return view('admin.inventory.stock-return', compact('items', 'brands', 'models'));
    }

    /**
     * Stock Return dedicated create form.
     */
    public function stockReturnCreate()
    {
        Gate::authorize('stockReturn', InventoryItem::class);
        $allItems = InventoryItem::active()->orderBy('item_name')->get();
        return view('admin.inventory.stock-return-create', compact('allItems'));
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
            return redirect()->route('admin.inventory.stock-return')
                ->with('success', "Stock Return {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Stock Return failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK ADJUSTMENT
    // ══════════════════════════════════════════════════════════

    public function stockAdjustmentForm()
    {
        Gate::authorize('stockAdjustment', InventoryItem::class);
        $allItems = InventoryItem::active()
            ->with(['stockBalances' => fn($q) => $q->warehouse()])
            ->orderBy('item_name')->get();
        return view('admin.inventory.stock-adjustment', compact('allItems'));
    }

    public function stockAdjustment(StockAdjustmentRequest $request)
    {
        Gate::authorize('stockAdjustment', InventoryItem::class);
        try {
            $adjustment = $this->service->stockAdjustment($request->validated());
            return redirect()->route('admin.inventory.stock-adjustment')
                ->with('success', "Stock Adjustment {$adjustment->adjustment_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Adjustment failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // EXPORT
    // ══════════════════════════════════════════════════════════

    public function export(Request $request)
    {
        Gate::authorize('export', InventoryItem::class);
        return Excel::download(
            new InventoryExport($request->only(['item_type', 'status'])),
            'inventory_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
