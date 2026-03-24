<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class StockMovementController extends Controller implements HasMiddleware
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

    protected function getTeamTechnicianIds(): array
    {
        return User::where('supervisor_id', auth()->id())
            ->pluck('id')
            ->toArray();
    }

    /**
     * Team stock movements listing.
     */
    public function index(): View
    {
        $techIds = $this->getTeamTechnicianIds();

        $stats = [
            'total'       => StockMovement::where(function ($q) use ($techIds) {
                                $q->whereIn('from_holder_id', $techIds)
                                  ->orWhereIn('to_holder_id', $techIds);
                             })->count(),
            'stock_out'   => StockMovement::byType(StockMovement::TYPE_STOCK_OUT)
                                ->whereIn('to_holder_id', $techIds)->count(),
            'returns'     => StockMovement::byType(StockMovement::TYPE_STOCK_RETURN)
                                ->whereIn('from_holder_id', $techIds)->count(),
        ];

        $items = InventoryItem::active()->orderBy('item_name')->get();

        return view('supervisor.inventory.movements', compact('stats', 'items'));
    }

    /**
     * Movements DataTable - scoped to team.
     */
    public function datatable(Request $request): JsonResponse
    {
        $techIds = $this->getTeamTechnicianIds();

        $query = StockMovement::with(['inventoryItem', 'performedBy'])
            ->select('stock_movements.*')
            ->where(function ($q) use ($techIds) {
                $q->whereIn('from_holder_id', $techIds)
                  ->orWhereIn('to_holder_id', $techIds)
                  ->orWhere('performed_by', auth()->id());
            });

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
            ->addColumn('performed_by_name', fn($m) => $m->performedBy->name ?? 'N/A')
            ->addColumn('date_display', fn($m) => $m->movement_date?->format('d M Y'))
            ->rawColumns(['type_badge', 'qty_display'])
            ->make(true);
    }

    /**
     * Transfer form.
     */
    public function transferForm(): View
    {
        $items = InventoryItem::active()->orderBy('item_name')->get();
        $techIds = $this->getTeamTechnicianIds();
        $technicians = User::whereIn('id', $techIds)->where('status', 'active')->orderBy('name')->get();

        return view('supervisor.inventory.transfer', compact('items', 'technicians'));
    }

    /**
     * Create transfer request.
     */
    public function createTransfer(Request $request): JsonResponse
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'from_holder_type'  => 'required|in:warehouse,technician',
            'from_holder_id'    => 'nullable|exists:users,id',
            'to_holder_type'    => 'required|in:warehouse,technician',
            'to_holder_id'      => 'nullable|exists:users,id',
            'quantity'          => 'required|integer|min:1',
            'transfer_date'     => 'required|date',
            'reason'            => 'nullable|string|max:500',
        ]);

        try {
            $transfer = $this->inventoryService->createTransfer($request->all());

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
}
