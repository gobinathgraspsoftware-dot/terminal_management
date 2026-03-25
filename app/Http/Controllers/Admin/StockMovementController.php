<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\StockMovementExport;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class StockMovementController extends Controller
{
    protected InventoryService $service;

    public function __construct(InventoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Movements list page.
     */
    public function index()
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $items = InventoryItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);
        $movementTypes = StockMovement::getMovementTypes();

        return view('admin.inventory.movements', compact('items', 'movementTypes'));
    }

    /**
     * Movements DataTable AJAX.
     */
    public function datatable(Request $request)
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $result = $this->service->getMovementsDatatable($request->all());
        return response()->json($result);
    }

    /**
     * Export movements to Excel.
     */
    public function export(Request $request)
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $filters = $request->only(['movement_type', 'date_from', 'date_to', 'inventory_item_id']);

        return Excel::download(
            new StockMovementExport($filters),
            'stock_movements_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
