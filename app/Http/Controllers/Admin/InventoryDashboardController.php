<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
        try {
            Gate::authorize('view_inventory');

            $data = $this->dashboardService->getStockByCategory();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock by Category Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Widget: Stock by Status
     */
    public function stockByStatus(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            $data = $this->dashboardService->getStockByStatus();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock by Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Widget: Stock by Depot
     */
    public function stockByDepot(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            $data = $this->dashboardService->getStockByDepot();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock by Depot Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Widget: Low Stock Alerts
     */
    public function lowStockAlerts(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            $data = $this->dashboardService->getLowStockAlerts();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Low Stock Alerts Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Widget: Recent Movements
     */
    public function recentMovements(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            $data = $this->dashboardService->getRecentMovements();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Recent Movements Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Widget: Stock Aging
     */
    public function stockAging(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            Log::info('Stock Aging called');

            $aging       = $this->dashboardService->getStockAging();
            $threshold   = $request->input('days', 90);
            $agedItems   = $this->dashboardService->getAgedStockItems($threshold);

            Log::info('Stock Aging data retrieved successfully');

            return response()->json([
                'success'    => true,
                'aging'      => $aging,
                'agedItems'  => $agedItems,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock Aging Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Widget: Top Models
     */
    public function topModels(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            $limit = $request->input('limit', 10);
            $data  = $this->dashboardService->getTopModelsByQuantity($limit);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Top Models Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Widget: Movement Trend Chart
     */
    public function movementTrend(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            $days = $request->input('days', 7);
            $data = $this->dashboardService->getMovementTrend($days);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Movement Trend Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Widget: Category Distribution Chart
     */
    public function categoryDistribution(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            $data = $this->dashboardService->getCategoryDistribution();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Category Distribution Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Widget: Movement Summary
     */
    public function movementSummary(Request $request)
    {
        try {
            Gate::authorize('view_inventory');

            $days = $request->input('days', 7);
            $data = $this->dashboardService->getMovementSummary($days);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Movement Summary Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
