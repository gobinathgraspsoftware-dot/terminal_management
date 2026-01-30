<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\SiteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Supervisor SiteController
 *
 * Handles site viewing for Supervisor users:
 * - View sites in coverage states
 * - View site details
 * - View installed assets
 * - View team job history
 * - Read-only access
 *
 * @package App\Http\Controllers\Supervisor
 */
class SiteController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    protected SiteService $siteService;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:supervisor'),
        ];
    }

    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    /**
     * Display a listing of sites (filtered by coverage states).
     */
    public function index(Request $request): View
    {
        $stats = $this->siteService->getStatistics();
        $clients = $this->siteService->getClientsForDropdown();
        $states = $this->siteService->getStatesForFilter();

        return view('supervisor.sites.index', compact('stats', 'clients', 'states'));
    }

    /**
     * Get DataTable data for sites (AJAX endpoint).
     */
    public function datatable(Request $request): JsonResponse
    {
        return $this->siteService->getDatatableData($request);
    }

    /**
     * Display the specified site.
     */
    public function show(Site $site): View
    {
        $this->authorize('view', $site);

        $site = $this->siteService->getSiteWithRelations($site->id);

        // Get team jobs for this site
        $user = auth()->user();
        $teamTechnicianIds = $user->technicians()->pluck('id');

        $teamJobs = $site->jobOrders()
            ->whereHas('jobAssignments', function ($query) use ($teamTechnicianIds) {
                $query->whereIn('technician_id', $teamTechnicianIds);
            })
            ->with(['jobAssignments.technician'])
            ->latest()
            ->limit(10)
            ->get();

        return view('supervisor.sites.show', compact('site', 'teamJobs'));
    }
}
