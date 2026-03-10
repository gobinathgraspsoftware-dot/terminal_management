<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChargeCatalog\StoreChargeCatalogRequest;
use App\Http\Requests\Admin\ChargeCatalog\UpdateChargeCatalogRequest;
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
 * Admin Charge Catalog Controller
 *
 * Handles charge catalog management for Admin:
 * - Full CRUD operations
 * - Price management
 * - Tax configuration
 * - Status management
 *
 * @package App\Http\Controllers\Admin
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
            new Middleware('role:admin'),
        ];
    }

    public function __construct(ChargeCatalogService $chargeService)
    {
        $this->chargeService = $chargeService;
    }

    /**
     * Display listing of charge catalog.
     */
    public function index(): View
    {
        Gate::authorize('view', ChargeCatalog::class);

        $charges = ChargeCatalog::with('jobType')
            ->orderBy('job_type_id')
            ->orderBy('charge_name')
            ->get();

        // Calculate stats
        $stats = [
            'total_charges' => ChargeCatalog::count(),
            'active_charges' => ChargeCatalog::where('status', ChargeCatalog::STATUS_ACTIVE)->count(),
            'taxable_charges' => ChargeCatalog::where('is_taxable', true)->count(),
            'types' => ChargeCatalog::select('job_type_id')
                ->distinct()
                ->count(),
        ];

        // Group charges by job type for quick reference
        $chargesByType = $charges->groupBy(function ($charge) {
            return $charge->jobType?->job_title ?? 'Unknown';
        });

        // Job types for filter dropdown
        $jobTypes = JobType::active()->orderBy('job_title')->get();

        return view('admin.charge-catalog.index', compact('charges', 'stats', 'chargesByType', 'jobTypes'));
    }

    /**
     * Get DataTable data for charge catalog.
     */
    public function datatable(Request $request): JsonResponse
    {
        Gate::authorize('view', ChargeCatalog::class);

        $query = ChargeCatalog::with('jobType')
            ->select('charge_catalog.*');

        // Filter by job type if provided
        if ($request->filled('job_type_id')) {
            $query->where('job_type_id', $request->job_type_id);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
            ->addColumn('status_badge', function ($charge) {
                $status = $charge->status;
                $color = $status === ChargeCatalog::STATUS_ACTIVE ? 'success' : 'danger';
                return '<span class="badge bg-' . $color . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('action', function ($charge) {
                $actions = '';

                // Edit button
                if (auth()->user()->can('update', $charge)) {
                    $actions .= '<a href="' . route('admin.charge-catalog.edit', $charge->id) . '"
                                   class="btn btn-sm btn-primary me-1"
                                   title="Edit">
                                   <i class="bi bi-pencil"></i>
                               </a>';
                }

                // Status toggle button
                if (auth()->user()->can('update', $charge)) {
                    $statusIcon = $charge->status === ChargeCatalog::STATUS_ACTIVE
                        ? 'bi-toggle-on text-success'
                        : 'bi-toggle-off text-secondary';
                    $actions .= '<button type="button"
                                   class="btn btn-sm btn-outline-secondary me-1 btn-toggle-status"
                                   data-id="' . $charge->id . '"
                                   data-status="' . $charge->status . '"
                                   title="Toggle Status">
                                   <i class="bi ' . $statusIcon . '"></i>
                               </button>';
                }

                // Delete button
                if (auth()->user()->can('delete', $charge)) {
                    $actions .= '<button type="button"
                                   class="btn btn-sm btn-danger btn-delete"
                                   data-id="' . $charge->id . '"
                                   data-name="' . htmlspecialchars($charge->charge_name) . '"
                                   title="Delete">
                                   <i class="bi bi-trash"></i>
                               </button>';
                }

                return $actions ?: '<span class="text-muted">No actions</span>';
            })
            ->rawColumns(['type_badge', 'price_display', 'tax_info', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new charge.
     */
    public function create(): View
    {
        Gate::authorize('create', ChargeCatalog::class);

        $nextCode = $this->chargeService->generateChargeCode();

        return view('admin.charge-catalog.create', compact('nextCode'));
    }

    /**
     * Store a newly created charge.
     */
    public function store(StoreChargeCatalogRequest $request): JsonResponse
    {
        try {
            $charge = $this->chargeService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Charge catalog created successfully',
                'charge' => $charge,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create charge catalog: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the charge.
     */
    public function edit(ChargeCatalog $chargeCatalog): View
    {
        Gate::authorize('update', $chargeCatalog);

        $charge = $chargeCatalog->load('jobType');

        return view('admin.charge-catalog.edit', compact('charge'));
    }

    /**
     * Update the specified charge.
     */
    public function update(UpdateChargeCatalogRequest $request, ChargeCatalog $chargeCatalog): JsonResponse
    {
        try {
            $charge = $this->chargeService->update($chargeCatalog, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Charge catalog updated successfully',
                'charge' => $charge,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update charge catalog: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified charge.
     */
    public function destroy(ChargeCatalog $chargeCatalog): JsonResponse
    {
        Gate::authorize('delete', $chargeCatalog);

        try {
            $this->chargeService->delete($chargeCatalog);

            return response()->json([
                'success' => true,
                'message' => 'Charge catalog deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete charge catalog: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle charge status.
     */
    public function toggleStatus(ChargeCatalog $chargeCatalog): JsonResponse
    {
        Gate::authorize('update', $chargeCatalog);

        try {
            $charge = $this->chargeService->toggleStatus($chargeCatalog);

            return response()->json([
                'success' => true,
                'message' => 'Charge status updated successfully',
                'status' => $charge->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
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

    /**
     * AJAX: Search job types for Select2 dropdown.
     */
    public function ajaxJobTypes(Request $request): JsonResponse
    {
        $search = $request->get('q', '');

        $jobTypes = JobType::active()
            ->when($search, function ($query) use ($search) {
                $query->where('job_title', 'like', "%{$search}%");
            })
            ->orderBy('job_title')
            ->limit(50)
            ->get(['id', 'job_title']);

        return response()->json([
            'results' => $jobTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'text' => $type->job_title,
                ];
            }),
        ]);
    }
}
