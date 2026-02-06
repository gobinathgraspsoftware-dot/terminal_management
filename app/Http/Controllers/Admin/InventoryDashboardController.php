<?php

namespace App\Http\Controllers\Admin;

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
     * Display the inventory dashboard (Admin - full system view)
     */
    public function index()
    {
        Gate::authorize('view_inventory');

        $overallTotals   = $this->dashboardService->getOverallTotals();
        $lowStockCounts  = $this->dashboardService->getLowStockCounts();

        return view('admin.inventory-dashboard.index', compact(
            'overallTotals',
            'lowStockCounts'
        ));
    }

    // =========================================================================
    // AJAX WIDGET ENDPOINTS
    // =========================================================================

    /**
     * Widget: Stock by Category
     */
    public function stockByCategory(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getStockByCategory();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Stock by Status
     */
    public function stockByStatus(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getStockByStatus();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Stock by Depot
     */
    public function stockByDepot(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getStockByDepot();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Low Stock Alerts
     */
    public function lowStockAlerts(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getLowStockAlerts();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Recent Movements
     */
    public function recentMovements(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getRecentMovements();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Stock Aging
     */
    public function stockAging(Request $request)
    {
        Gate::authorize('view_inventory');

        $aging       = $this->dashboardService->getStockAging();
        $threshold   = $request->input('days', 90);
        $agedItems   = $this->dashboardService->getAgedStockItems($threshold);

        return response()->json([
            'success'    => true,
            'aging'      => $aging,
            'agedItems'  => $agedItems,
        ]);
    }

    /**
     * Widget: Top Models
     */
    public function topModels(Request $request)
    {
        Gate::authorize('view_inventory');

        $limit = $request->input('limit', 10);
        $data  = $this->dashboardService->getTopModelsByQuantity($limit);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Movement Trend Chart
     */
    public function movementTrend(Request $request)
    {
        Gate::authorize('view_inventory');

        $days = $request->input('days', 7);
        $data = $this->dashboardService->getMovementTrend($days);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Category Distribution Chart
     */
    public function categoryDistribution(Request $request)
    {
        Gate::authorize('view_inventory');

        $data = $this->dashboardService->getCategoryDistribution();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Widget: Movement Summary
     */
    public function movementSummary(Request $request)
    {
        Gate::authorize('view_inventory');

        $days = $request->input('days', 7);
        $data = $this->dashboardService->getMovementSummary($days);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
