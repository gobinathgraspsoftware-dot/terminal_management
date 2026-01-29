<?php

namespace App\Http\Controllers\Technician;

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

        // Technicians have limited view access to partners
        // $this->middleware('permission:view_partners');
    }

    /**
     * Display a listing of active partners
     * Technicians only see active partners with limited information
     */
    public function index(): View
    {
        $statistics = [
            'total' => Partner::active()->count(),
        ];

        return view('technician.partners.index', compact('statistics'));
    }

    /**
     * DataTables server-side processing
     * Limited columns for technicians
     */
    public function datatable(Request $request): JsonResponse
    {
        // Technicians only see active partners
        $query = Partner::active()
            ->select('id', 'partner_code', 'partner_name', 'pic_name', 'pic_phone', 'city', 'state');

        return DataTables::of($query)
            ->addColumn('pic_info', function ($partner) {
                $html = '<strong>' . e($partner->pic_name ?? 'N/A') . '</strong>';
                if ($partner->pic_phone) {
                    $html .= '<br><small class="text-muted"><i class="bi bi-telephone"></i> ' . e($partner->pic_phone) . '</small>';
                }
                return $html;
            })
            ->addColumn('location', function ($partner) {
                $parts = array_filter([$partner->city, $partner->state]);
                return implode(', ', $parts) ?: '-';
            })
            ->addColumn('actions', function ($partner) {
                return '<a href="' . route('technician.partners.show', $partner->id) . '"
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
            })
            ->rawColumns(['pic_info', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified partner
     * Limited information for technicians
     */
    public function show(Partner $partner): View
    {
        // Ensure partner is active for technicians
        if ($partner->status !== Partner::STATUS_ACTIVE) {
            abort(404, 'Partner not found');
        }

        // Load only necessary relationships for technician view
        $partner->load(['clients' => function ($query) {
            $query->active()->select('id', 'partner_id', 'client_code', 'client_name', 'city', 'state')
                ->limit(10);
        }]);

        return view('technician.partners.show', compact('partner'));
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
