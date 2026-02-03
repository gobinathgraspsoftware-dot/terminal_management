<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\InventorySerial;
use App\Services\InventorySerialService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class InventorySerialController extends Controller
{
    use AuthorizesRequests;

    protected InventorySerialService $service;

    public function __construct(InventorySerialService $service)
    {
        $this->service = $service;
    }

    /**
     * Display listing - own issued stock only.
     */
    public function index(Request $request)
    {
        $this->authorize('view_inventory');

        $user = auth()->user();

        if ($request->ajax()) {
            return $this->service->getDataTable($request, 'technician', $user->id);
        }

        // Count technician's own stock
        $myStockCount = InventorySerial::withTechnician($user->id)->count();
        $myAvailableCount = InventorySerial::withTechnician($user->id)
            ->where('current_status', InventorySerial::STATUS_ISSUED_TO_TECH)
            ->count();

        return view('technician.inventory-serials.index', compact('myStockCount', 'myAvailableCount'));
    }

    /**
     * DataTable AJAX endpoint.
     */
    public function datatable(Request $request)
    {
        $this->authorize('view_inventory');
        return $this->service->getDataTable($request, 'technician', auth()->id());
    }

    /**
     * Show serial detail (own stock only).
     */
    public function show(InventorySerial $inventorySerial)
    {
        $this->authorize('view_inventory');

        // Technician can only view their own serials
        $user = auth()->user();
        if ($inventorySerial->current_location_type !== InventorySerial::LOCATION_TYPE_TECHNICIAN
            || $inventorySerial->current_location_id !== $user->id) {
            abort(403, 'You can only view your own issued stock.');
        }

        $detail = $this->service->getSerialDetail($inventorySerial->id);

        return view('technician.inventory-serials.show', $detail);
    }
}
