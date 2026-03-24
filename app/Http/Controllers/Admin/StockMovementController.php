<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StockTransferRequest;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class StockMovementController extends Controller implements HasMiddleware
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
     * Stock movements listing.
     */
    public function index(): View
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $stats = [
            'total'       => StockMovement::count(),
            'stock_in'    => StockMovement::byType(StockMovement::TYPE_STOCK_IN)->count(),
            'stock_out'   => StockMovement::byType(StockMovement::TYPE_STOCK_OUT)->count(),
            'returns'     => StockMovement::byType(StockMovement::TYPE_STOCK_RETURN)->count(),
            'adjustments' => StockMovement::byType(StockMovement::TYPE_STOCK_ADJUSTMENT)->count(),
            'transfers'   => StockMovement::byType(StockMovement::TYPE_STOCK_TRANSFER)->count(),
        ];

        $items = InventoryItem::active()->orderBy('item_name')->get();

        return view('admin.inventory.movements', compact('stats', 'items'));
    }

    /**
     * Movements DataTable data.
     */
    public function datatable(Request $request): JsonResponse
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $query = StockMovement::with(['inventoryItem', 'performedBy', 'ticket'])
            ->select('stock_movements.*');

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->filled('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }
        if ($request->filled('date_from')) {
            $query->where('movement_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('movement_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('item_name', fn($m) => $m->inventoryItem->item_name ?? 'N/A')
            ->addColumn('type_badge', function ($m) {
                $color = StockMovement::getTypeBadgeColor($m->movement_type);
                $label = StockMovement::getTypeLabel($m->movement_type);
                return '<span class="badge bg-' . $color . '">' . $label . '</span>';
            })
            ->addColumn('qty_display', function ($m) {
                $prefix = $m->quantity > 0 ? '+' : '';
                $color = $m->quantity > 0 ? 'text-success' : 'text-danger';
                return '<span class="fw-bold ' . $color . '">' . $prefix . $m->quantity . '</span>';
            })
            ->addColumn('from_display', function ($m) {
                if (!$m->from_holder_type) return '-';
                if ($m->from_holder_type === 'warehouse') return 'Warehouse';
                return User::find($m->from_holder_id)?->name ?? 'Unknown';
            })
            ->addColumn('to_display', function ($m) {
                if (!$m->to_holder_type) return '-';
                if ($m->to_holder_type === 'warehouse') return 'Warehouse';
                return User::find($m->to_holder_id)?->name ?? 'Unknown';
            })
            ->addColumn('ticket_display', function ($m) {
                if ($m->ticket) {
                    return '<a href="' . route('admin.tickets.show', $m->ticket_id) . '">' . $m->ticket->ticket_no . '</a>';
                }
                return '-';
            })
            ->addColumn('performed_by_name', fn($m) => $m->performedBy->name ?? 'N/A')
            ->addColumn('date_display', fn($m) => $m->movement_date?->format('d M Y'))
            ->orderColumn('movement_date', 'movement_date $1')
            ->rawColumns(['type_badge', 'qty_display', 'ticket_display'])
            ->make(true);
    }

    /**
     * Stock transfer form.
     */
    public function transferForm(): View
    {
        Gate::authorize('stockTransfer', InventoryItem::class);

        $items = InventoryItem::active()->orderBy('item_name')->get();
        $technicians = User::role('technician')->where('status', 'active')->orderBy('name')->get();
        $pendingTransfers = StockTransfer::pending()
            ->with(['inventoryItem', 'createdBy'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.inventory.transfer', compact('items', 'technicians', 'pendingTransfers'));
    }

    /**
     * Create stock transfer.
     */
    public function createTransfer(StockTransferRequest $request): JsonResponse
    {
        Gate::authorize('stockTransfer', InventoryItem::class);

        try {
            $transfer = $this->inventoryService->createTransfer($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Transfer request created: ' . $transfer->transfer_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve stock transfer.
     */
    public function approveTransfer(StockTransfer $stockTransfer): JsonResponse
    {
        Gate::authorize('approveTransfer', InventoryItem::class);

        try {
            $transfer = $this->inventoryService->approveTransfer($stockTransfer);

            return response()->json([
                'success' => true,
                'message' => 'Transfer approved and completed: ' . $transfer->transfer_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Approve failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject stock transfer.
     */
    public function rejectTransfer(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        Gate::authorize('approveTransfer', InventoryItem::class);

        try {
            $transfer = $this->inventoryService->rejectTransfer(
                $stockTransfer,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Transfer rejected: ' . $transfer->transfer_no,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reject failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
