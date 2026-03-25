<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    protected InventoryService $service;

    public function __construct(InventoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Technician inventory view - shows stock movements related to their tickets.
     */
    public function index()
    {
        $stats = $this->service->getSummaryStats();

        return view('technician.inventory.index', compact('stats'));
    }

    /**
     * DataTable AJAX for technician view.
     * Shows movements linked to technician's tickets.
     */
    public function datatable(Request $request)
    {
        $user = Auth::user();

        $query = StockMovement::with(['inventoryItem', 'performer', 'ticket'])
            ->whereHas('ticket', function ($q) use ($user) {
                $q->where('technician_id', $user->id);
            });

        $totalRecords = (clone $query)->count();

        $search = $request->input('search.value', '');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('movement_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('inventoryItem', fn($q2) => $q2->where('item_name', 'like', "%{$search}%")
                      ->orWhere('item_code', 'like', "%{$search}%"))
                  ->orWhereHas('ticket', fn($q2) => $q2->where('ticket_no', 'like', "%{$search}%"));
            });
        }

        $filteredRecords = $query->count();

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $sortable = [0 => 'movement_no', 1 => 'movement_type', 2 => 'movement_date', 3 => 'quantity'];
        $query->orderBy($sortable[$orderColumn] ?? 'created_at', $orderDir);

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $movements = $query->skip($start)->take($length)->get();

        $data = $movements->map(function ($m, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $m->id,
                'movement_no' => $m->movement_no,
                'item_name' => $m->inventoryItem->item_name ?? 'N/A',
                'movement_type' => $m->getTypeBadge(),
                'quantity' => $m->quantity,
                'router_ids' => $m->getRouterIdsDisplay(),
                'ticket_no' => $m->ticket->ticket_no ?? '-',
                'condition' => $m->getConditionBadge(),
                'movement_date' => $m->movement_date?->format('d M Y'),
                'date_label' => $m->getDateLabel(),
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
