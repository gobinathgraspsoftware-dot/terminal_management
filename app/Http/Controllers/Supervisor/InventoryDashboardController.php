<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryDashboardController extends Controller
{
    protected InventoryDashboardService $dashboardService;

    public function __construct(InventoryDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get current supervisor's team technician IDs
     */
    protected function getTeamTechIds(): array
    {
        return $this->dashboardService->getTeamTechnicianIds(auth()->id());
    }

    /**
     * Display the inventory dashboard (Supervisor - team-scoped)
     */
    public function index()
    {
        Gate::authorize('view_inventory');

        $techIds = $this->getTeamTechIds();

        // Get team stock totals (technician locations only)
        $overallTotals  = $this->dashboardService->getOverallTotals('technician');
        $lowStockCounts = $this->dashboardService->getLowStockCounts('technician');

        return view('supervisor.inventory-dashboard.index', compact(
            'overallTotals',
            'lowStockCounts'
        ));
    }

    // =========================================================================
    // AJAX WIDGET ENDPOINTS (Team-scoped)
    // =========================================================================

    /**
     * Widget: Stock by Category (team technicians)
     */
    public function stockByCategory(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getStockByCategory('technician');

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Stock by Technician
     */
    public function stockByTechnician(Request $request)
    {
        Gate::authorize('view_inventory');

        $techIds = $this->getTeamTechIds();
        $data = $this->dashboardService->getStockByTechnician($techIds);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Low Stock Alerts (team)
     */
    public function lowStockAlerts(Request $request)
    {
        Gate::authorize('view_inventory');

        $techIds = $this->getTeamTechIds();
        $data = $this->dashboardService->getTeamLowStockAlerts($techIds);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Recent Movements (team)
     */
    public function recentMovements(Request $request)
    {
        Gate::authorize('view_inventory');

        $techIds = $this->getTeamTechIds();
        $data = $this->dashboardService->getTeamRecentMovements($techIds);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Stock Aging (team technicians)
     */
    public function stockAging(Request $request)
    {
        Gate::authorize('view_inventory');

        $aging     = $this->dashboardService->getStockAging('technician');
        $threshold = $request->input('days', 90);
        $agedItems = $this->dashboardService->getAgedStockItems($threshold, 'technician');

        return response()->json([
            'success'   => true,
            'aging'     => $aging,
            'agedItems' => $agedItems,
        ]);
    }

    /**
     * Widget: Top Models (team technicians)
     */
    public function topModels(Request $request)
    {
        Gate::authorize('view_inventory');

        $limit = $request->input('limit', 10);
        $data  = $this->dashboardService->getTopModelsByQuantity($limit, 'technician');

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Movement Trend Chart (team)
     */
    public function movementTrend(Request $request)
    {
        Gate::authorize('view_inventory');

        $days = $request->input('days', 7);
        $data = $this->dashboardService->getMovementTrend($days, 'technician');

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Category Distribution (team)
     */
    public function categoryDistribution(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getCategoryDistribution('technician');

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Movement Summary (team)
     */
    public function movementSummary(Request $request)
    {
        Gate::authorize('view_inventory');

        $days = $request->input('days', 7);
        $data = $this->dashboardService->getMovementSummary($days, 'technician');

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
