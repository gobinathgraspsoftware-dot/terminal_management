<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\PartnerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PartnerController extends Controller
{
    protected PartnerService $partnerService;

    public function __construct(PartnerService $partnerService)
    {
        $this->partnerService = $partnerService;

        // Supervisors have view-only access to partners
        // $this->middleware('permission:view_partners');
    }

    /**
     * Display a listing of partners
     */
    public function index(): View
    {
        $statistics = $this->partnerService->getStatistics();

        return view('supervisor.partners.index', compact('statistics'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Partner::active()
            ->withCount('clients')
            ->withCount('jobOrders')
            ->select('partners.*');

        return DataTables::of($query)
            ->addColumn('status_badge', function ($partner) {
                return $partner->status_badge;
            })
            ->addColumn('job_intake_badge', function ($partner) {
                return $partner->job_intake_method_badge;
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
            ->addColumn('actions', function ($partner) {
                return '<a href="' . route('supervisor.partners.show', $partner->id) . '"
                    class="btn btn-sm btn-info" title="View">
                    <i class="bi bi-eye"></i> View
                </a>';
            })
            ->filter(function ($query) use ($request) {
                // Search functionality
                if ($request->has('search') && $request->search['value']) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('partner_code', 'like', "%{$searchValue}%")
                            ->orWhere('partner_name', 'like', "%{$searchValue}%")
                            ->orWhere('pic_name', 'like', "%{$searchValue}%")
                            ->orWhere('city', 'like', "%{$searchValue}%")
                            ->orWhere('state', 'like', "%{$searchValue}%");
                    });
                }

                // State filter
                if ($request->filled('state')) {
                    $query->where('state', $request->state);
                }

                // Job intake method filter
                if ($request->filled('job_intake_method')) {
                    $query->where('job_intake_method', $request->job_intake_method);
                }
            })
            ->rawColumns(['status_badge', 'job_intake_badge', 'pic_info', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified partner
     */
    public function show(Partner $partner): View
    {
        // Load related data for viewing
        $partner->load([
            'clients' => function ($query) {
                $query->limit(10)->orderBy('created_at', 'desc');
            },
            'jobOrders' => function ($query) {
                $query->limit(10)->orderBy('created_at', 'desc');
            },
            'createdBy',
            'updatedBy'
        ]);

        $statistics = $this->partnerService->getPartnerStatistics($partner);

        return view('supervisor.partners.show', compact('partner', 'statistics'));
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
}
