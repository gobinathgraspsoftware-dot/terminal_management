<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use App\Services\DepotService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DepotController extends Controller
{
    use AuthorizesRequests;
    
    protected $depotService;

    public function __construct(DepotService $depotService)
    {
        $this->depotService = $depotService;
    }

    /**
     * Display a listing of depots (read-only).
     */
    public function index(Request $request)
    {
        $this->authorize('view_depots');

        if ($request->ajax()) {
            return $this->depotService->getDataTable($request, true);
        }

        return view('technician.depots.index');
    }

    /**
     * Display the specified depot with stock levels (read-only).
     */
    public function show(Depot $depot)
    {
        $this->authorize('view_depots');

        // Load relationships
        $depot->load([
            'stockBalances.model.category'
        ]);

        // Get stock summary
        $stockSummary = $this->depotService->getStockSummary($depot->id);

        return view('technician.depots.show', compact('depot', 'stockSummary'));
    }

    /**
     * Get stock summary for a specific depot (AJAX).
     */
    public function stockSummary(Depot $depot)
    {
        $this->authorize('view_depots');

        $summary = $this->depotService->getStockSummary($depot->id);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}
