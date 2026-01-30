<?php

namespace App\Http\Controllers\Technician;

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
 * Technician SiteController
 *
 * Handles site viewing for Technician users:
 * - View sites where they have assignments
 * - View site details
 * - View installed equipment
 * - Mobile-friendly interface
 * - Read-only access
 *
 * @package App\Http\Controllers\Technician
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
            new Middleware('role:technician'),
        ];
    }

    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    /**
     * Display a listing of sites where technician has assignments.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Get sites where technician has job assignments
        $sites = Site::whereHas('jobOrders.jobAssignments', function ($query) use ($user) {
            $query->where('technician_id', $user->id);
        })
        ->with(['client:id,client_name'])
        ->withCount(['siteAssets' => function ($query) {
            $query->where('status', 'active');
        }])
        ->orderBy('site_name')
        ->paginate(15);

        $stats = [
            'total_sites' => $sites->total(),
            'active_jobs' => $user->jobAssignments()
                ->whereHas('jobOrder', function ($query) {
                    $query->whereIn('status', ['assigned', 'in_progress']);
                })
                ->count(),
        ];

        return view('technician.sites.index', compact('sites', 'stats'));
    }

    /**
     * Display the specified site (mobile-friendly).
     */
    public function show(Site $site): View
    {
        $this->authorize('view', $site);

        $user = auth()->user();

        // Get site with relationships
        $site->load([
            'client',
            'contacts',
            'siteAssets' => function ($query) {
                $query->where('status', 'active')
                      ->with('model');
            }
        ]);

        // Get technician's jobs at this site
        $myJobs = $site->jobOrders()
            ->whereHas('jobAssignments', function ($query) use ($user) {
                $query->where('technician_id', $user->id);
            })
            ->with(['jobAssignments' => function ($query) use ($user) {
                $query->where('technician_id', $user->id);
            }])
            ->latest()
            ->limit(10)
            ->get();

        return view('technician.sites.show', compact('site', 'myJobs'));
    }
}
