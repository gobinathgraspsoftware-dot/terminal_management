<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class InventoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:technician'),
        ];
    }

    /**
     * My Inventory - view own stock.
     */
    public function index(): View
    {
        $technicianId = auth()->id();

        $myStock = StockBalance::where('holder_type', 'technician')
            ->where('holder_id', $technicianId)
            ->where('quantity', '>', 0)
            ->with('inventoryItem.jobCategory')
            ->get();

        $stats = [
            'total_items'   => $myStock->count(),
            'total_qty'     => $myStock->sum('quantity'),
            'routers'       => $myStock->filter(fn($b) => $b->inventoryItem?->item_type === 'router')->count(),
            'accessories'   => $myStock->filter(fn($b) => $b->inventoryItem?->item_type === 'accessory')->sum('quantity'),
        ];

        $recentMovements = StockMovement::where(function ($q) use ($technicianId) {
                $q->where(function ($sub) use ($technicianId) {
                    $sub->where('from_holder_type', 'technician')
                        ->where('from_holder_id', $technicianId);
                })->orWhere(function ($sub) use ($technicianId) {
                    $sub->where('to_holder_type', 'technician')
                        ->where('to_holder_id', $technicianId);
                });
            })
            ->with(['inventoryItem', 'performedBy'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('technician.inventory.index', compact('myStock', 'stats', 'recentMovements'));
    }

    /**
     * AJAX: DataTable for my stock.
     */
    public function datatable(Request $request): JsonResponse
    {
        $technicianId = auth()->id();

        $query = StockBalance::where('holder_type', 'technician')
            ->where('holder_id', $technicianId)
            ->where('quantity', '>', 0)
            ->with('inventoryItem.jobCategory');

        // Manual server-side pagination
        $totalRecords = $query->count();
        $filteredRecords = $totalRecords;

        // Search
        $search = $request->input('search.value');
        if ($search) {
            $query->whereHas('inventoryItem', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
            $filteredRecords = $query->count();
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $items = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($balance) {
            $item = $balance->inventoryItem;
            return [
                'item_code'     => $item->item_code ?? 'N/A',
                'item_name'     => $item->item_name ?? 'N/A',
                'category'      => $item->jobCategory->category_name ?? 'N/A',
                'type_badge'    => '<span class="badge bg-' . ($item->item_type === 'router' ? 'primary' : 'info') . '">' . ucfirst($item->item_type) . '</span>',
                'serial_number' => $item->serial_number ?? '-',
                'quantity'      => $balance->quantity,
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }
}
