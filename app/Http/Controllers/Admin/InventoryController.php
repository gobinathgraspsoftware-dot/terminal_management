<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Http\Requests\StockReturnRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\JobCategory;
use App\Models\StockBalance;
use App\Models\Ticket;
use App\Models\User;
use App\Exports\InventoryExport;
use App\Services\InventoryService;
use Illuminate\Http\Request;
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
        $this->authorize('viewAny', InventoryItem::class);

        $stats = $this->service->getSummaryStats();
        $jobCategories = JobCategory::active()->orderBy('category_name')->get();

        return view('admin.inventory.index', compact('stats', 'jobCategories'));
    }

    /**
     * DataTable AJAX endpoint.
     */
    public function datatable(Request $request)
    {
        $this->authorize('viewAny', InventoryItem::class);

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
        $this->authorize('create', InventoryItem::class);

        $jobCategories = JobCategory::active()->orderBy('category_name')->get();

        return view('admin.inventory.create', compact('jobCategories'));
    }

    /**
     * Store new inventory item.
     */
    public function store(StoreInventoryItemRequest $request)
    {
        $this->authorize('create', InventoryItem::class);

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
        $this->authorize('view', $inventory_item);

        $inventory_item->load(['jobCategory', 'creator', 'updater']);

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
        $this->authorize('update', $inventory_item);

        $jobCategories = JobCategory::active()->orderBy('category_name')->get();

        return view('admin.inventory.edit', compact('inventory_item', 'jobCategories'));
    }

    /**
     * Update inventory item.
     */
    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventory_item)
    {
        $this->authorize('update', $inventory_item);

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
        $this->authorize('delete', $inventory_item);

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
        $this->authorize('update', $inventory_item);

        $item = $this->service->toggleStatus($inventory_item);

        return response()->json([
            'success' => true,
            'message' => "Item status changed to {$item->status}.",
            'status' => $item->status,
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
        $this->authorize('stockIn', InventoryItem::class);

        $jobCategories = JobCategory::active()->orderBy('category_name')->get();
        $routerItems = InventoryItem::routers()->active()->orderBy('item_name')->get();
        $accessoryItems = InventoryItem::accessories()->active()->orderBy('item_name')->get();

        return view('admin.inventory.stock-in', compact('jobCategories', 'routerItems', 'accessoryItems'));
    }

    /**
     * Process Stock In.
     */
    public function stockIn(StockInRequest $request)
    {
        $this->authorize('stockIn', InventoryItem::class);

        try {
            $data = $request->validated();

            // If stock type is router and creating a new router item
            if ($data['stock_type'] === 'router' && empty($data['inventory_item_id'])) {
                // Create the router item first
                $item = $this->service->createItem([
                    'item_name' => $data['item_name'],
                    'job_category_id' => $data['job_category_id'],
                    'item_type' => InventoryItem::TYPE_ROUTER,
                    'serial_number' => $data['serial_number'],
                    'brand' => $data['brand'] ?? null,
                    'model' => $data['model'] ?? null,
                    'reorder_level' => 1,
                    'status' => 'active',
                ]);
                $data['inventory_item_id'] = $item->id;
            }

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
    // STOCK OUT (linked to Ticket)
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Out form.
     */
    public function stockOutForm()
    {
        $this->authorize('stockOut', InventoryItem::class);

        $availableRouters = $this->service->getAvailableRouters();
        $availableAccessories = $this->service->getAvailableAccessories();

        // Tickets that are open/assigned/in_progress for linking
        $tickets = Ticket::whereIn('status', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_ASSIGNED,
                Ticket::STATUS_ACCEPTED,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_SCHEDULED,
            ])
            ->select('id', 'ticket_no', 'merchant_name', 'technician_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $technicians = User::role('technician')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id']);

        return view('admin.inventory.stock-out', compact(
            'availableRouters', 'availableAccessories', 'tickets', 'technicians'
        ));
    }

    /**
     * Process Stock Out.
     */
    public function stockOut(StockOutRequest $request)
    {
        $this->authorize('stockOut', InventoryItem::class);

        try {
            $movement = $this->service->stockOut($request->validated());

            return redirect()
                ->route('admin.inventory.stock-out')
                ->with('success', "Stock Out {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Stock Out failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK RETURN
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Return form.
     */
    public function stockReturnForm()
    {
        $this->authorize('stockReturn', InventoryItem::class);

        $allItems = InventoryItem::active()->orderBy('item_name')->get();
        $technicians = User::role('technician')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id']);

        return view('admin.inventory.stock-return', compact('allItems', 'technicians'));
    }

    /**
     * Process Stock Return.
     */
    public function stockReturn(StockReturnRequest $request)
    {
        $this->authorize('stockReturn', InventoryItem::class);

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
        $this->authorize('stockAdjustment', InventoryItem::class);

        $allItems = InventoryItem::active()
            ->with(['stockBalances' => fn($q) => $q->warehouse()])
            ->orderBy('item_name')
            ->get();

        return view('admin.inventory.stock-adjustment', compact('allItems'));
    }

    /**
     * Process Stock Adjustment.
     */
    public function stockAdjustment(StockAdjustmentRequest $request)
    {
        $this->authorize('stockAdjustment', InventoryItem::class);

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
        $this->authorize('export', InventoryItem::class);

        $filters = $request->only(['item_type', 'status']);

        return Excel::download(
            new InventoryExport($filters),
            'inventory_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
