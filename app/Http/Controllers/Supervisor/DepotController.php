<?php

namespace App\Http\Controllers\Supervisor;

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

        return view('supervisor.depots.index');
    }

    /**
     * Display the specified depot with stock levels and movements (read-only).
     */
    public function show(Depot $depot)
    {
        $this->authorize('view_depots');

        // Load relationships
        $depot->load([
            'stockBalances.model.category',
            'stockIssues' => function($query) {
                $query->latest()->take(10);
            }
        ]);

        // Get stock summary
        $stockSummary = $this->depotService->getStockSummary($depot->id);
        
        // Get recent movements
        $recentMovements = $this->depotService->getRecentMovements($depot->id, 10);

        // Get assigned technicians if regional depot
        $technicians = [];
        if ($depot->depot_type === Depot::TYPE_REGIONAL) {
            $technicians = $this->depotService->getAssignedTechnicians($depot->id);
        }

        return view('supervisor.depots.show', compact('depot', 'stockSummary', 'recentMovements', 'technicians'));
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

    /**
     * Get recent movements for a specific depot (AJAX).
     */
    public function movements(Depot $depot, Request $request)
    {
        $this->authorize('view_depots');

        $limit = $request->get('limit', 20);
        $movements = $this->depotService->getRecentMovements($depot->id, $limit);

        return response()->json([
            'success' => true,
            'data' => $movements
        ]);
    }
}
