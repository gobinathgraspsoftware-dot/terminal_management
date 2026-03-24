<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Admin\Inventory\UpdateInventoryItemRequest;
use App\Http\Requests\Admin\Inventory\StockInRequest;
use App\Http\Requests\Admin\Inventory\StockOutRequest;
use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\JobCategory;
use App\Models\User;
use App\Services\InventoryService;
use App\Exports\InventoryExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller implements HasMiddleware
{
    protected InventoryService $inventoryService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:admin'),
        ];
    }

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Inventory index page.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $stats = [
            'total'      => InventoryItem::count(),
            'routers'    => InventoryItem::routers()->count(),
            'accessories' => InventoryItem::accessories()->count(),
            'active'     => InventoryItem::active()->count(),
            'low_stock'  => $this->inventoryService->getLowStockItems()->count(),
        ];

        $categories = JobCategory::active()->orderBy('category_name')->get();

        return view('admin.inventory.index', compact('stats', 'categories'));
    }

    /**
     * DataTable data.
     */
    public function datatable(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $query = InventoryItem::with(['jobCategory'])
            ->select('inventory_items.*');

        if ($request->filled('item_type')) {
            $query->where('item_type', $request->item_type);
        }
        if ($request->filled('job_category_id')) {
            $query->where('job_category_id', $request->job_category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('category_name', fn($item) => $item->jobCategory->category_name ?? 'N/A')
            ->addColumn('type_badge', function ($item) {
                $color = $item->item_type === 'router' ? 'primary' : 'info';
                return '<span class="badge bg-' . $color . '">' . ucfirst($item->item_type) . '</span>';
            })
            ->addColumn('warehouse_qty', fn($item) => $item->warehouse_stock)
            ->addColumn('total_qty', fn($item) => $item->total_stock)
            ->addColumn('stock_status', function ($item) {
                if ($item->isLowStock()) {
                    return '<span class="badge bg-danger">Low Stock</span>';
                }
                return '<span class="badge bg-success">OK</span>';
            })
            ->addColumn('status_badge', function ($item) {
                $color = $item->status === InventoryItem::STATUS_ACTIVE ? 'success' : 'danger';
                return '<span class="badge bg-' . $color . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $actions = '';

                $actions .= '<a href="' . route('admin.inventory.show', $item->id) . '" class="btn btn-sm btn-outline-info me-1" title="View"><i class="bi bi-eye"></i></a>';

                if (auth()->user()->can('update', $item)) {
                    $actions .= '<a href="' . route('admin.inventory.edit', $item->id) . '" class="btn btn-sm btn-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>';

                    $statusIcon = $item->status === InventoryItem::STATUS_ACTIVE
                        ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary me-1 btn-toggle-status" data-id="' . $item->id . '" data-status="' . $item->status . '" title="Toggle Status"><i class="bi ' . $statusIcon . '"></i></button>';
                }

                if (auth()->user()->can('delete', $item)) {
                    $actions .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $item->id . '" data-name="' . htmlspecialchars($item->item_name) . '" title="Delete"><i class="bi bi-trash"></i></button>';
                }

                return '<div class="d-flex flex-nowrap gap-1">' . $actions . '</div>';
            })
            ->rawColumns(['type_badge', 'stock_status', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Create form.
     */
    public function create(): View
    {
        Gate::authorize('create', InventoryItem::class);

        $categories = JobCategory::active()->orderBy('category_name')->get();

        return view('admin.inventory.create', compact('categories'));
    }

    /**
     * Store new item.
     */
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        try {
            $item = $this->inventoryService->createItem($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Inventory item created successfully.',
                'item'    => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show item details with stock history.
     */
    public function show(InventoryItem $inventoryItem): View
    {
        Gate::authorize('view', $inventoryItem);

        $inventoryItem->load(['jobCategory', 'stockBalances', 'createdBy']);

        $balances = StockBalance::where('inventory_item_id', $inventoryItem->id)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($b) {
                $b->holder_name = $b->holder_type === 'warehouse'
                    ? 'Warehouse'
                    : (User::find($b->holder_id)?->name ?? 'Unknown');
                return $b;
            });

        $recentMovements = StockMovement::where('inventory_item_id', $inventoryItem->id)
            ->with(['performedBy', 'ticket'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.inventory.show', compact('inventoryItem', 'balances', 'recentMovements'));
    }

    /**
     * Edit form.
     */
    public function edit(InventoryItem $inventoryItem): View
    {
        Gate::authorize('update', $inventoryItem);

        $categories = JobCategory::active()->orderBy('category_name')->get();

        return view('admin.inventory.edit', compact('inventoryItem', 'categories'));
    }

    /**
     * Update item.
     */
    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        try {
            $item = $this->inventoryService->updateItem($inventoryItem, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Inventory item updated successfully.',
                'item'    => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete item.
     */
    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        Gate::authorize('delete', $inventoryItem);

        try {
            $this->inventoryService->deleteItem($inventoryItem);

            return response()->json([
                'success' => true,
                'message' => 'Inventory item deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(InventoryItem $inventoryItem): JsonResponse
    {
        Gate::authorize('update', $inventoryItem);

        try {
            $item = $this->inventoryService->toggleStatus($inventoryItem);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'status'  => $item->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stock In form.
     */
    public function stockInForm(): View
    {
        Gate::authorize('stockIn', InventoryItem::class);

        $items = InventoryItem::active()->orderBy('item_name')->get();

        return view('admin.inventory.stock-in', compact('items'));
    }

    /**
     * Process Stock In.
     */
    public function stockIn(StockInRequest $request): JsonResponse
    {
        Gate::authorize('stockIn', InventoryItem::class);

        try {
            $movement = $this->inventoryService->stockIn($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stock In processed successfully. Movement: ' . $movement->movement_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock In failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stock Out form.
     */
    public function stockOutForm(): View
    {
        Gate::authorize('stockOut', InventoryItem::class);

        $items = InventoryItem::active()->orderBy('item_name')->get();
        $technicians = User::role('technician')->where('status', 'active')->orderBy('name')->get();

        return view('admin.inventory.stock-out', compact('items', 'technicians'));
    }

    /**
     * Process Stock Out.
     */
    public function stockOut(StockOutRequest $request): JsonResponse
    {
        Gate::authorize('stockOut', InventoryItem::class);

        try {
            $movement = $this->inventoryService->stockOut($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stock Out processed successfully. Movement: ' . $movement->movement_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock Out failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stock Return form.
     */
    public function stockReturnForm(): View
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        $items = InventoryItem::active()->orderBy('item_name')->get();
        $technicians = User::role('technician')->where('status', 'active')->orderBy('name')->get();

        return view('admin.inventory.stock-out', [
            'items'       => $items,
            'technicians' => $technicians,
            'mode'        => 'return',
        ]);
    }

    /**
     * Process Stock Return.
     */
    public function stockReturn(Request $request): JsonResponse
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'technician_id'     => 'required|exists:users,id',
            'quantity'          => 'required|integer|min:1',
            'movement_date'     => 'required|date',
            'ticket_id'         => 'nullable|exists:tickets,id',
            'reason'            => 'nullable|string|max:500',
            'remarks'           => 'nullable|string|max:1000',
        ]);

        try {
            $movement = $this->inventoryService->stockReturn($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Stock Return processed successfully. Movement: ' . $movement->movement_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock Return failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stock Adjustment form + process.
     */
    public function stockAdjustment(Request $request): JsonResponse
    {
        Gate::authorize('stockAdjustment', InventoryItem::class);

        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity'          => 'required|integer|not_in:0',
            'holder_type'       => 'required|in:warehouse,technician',
            'holder_id'         => 'nullable|exists:users,id',
            'movement_date'     => 'required|date',
            'reason'            => 'required|string|max:500',
            'remarks'           => 'nullable|string|max:1000',
        ]);

        try {
            $movement = $this->inventoryService->stockAdjustment($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Stock Adjustment processed successfully. Movement: ' . $movement->movement_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Adjustment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX: Get item warehouse stock.
     */
    public function getItemStock(Request $request): JsonResponse
    {
        $itemId = $request->input('item_id');
        $item = InventoryItem::find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $warehouseStock = $item->warehouse_stock;

        // Get technician-held stock
        $techStock = StockBalance::where('inventory_item_id', $itemId)
            ->where('holder_type', 'technician')
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($b) {
                return [
                    'technician_id'   => $b->holder_id,
                    'technician_name' => User::find($b->holder_id)?->name ?? 'Unknown',
                    'quantity'        => $b->quantity,
                ];
            });

        return response()->json([
            'success'         => true,
            'warehouse_stock' => $warehouseStock,
            'total_stock'     => $item->total_stock,
            'tech_stock'      => $techStock,
            'item_type'       => $item->item_type,
        ]);
    }

    /**
     * Export inventory items.
     */
    public function export(Request $request)
    {
        Gate::authorize('export', InventoryItem::class);

        $fileName = 'inventory_items_' . date('Y_m_d_His') . '.xlsx';

        return Excel::download(
            new InventoryExport(
                $request->input('item_type'),
                $request->input('status'),
                $request->input('job_category_id')
            ),
            $fileName
        );
    }
}
