<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\JobCategory;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller implements HasMiddleware
{
    protected InventoryService $inventoryService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:supervisor'),
        ];
    }

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get team technician IDs for scoping.
     */
    protected function getTeamTechnicianIds(): array
    {
        return User::where('supervisor_id', auth()->id())
            ->pluck('id')
            ->toArray();
    }

    /**
     * Inventory index - view items + team stock.
     */
    public function index(): View
    {
        $techIds = $this->getTeamTechnicianIds();

        $stats = [
            'total_items'   => InventoryItem::active()->count(),
            'team_members'  => count($techIds),
            'team_stock'    => StockBalance::where('holder_type', 'technician')
                                ->whereIn('holder_id', $techIds)
                                ->sum('quantity'),
            'warehouse'     => StockBalance::where('holder_type', 'warehouse')
                                ->whereNull('holder_id')
                                ->sum('quantity'),
        ];

        $categories = JobCategory::active()->orderBy('category_name')->get();
        $items = InventoryItem::active()->orderBy('item_name')->get();
        $technicians = User::whereIn('id', $techIds)->where('status', 'active')->orderBy('name')->get();

        return view('supervisor.inventory.index', compact('stats', 'categories', 'items', 'technicians'));
    }

    /**
     * DataTable data - inventory items with stock info.
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = InventoryItem::with(['jobCategory'])
            ->active()
            ->select('inventory_items.*');

        if ($request->filled('item_type')) {
            $query->where('item_type', $request->item_type);
        }
        if ($request->filled('job_category_id')) {
            $query->where('job_category_id', $request->job_category_id);
        }

        return DataTables::of($query)
            ->addColumn('category_name', fn($item) => $item->jobCategory->category_name ?? 'N/A')
            ->addColumn('type_badge', function ($item) {
                $color = $item->item_type === 'router' ? 'primary' : 'info';
                return '<span class="badge bg-' . $color . '">' . ucfirst($item->item_type) . '</span>';
            })
            ->addColumn('warehouse_qty', fn($item) => $item->warehouse_stock)
            ->addColumn('total_qty', fn($item) => $item->total_stock)
            ->addColumn('status_badge', function ($item) {
                $color = $item->status === InventoryItem::STATUS_ACTIVE ? 'success' : 'danger';
                return '<span class="badge bg-' . $color . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return '<button type="button" class="btn btn-sm btn-outline-info btn-view-stock" data-id="' . $item->id . '" data-name="' . htmlspecialchars($item->item_name) . '" title="View Stock"><i class="bi bi-eye"></i> Stock</button>';
            })
            ->rawColumns(['type_badge', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Stock In form.
     */
    public function stockInForm(): View
    {
        $items = InventoryItem::active()->orderBy('item_name')->get();

        return view('supervisor.inventory.stock-in', compact('items'));
    }

    /**
     * Process Stock In.
     */
    public function stockIn(Request $request): JsonResponse
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity'          => 'required|integer|min:1',
            'movement_date'     => 'required|date',
            'reason'            => 'nullable|string|max:500',
            'remarks'           => 'nullable|string|max:1000',
        ]);

        try {
            $movement = $this->inventoryService->stockIn($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Stock In processed: ' . $movement->movement_no,
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
        $items = InventoryItem::active()->orderBy('item_name')->get();
        $techIds = $this->getTeamTechnicianIds();
        $technicians = User::whereIn('id', $techIds)->where('status', 'active')->orderBy('name')->get();

        return view('supervisor.inventory.stock-out', compact('items', 'technicians'));
    }

    /**
     * Process Stock Out.
     */
    public function stockOut(Request $request): JsonResponse
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'technician_id'     => 'required|exists:users,id',
            'quantity'          => 'required|integer|min:1',
            'movement_date'     => 'required|date',
            'ticket_id'         => 'nullable|exists:tickets,id',
            'reason'            => 'nullable|string|max:500',
            'remarks'           => 'nullable|string|max:1000',
        ]);

        // Validate technician is in team
        $techIds = $this->getTeamTechnicianIds();
        if (!in_array($request->technician_id, $techIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected technician is not in your team.',
            ], 403);
        }

        try {
            $movement = $this->inventoryService->stockOut($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Stock Out processed: ' . $movement->movement_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock Out failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process Stock Return.
     */
    public function stockReturn(Request $request): JsonResponse
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'technician_id'     => 'required|exists:users,id',
            'quantity'          => 'required|integer|min:1',
            'movement_date'     => 'required|date',
            'reason'            => 'nullable|string|max:500',
            'remarks'           => 'nullable|string|max:1000',
        ]);

        $techIds = $this->getTeamTechnicianIds();
        if (!in_array($request->technician_id, $techIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected technician is not in your team.',
            ], 403);
        }

        try {
            $movement = $this->inventoryService->stockReturn($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Stock Return processed: ' . $movement->movement_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock Return failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX: Get item stock for team.
     */
    public function getItemStock(Request $request): JsonResponse
    {
        $itemId = $request->input('item_id');
        $item = InventoryItem::find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $techIds = $this->getTeamTechnicianIds();
        $techStock = StockBalance::where('inventory_item_id', $itemId)
            ->where('holder_type', 'technician')
            ->whereIn('holder_id', $techIds)
            ->where('quantity', '>', 0)
            ->get()
            ->map(fn($b) => [
                'technician_id'   => $b->holder_id,
                'technician_name' => User::find($b->holder_id)?->name ?? 'Unknown',
                'quantity'        => $b->quantity,
            ]);

        return response()->json([
            'success'         => true,
            'warehouse_stock' => $item->warehouse_stock,
            'tech_stock'      => $techStock,
            'item_type'       => $item->item_type,
        ]);
    }
}
