<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    protected VendorService $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
        $this->middleware('permission:view_vendors');
    }

    /**
     * Display a listing of vendors
     */
    public function index(): View
    {
        $statistics = $this->vendorService->getStatistics();

        return view('supervisor.vendors.index', compact('statistics'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Vendor::with(['createdBy'])
            ->withCount('branches')
            ->withCount('purchaseOrders')
            ->select('vendors.*')
            ->where('status', Vendor::STATUS_ACTIVE);

        return DataTables::of($query)
            ->addColumn('status_badge', function ($vendor) {
                $badgeClass = $vendor->status === Vendor::STATUS_ACTIVE ? 'bg-success' : 'bg-secondary';
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($vendor->status) . '</span>';
            })
            ->addColumn('vendor_type_badge', function ($vendor) {
                return match($vendor->vendor_type) {
                    Vendor::TYPE_SUPPLIER => '<span class="badge bg-primary">Supplier</span>',
                    Vendor::TYPE_SUBCON => '<span class="badge bg-info">Sub-contractor</span>',
                    Vendor::TYPE_COURIER => '<span class="badge bg-warning">Courier</span>',
                    Vendor::TYPE_OTHER => '<span class="badge bg-secondary">Other</span>',
                    default => '<span class="badge bg-dark">Unknown</span>',
                };
            })
            ->addColumn('pic_info', function ($vendor) {
                $html = '<strong>' . e($vendor->pic_name ?? 'N/A') . '</strong>';
                if ($vendor->pic_email) {
                    $html .= '<br><small class="text-muted"><i class="bi bi-envelope"></i> ' . e($vendor->pic_email) . '</small>';
                }
                if ($vendor->pic_phone) {
                    $html .= '<br><small class="text-muted"><i class="bi bi-telephone"></i> ' . e($vendor->pic_phone) . '</small>';
                }
                return $html;
            })
            ->addColumn('branches_count_display', function ($vendor) {
                $count = $vendor->branches_count ?? 0;
                return '<span class="badge bg-light text-dark">' . $count . ' branch' . ($count !== 1 ? 'es' : '') . '</span>';
            })
            ->addColumn('actions', function ($vendor) {
                return '<a href="' . route('supervisor.vendors.show', $vendor->id) . '"
                    class="btn btn-sm btn-info" title="View">
                    <i class="bi bi-eye"></i>
                </a>';
            })
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('vendor_code', 'like', "%{$searchValue}%")
                            ->orWhere('vendor_name', 'like', "%{$searchValue}%")
                            ->orWhere('company_name', 'like', "%{$searchValue}%")
                            ->orWhere('pic_name', 'like', "%{$searchValue}%");
                    });
                }

                if ($request->filled('vendor_type')) {
                    $query->where('vendor_type', $request->vendor_type);
                }
            })
            ->rawColumns(['status_badge', 'vendor_type_badge', 'pic_info', 'branches_count_display', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified vendor
     */
    public function show(Vendor $vendor): View
    {
        $vendor->load([
            'branches.state',
            'branches.city',
            'purchaseOrders' => function ($query) {
                $query->latest()->limit(10);
            },
            'grns' => function ($query) {
                $query->latest()->limit(10);
            },
            'createdBy',
            'updatedBy'
        ]);

        $statistics = $this->vendorService->getVendorStatistics($vendor);

        return view('supervisor.vendors.show', compact('vendor', 'statistics'));
    }
}
