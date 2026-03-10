<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ChargeCatalog;
use App\Models\JobType;
use App\Services\ChargeCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Supervisor Charge Catalog Controller
 *
 * View-only access to charge catalog for reference in quotations and job planning
 *
 * @package App\Http\Controllers\Supervisor
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
            new Middleware('role:supervisor'),
        ];
    }

    public function __construct(ChargeCatalogService $chargeService)
    {
        $this->chargeService = $chargeService;
    }

    /**
     * Display listing of active charge catalog (view-only).
     */
    public function index(): View
    {
        Gate::authorize('view', ChargeCatalog::class);

        $charges = ChargeCatalog::with('jobType')
            ->where('status', ChargeCatalog::STATUS_ACTIVE)
            ->orderBy('job_type_id')
            ->orderBy('charge_name')
            ->get();

        // Calculate stats
        $stats = [
            'total_charges' => ChargeCatalog::where('status', ChargeCatalog::STATUS_ACTIVE)->count(),
            'taxable_charges' => ChargeCatalog::where('status', ChargeCatalog::STATUS_ACTIVE)
                ->where('is_taxable', true)->count(),
            'types' => ChargeCatalog::where('status', ChargeCatalog::STATUS_ACTIVE)
                ->select('job_type_id')
                ->distinct()
                ->count(),
        ];

        // Group charges by job type for quick reference
        $chargesByType = $charges->groupBy(function ($charge) {
            return $charge->jobType?->job_title ?? 'Unknown';
        });

        // Job types for filter
        $jobTypes = JobType::active()->orderBy('job_title')->get();

        return view('supervisor.charge-catalog.index', compact('charges', 'stats', 'chargesByType', 'jobTypes'));
    }

    /**
     * Get DataTable data for charge catalog.
     */
    public function datatable(Request $request): JsonResponse
    {
        Gate::authorize('view', ChargeCatalog::class);

        $query = ChargeCatalog::with('jobType')
            ->where('status', ChargeCatalog::STATUS_ACTIVE)
            ->select('charge_catalog.*');

        // Filter by job type if provided
        if ($request->filled('job_type_id')) {
            $query->where('job_type_id', $request->job_type_id);
        }

        return DataTables::of($query)
            ->addColumn('type_badge', function ($charge) {
                $title = $charge->jobType?->job_title ?? 'Unknown';
                return '<span class="badge bg-primary">' . htmlspecialchars($title) . '</span>';
            })
            ->addColumn('price_display', function ($charge) {
                return '<strong>RM ' . number_format($charge->default_price, 2) . '</strong>' .
                       ($charge->unit ? '<br><small class="text-muted">' . $charge->unit . '</small>' : '');
            })
            ->addColumn('tax_info', function ($charge) {
                if ($charge->is_taxable) {
                    return '<span class="badge bg-success">' . $charge->tax_rate . '%</span>';
                }
                return '<span class="badge bg-secondary">No Tax</span>';
            })
            ->rawColumns(['type_badge', 'price_display', 'tax_info'])
            ->make(true);
    }

    /**
     * Get charges for AJAX (for quotation/invoice line items).
     */
    public function searchCharges(Request $request): JsonResponse
    {
        Gate::authorize('view', ChargeCatalog::class);

        $query = $request->get('q', '');
        $charges = $this->chargeService->searchForLineItems($query);

        return response()->json($charges);
    }
}
