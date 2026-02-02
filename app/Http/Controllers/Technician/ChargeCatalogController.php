<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\ChargeCatalog;
use App\Services\ChargeCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Technician Charge Catalog Controller
 *
 * Mobile-friendly view-only access to charge catalog for field reference
 *
 * @package App\Http\Controllers\Technician
 */
class ChargeCatalogController extends Controller implements HasMiddleware
{
    protected ChargeCatalogService $chargeService;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:technician'),
        ];
    }

    public function __construct(ChargeCatalogService $chargeService)
    {
        $this->chargeService = $chargeService;
    }

    /**
     * Display listing of active charge catalog (mobile-friendly).
     */
    public function index(): View
    {
        Gate::authorize('view', ChargeCatalog::class);

        // Get active charges grouped by type for easy reference
        $chargesByType = $this->chargeService->getGroupedByType();

        $stats = [
            'total_charges' => ChargeCatalog::where('status', ChargeCatalog::STATUS_ACTIVE)->count(),
            'types' => $chargesByType->keys()->count(),
        ];

        return view('technician.charge-catalog.index', compact('chargesByType', 'stats'));
    }

    /**
     * Get charges by type (AJAX).
     */
    public function getByType(Request $request): JsonResponse
    {
        Gate::authorize('view', ChargeCatalog::class);

        $type = $request->get('type');
        $charges = $this->chargeService->getByType($type);

        return response()->json([
            'success' => true,
            'charges' => $charges,
        ]);
    }

    /**
     * Search charges (AJAX).
     */
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('view', ChargeCatalog::class);

        $query = $request->get('q', '');
        $charges = $this->chargeService->searchForLineItems($query);

        return response()->json([
            'success' => true,
            'charges' => $charges,
        ]);
    }
}
