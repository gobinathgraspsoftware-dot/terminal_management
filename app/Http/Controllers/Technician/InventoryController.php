<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Technician's inventory index - items assigned to them.
     */
    public function index()
    {
        $technicianId = auth()->id();

        // Summary stats
        $summary = $this->getSummary($technicianId);

        // Items assigned to this technician
        $assignedItems = StockBalance::where('holder_type', 'technician')
            ->where('holder_id', $technicianId)
            ->where('quantity', '>', 0)
            ->with(['inventoryItem.jobCategory'])
            ->get();

        return view('technician.inventory.index', compact('summary', 'assignedItems'));
    }

    /**
     * DataTable AJAX endpoint for technician's items.
     */
    public function datatable(Request $request)
    {
        $technicianId = auth()->id();

        $query = StockBalance::where('holder_type', 'technician')
            ->where('holder_id', $technicianId)
            ->where('quantity', '>', 0)
            ->with(['inventoryItem.jobCategory']);

        $totalRecords = (clone $query)->count();

        // Search
        $search = $request->input('search.value', '');
        if ($search) {
            $query->whereHas('inventoryItem', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $balances = $query->skip($start)->take($length)->get();

        $data = $balances->map(function ($balance, $index) use ($start) {
            $item = $balance->inventoryItem;
            return [
                'DT_RowIndex' => $start + $index + 1,
                'item_code' => $item->item_code ?? 'N/A',
                'item_name' => $item->item_name ?? 'N/A',
                'item_type' => $item->getTypeBadge(),
                'serial_number' => $item->serial_number ?? '-',
                'category' => $item->jobCategory->category_name ?? 'N/A',
                'quantity' => $balance->quantity,
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get summary statistics for technician.
     */
    protected function getSummary(int $technicianId): array
    {
        $balances = StockBalance::where('holder_type', 'technician')
            ->where('holder_id', $technicianId)
            ->where('quantity', '>', 0)
            ->with('inventoryItem')
            ->get();

        $totalItems = $balances->sum('quantity');
        $routerCount = $balances->filter(fn($b) => $b->inventoryItem?->isRouter())->sum('quantity');
        $accessoryCount = $balances->filter(fn($b) => $b->inventoryItem?->isAccessory())->sum('quantity');

        // Recent movements for this technician
        $recentMovements = StockMovement::where(function ($q) use ($technicianId) {
                $q->where(function ($q2) use ($technicianId) {
                    $q2->where('from_holder_type', 'technician')
                       ->where('from_holder_id', $technicianId);
                })->orWhere(function ($q2) use ($technicianId) {
                    $q2->where('to_holder_type', 'technician')
                       ->where('to_holder_id', $technicianId);
                });
            })
            ->count();

        return [
            'total_items' => $totalItems,
            'router_count' => $routerCount,
            'accessory_count' => $accessoryCount,
            'movement_count' => $recentMovements,
        ];
    }
}
