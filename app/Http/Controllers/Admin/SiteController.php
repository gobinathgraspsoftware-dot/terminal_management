<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Site\StoreSiteRequest;
use App\Http\Requests\Admin\Site\UpdateSiteRequest;
use App\Models\Site;
use App\Services\SiteService;
use App\Exports\SitesExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin SiteController
 *
 * Handles all site/branch management for Admin users:
 * - Full CRUD operations
 * - GPS coordinates management
 * - Contact management
 * - Asset overview
 * - Export functionality
 *
 * @package App\Http\Controllers\Admin
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
            new Middleware('role:admin'),
        ];
    }

    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    /**
     * Display a listing of sites.
     */
    public function index(Request $request): View
    {
        $stats = $this->siteService->getStatistics();
        $clients = $this->siteService->getClientsForDropdown();
        $states = $this->siteService->getStatesForFilter();

        return view('admin.sites.index', compact('stats', 'clients', 'states'));
    }

    /**
     * Get DataTable data for sites (AJAX endpoint).
     */
    public function datatable(Request $request): JsonResponse
    {
        return $this->siteService->getDatatableData($request);
    }

    /**
     * Show the form for creating a new site.
     */
    public function create(): View
    {
        $clients = $this->siteService->getClientsForDropdown();
        $states = $this->siteService->getMalaysianStates();

        return view('admin.sites.create', compact('clients', 'states'));
    }

    /**
     * Store a newly created site.
     */
    public function store(StoreSiteRequest $request)
    {
        try {
            $site = $this->siteService->createSite($request->validated());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Site created successfully.',
                    'site' => $site
                ]);
            }

            return redirect()
                ->route('admin.sites.index')
                ->with('success', 'Site created successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create site: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to create site: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified site.
     */
    public function show(Site $site): View
    {
        $this->authorize('view', $site);

        $site = $this->siteService->getSiteWithRelations($site->id);

        return view('admin.sites.show', compact('site'));
    }

    /**
     * Show the form for editing the specified site.
     */
    public function edit(Site $site): View
    {
        $this->authorize('update', $site);

        $clients = $this->siteService->getClientsForDropdown();
        $states = $this->siteService->getMalaysianStates();

        return view('admin.sites.edit', compact('site', 'clients', 'states'));
    }

    /**
     * Update the specified site.
     */
    public function update(UpdateSiteRequest $request, Site $site)
    {
        $this->authorize('update', $site);

        try {
            $site = $this->siteService->updateSite($site, $request->validated());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Site updated successfully.',
                    'site' => $site
                ]);
            }

            return redirect()
                ->route('admin.sites.index')
                ->with('success', 'Site updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update site: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to update site: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified site (soft delete).
     */
    public function destroy(Request $request, Site $site): JsonResponse
    {
        $this->authorize('delete', $site);

        try {
            $this->siteService->deleteSite($site);

            return response()->json([
                'success' => true,
                'message' => 'Site deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete site: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted site.
     */
    public function restore(Request $request, $siteId): JsonResponse
    {
        try {
            $this->siteService->restoreSite($siteId);

            return response()->json([
                'success' => true,
                'message' => 'Site restored successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore site: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export sites to Excel.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['client_id', 'state', 'status']);

        return Excel::download(
            new SitesExport($filters),
            'sites_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Get current GPS location (for mobile devices).
     */
    public function captureGps(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        return response()->json([
            'success' => true,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
    }
}
