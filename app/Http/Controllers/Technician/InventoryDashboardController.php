<?php

namespace App\Http\Controllers\Technician;

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
     * Display the inventory dashboard (Technician - personal inventory)
     */
    public function index()
    {
        Gate::authorize('view_inventory');

        $userId = auth()->id();

        $overallTotals  = $this->dashboardService->getTechnicianStockSummary($userId);
        $lowStockCounts = $this->dashboardService->getLowStockCounts('technician', $userId);

        return view('technician.inventory-dashboard.index', compact(
            'overallTotals',
            'lowStockCounts'
        ));
    }

    // =========================================================================
    // AJAX WIDGET ENDPOINTS (Personal inventory)
    // =========================================================================

    /**
     * Widget: Stock by Category (personal)
     */
    public function stockByCategory(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getStockByCategory('technician', auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Low Stock Alerts (personal)
     */
    public function lowStockAlerts(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getLowStockAlerts('technician', auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Recent Movements (personal)
     */
    public function recentMovements(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getRecentMovements('technician', auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Stock Aging (personal)
     */
    public function stockAging(Request $request)
    {
        Gate::authorize('view_inventory');

        $userId = auth()->id();

        $aging     = $this->dashboardService->getStockAging('technician', $userId);
        $threshold = $request->input('days', 90);
        $agedItems = $this->dashboardService->getAgedStockItems($threshold, 'technician', $userId);

        return response()->json([
            'success'   => true,
            'aging'     => $aging,
            'agedItems' => $agedItems,
        ]);
    }

    /**
     * Widget: Top Models (personal)
     */
    public function topModels(Request $request)
    {
        Gate::authorize('view_inventory');

        $limit = $request->input('limit', 10);
        $data  = $this->dashboardService->getTopModelsByQuantity($limit, 'technician', auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Movement Trend Chart (personal)
     */
    public function movementTrend(Request $request)
    {
        Gate::authorize('view_inventory');

        $days = $request->input('days', 7);
        $data = $this->dashboardService->getMovementTrend($days, 'technician', auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Category Distribution (personal)
     */
    public function categoryDistribution(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getCategoryDistribution('technician', auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Movement Summary (personal)
     */
    public function movementSummary(Request $request)
    {
        Gate::authorize('view_inventory');

        $days = $request->input('days', 7);
        $data = $this->dashboardService->getMovementSummary($days, 'technician', auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
