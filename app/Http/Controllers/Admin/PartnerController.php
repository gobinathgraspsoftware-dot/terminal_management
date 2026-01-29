<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Models\Partner;
use App\Services\PartnerService;
use App\Exports\PartnersExport;
use App\Imports\PartnersImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class PartnerController extends Controller
{
    protected PartnerService $partnerService;

    public function __construct(PartnerService $partnerService)
    {
        $this->partnerService = $partnerService;

        // Apply permission middleware
        // $this->middleware('permission:view_partners')->only(['index', 'show', 'datatable']);
        // $this->middleware('permission:create_partners')->only(['create', 'store']);
        // $this->middleware('permission:edit_partners')->only(['edit', 'update', 'toggleStatus']);
        // $this->middleware('permission:delete_partners')->only(['destroy', 'restore']);
        // $this->middleware('permission:export_partners')->only(['export']);
        // $this->middleware('permission:import_partners')->only(['import', 'importTemplate']);
    }

    /**
     * Display a listing of partners
     */
    public function index(): View
    {
        $statistics = $this->partnerService->getStatistics();

        return view('admin.partners.index', compact('statistics'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Partner::with(['createdBy', 'updatedBy'])
            ->withCount('clients')
            ->withCount('jobOrders')
            ->select('partners.*');

        // Include trashed if requested
        if ($request->get('show_trashed') === 'true') {
            $query->withTrashed();
        }

        return DataTables::of($query)
            ->addColumn('status_badge', function ($partner) {
                if ($partner->trashed()) {
                    return '<span class="badge bg-danger">Deleted</span>';
                }
                return $partner->status_badge;
            })
            ->addColumn('job_intake_badge', function ($partner) {
                return $partner->job_intake_method_badge;
            })
            ->addColumn('sla_count', function ($partner) {
                $slaRules = $partner->sla_rules ?? [];
                return count($slaRules);
            })
            ->addColumn('pic_info', function ($partner) {
                $html = '<strong>' . e($partner->pic_name ?? 'N/A') . '</strong>';
                if ($partner->pic_email) {
                    $html .= '<br><small class="text-muted"><i class="bi bi-envelope"></i> ' . e($partner->pic_email) . '</small>';
                }
                if ($partner->pic_phone) {
                    $html .= '<br><small class="text-muted"><i class="bi bi-telephone"></i> ' . e($partner->pic_phone) . '</small>';
                }
                return $html;
            })
            ->addColumn('created_info', function ($partner) {
                $html = $partner->created_at ? $partner->created_at->format('Y-m-d H:i') : '-';
                if ($partner->createdBy) {
                    $html .= '<br><small class="text-muted">by ' . e($partner->createdBy->name) . '</small>';
                }
                return $html;
            })
            ->addColumn('actions', function ($partner) {
                return $this->getActionButtons($partner);
            })
            ->filter(function ($query) use ($request) {
                // Search functionality
                if ($request->has('search') && $request->search['value']) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('partner_code', 'like', "%{$searchValue}%")
                            ->orWhere('partner_name', 'like', "%{$searchValue}%")
                            ->orWhere('pic_name', 'like', "%{$searchValue}%")
                            ->orWhere('pic_email', 'like', "%{$searchValue}%")
                            ->orWhere('city', 'like', "%{$searchValue}%")
                            ->orWhere('state', 'like', "%{$searchValue}%");
                    });
                }

                // Status filter
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }

                // Job intake method filter
                if ($request->filled('job_intake_method')) {
                    $query->where('job_intake_method', $request->job_intake_method);
                }

                // State filter
                if ($request->filled('state')) {
                    $query->where('state', $request->state);
                }
            })
            ->rawColumns(['status_badge', 'job_intake_badge', 'pic_info', 'created_info', 'actions'])
            ->make(true);
    }

    /**
     * Generate action buttons for DataTable
     */
    private function getActionButtons(Partner $partner): string
    {
        $actions = '<div class="btn-group btn-group-sm" role="group">';

        // View button
        if (Auth::user()->can('view_partners')) {
            $actions .= '<a href="' . route('admin.partners.show', $partner->id) . '"
                class="btn btn-info" title="View">
                <i class="bi bi-eye"></i>
            </a>';
        }

        if ($partner->trashed()) {
            // Restore button for deleted partners
            if (Auth::user()->can('delete_partners')) {
                $actions .= '<button type="button" class="btn btn-success restore-partner"
                    data-id="' . $partner->id . '" title="Restore">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>';
            }
        } else {
            // Edit button
            if (Auth::user()->can('edit_partners')) {
                $actions .= '<a href="' . route('admin.edit_partners', $partner->id) . '"
                    class="btn btn-primary" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>';
            }

            // Toggle status button
            if (Auth::user()->can('edit_partners')) {
                $statusIcon = $partner->status === 'active' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
                $statusTitle = $partner->status === 'active' ? 'Deactivate' : 'Activate';
                $actions .= '<button type="button" class="btn btn-outline-secondary toggle-status"
                    data-id="' . $partner->id . '" title="' . $statusTitle . '">
                    <i class="bi ' . $statusIcon . '"></i>
                </button>';
            }

            // Delete button
            if (Auth::user()->can('delete_partners')) {
                $actions .= '<button type="button" class="btn btn-danger delete-partner"
                    data-id="' . $partner->id . '" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>';
            }
        }

        $actions .= '</div>';
        return $actions;
    }

    /**
     * Show the form for creating a new partner
     */
    public function create(): View
    {
        $nextCode = Partner::generatePartnerCode();
        $states = $this->getMalaysianStates();
        $jobIntakeMethods = $this->getJobIntakeMethods();

        return view('admin.create_partners', compact('nextCode', 'states', 'jobIntakeMethods'));
    }

    /**
     * Store a newly created partner
     */
    public function store(StorePartnerRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $partner = $this->partnerService->createPartner($request->validated());

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($partner)
                ->withProperties($request->except(['api_key']))
                ->log('Partner created');

            return response()->json([
                'success' => true,
                'message' => 'Partner created successfully',
                'partner' => $partner,
                'redirect' => route('admin.partners.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified partner
     */
    public function show(Partner $partner): View
    {
        $partner->load([
            'clients' => function ($query) {
                $query->limit(10)->orderBy('created_at', 'desc');
            },
            'jobOrders' => function ($query) {
                $query->limit(10)->orderBy('created_at', 'desc');
            },
            'invoices' => function ($query) {
                $query->limit(10)->orderBy('created_at', 'desc');
            },
            'createdBy',
            'updatedBy'
        ]);

        $statistics = $this->partnerService->getPartnerStatistics($partner);

        return view('admin.partners.show', compact('partner', 'statistics'));
    }

    /**
     * Show the form for editing the specified partner
     */
    public function edit(Partner $partner): View
    {
        $states = $this->getMalaysianStates();
        $jobIntakeMethods = $this->getJobIntakeMethods();

        return view('admin.edit_partners', compact('partner', 'states', 'jobIntakeMethods'));
    }

    /**
     * Update the specified partner
     */
    public function update(UpdatePartnerRequest $request, Partner $partner): JsonResponse
    {
        try {
            DB::beginTransaction();

            $partner = $this->partnerService->updatePartner($partner, $request->validated());

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($partner)
                ->withProperties($request->except(['api_key']))
                ->log('Partner updated');

            return response()->json([
                'success' => true,
                'message' => 'Partner updated successfully',
                'partner' => $partner,
                'redirect' => route('admin.partners.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified partner (soft delete)
     */
    public function destroy(Partner $partner): JsonResponse
    {
        try {
            // Check if partner has active clients or jobs
            if ($partner->clients()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete partner with existing clients. Please reassign or delete clients first.'
                ], 422);
            }

            if ($partner->jobOrders()->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete partner with active job orders.'
                ], 422);
            }

            DB::beginTransaction();

            $partner->update(['updated_by' => Auth::id()]);
            $partner->delete();

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($partner)
                ->log('Partner deleted');

            return response()->json([
                'success' => true,
                'message' => 'Partner deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted partner
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $partner = Partner::withTrashed()->findOrFail($id);

            if (!$partner->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Partner is not deleted'
                ], 422);
            }

            DB::beginTransaction();

            $partner->restore();
            $partner->update(['updated_by' => Auth::id()]);

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($partner)
                ->log('Partner restored');

            return response()->json([
                'success' => true,
                'message' => 'Partner restored successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle partner status (active/inactive)
     */
    public function toggleStatus(Partner $partner): JsonResponse
    {
        try {
            DB::beginTransaction();

            $newStatus = $partner->status === Partner::STATUS_ACTIVE
                ? Partner::STATUS_INACTIVE
                : Partner::STATUS_ACTIVE;

            $partner->update([
                'status' => $newStatus,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($partner)
                ->withProperties(['status' => $newStatus])
                ->log('Partner status toggled');

            return response()->json([
                'success' => true,
                'message' => 'Partner status updated successfully',
                'status' => $newStatus
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export partners to Excel
     */
    public function export(Request $request)
    {
        $filename = 'partners_' . date('Y-m-d_His') . '.xlsx';

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->withProperties(['filename' => $filename, 'filters' => $request->all()])
            ->log('Partners exported');

        return Excel::download(new PartnersExport($request->all()), $filename);
    }

    /**
     * Import partners from Excel
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // Max 10MB
        ]);

        try {
            $import = new PartnersImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->withProperties($results)
                ->log('Partners imported');

            return response()->json([
                'success' => true,
                'message' => "Import completed. {$results['success']} partners imported, {$results['failed']} failed.",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download import template
     */
    public function importTemplate()
    {
        $filename = 'partner_import_template.xlsx';

        return Excel::download(new PartnersExport([], true), $filename);
    }

    /**
     * Get partners list for dropdowns (AJAX)
     */
    public function getList(Request $request): JsonResponse
    {
        $search = $request->get('search');

        $query = Partner::active()
            ->select('id', 'partner_code', 'partner_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('partner_code', 'like', "%{$search}%")
                    ->orWhere('partner_name', 'like', "%{$search}%");
            });
        }

        $partners = $query->orderBy('partner_name')->limit(50)->get();

        return response()->json([
            'success' => true,
            'partners' => $partners->map(function ($partner) {
                return [
                    'id' => $partner->id,
                    'text' => "[{$partner->partner_code}] {$partner->partner_name}"
                ];
            })
        ]);
    }

    /**
     * Get Malaysian states for dropdown
     */
    private function getMalaysianStates(): array
    {
        return [
            'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan',
            'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak',
            'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'
        ];
    }

    /**
     * Get job intake methods for dropdown
     */
    private function getJobIntakeMethods(): array
    {
        return [
            Partner::JOB_INTAKE_MANUAL => 'Manual Entry',
            Partner::JOB_INTAKE_IMPORT => 'File Import',
            Partner::JOB_INTAKE_API => 'API Integration'
        ];
    }
}
