<?php

namespace App\Http\Controllers\Supervisor;

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
     * Display listing - team-scoped serials.
     */
    public function index(Request $request)
    {
        $this->authorize('view_inventory');

        $user = auth()->user();

        if ($request->ajax()) {
            return $this->service->getDataTable($request, 'supervisor', $user->id);
        }

        // Get stats for supervisor's team
        $teamIds = $user->technicians()->pluck('id')->toArray();
        $stats = $this->service->getStatistics();
        $filterOptions = $this->service->getFilterOptions();

        return view('supervisor.inventory-serials.index', compact('stats', 'filterOptions'));
    }

    /**
     * DataTable AJAX endpoint.
     */
    public function datatable(Request $request)
    {
        $this->authorize('view_inventory');
        return $this->service->getDataTable($request, 'supervisor', auth()->id());
    }

    /**
     * Show serial detail.
     */
    public function show(InventorySerial $inventorySerial)
    {
        $this->authorize('view_inventory');

        $detail = $this->service->getSerialDetail($inventorySerial->id);

        return view('supervisor.inventory-serials.show', $detail);
    }
}
