<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\VendorService;
use App\Exports\VendorsExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

/**
 * Supervisor Vendor Controller
 *
 * View-only access to vendors for reference.
 * Supervisor can: view list, view detail, export.
 * Supervisor cannot: create, edit, delete, restore, toggle status.
 */
class VendorController extends Controller implements HasMiddleware
{
    protected VendorService $vendorService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:supervisor'),
        ];
    }

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Display a listing of vendors (view-only)
     */
    public function index(): View
    {
        $statistics = $this->vendorService->getStatistics();
        $vendorTypes = VendorType::where('is_active', true)->orderBy('title')->get();

        return view('supervisor.vendors.index', compact('statistics', 'vendorTypes'));
    }

    /**
     * DataTables server-side processing (view-only, no action buttons)
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Vendor::select('vendors.*')
            ->with(['createdBy', 'vendorType'])
            ->withCount('branches')
            ->withCount('purchaseOrders')
            ->withCount('grns');

        return DataTables::of($query)
            ->addColumn('status_badge', function ($vendor) {
                $badgeClass = $vendor->status === Vendor::STATUS_ACTIVE ? 'bg-success' : 'bg-secondary';
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($vendor->status) . '</span>';
            })
            ->addColumn('vendor_type_badge', function ($vendor) {
                $type = $vendor->vendor_type ?? '';
                return match($type) {
                    Vendor::TYPE_SUPPLIER => '<span class="badge bg-primary">Supplier</span>',
                    Vendor::TYPE_SUBCON => '<span class="badge bg-info">Sub-contractor</span>',
                    Vendor::TYPE_COURIER => '<span class="badge bg-warning">Courier</span>',
                    Vendor::TYPE_OTHER => '<span class="badge bg-secondary">Other</span>',
                    default => '<span class="badge bg-dark">' . ucfirst($type ?: 'Unknown') . '</span>',
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
                            ->orWhere('pic_name', 'like', "%{$searchValue}%")
                            ->orWhere('pic_email', 'like', "%{$searchValue}%")
                            ->orWhereHas('branches', function ($bq) use ($searchValue) {
                                $bq->where('branch_name', 'like', "%{$searchValue}%")
                                   ->orWhereHas('city', function ($cq) use ($searchValue) {
                                       $cq->where('name', 'like', "%{$searchValue}%");
                                   })
                                   ->orWhereHas('state', function ($sq) use ($searchValue) {
                                       $sq->where('name', 'like', "%{$searchValue}%");
                                   });
                            });
                    });
                }

                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }

                if ($request->filled('vendor_type')) {
                    $typeFilter = $request->vendor_type;
                    if (is_numeric($typeFilter)) {
                        $query->where('vendor_type_id', $typeFilter);
                    } else {
                        $query->where('vendor_type', $typeFilter);
                    }
                }

                if ($request->filled('state')) {
                    $query->whereHas('branches', function ($bq) use ($request) {
                        $bq->where('state_id', $request->state);
                    });
                }
            })
            ->rawColumns(['status_badge', 'vendor_type_badge', 'pic_info', 'branches_count_display', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified vendor (view-only)
     */
    public function show(Vendor $vendor): View
    {
        $vendor->load([
            'branches.state',
            'branches.city',
            'vendorType',
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

    /**
     * Export vendors to Excel
     */
    public function export(Request $request)
    {
        $filename = 'vendors_' . date('Y-m-d_His') . '.xlsx';

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['filename' => $filename, 'filters' => $request->all()])
            ->log('Vendors exported (supervisor)');

        return Excel::download(new VendorsExport($request->all()), $filename);
    }

    /**
     * Get vendors list for dropdowns (AJAX) - used by tickets, quotations etc.
     */
    public function getList(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $type = $request->get('type');

        $query = Vendor::active()
            ->select('id', 'vendor_code', 'vendor_name', 'vendor_type', 'vendor_type_id');

        if ($type) {
            if (is_numeric($type)) {
                $query->where('vendor_type_id', (int) $type);
            } else {
                $query->where('vendor_type', $type);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('vendor_code', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%");
            });
        }

        $vendors = $query->orderBy('vendor_name')->limit(50)->get();

        return response()->json([
            'success' => true,
            'vendors' => $vendors->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'text' => "[{$vendor->vendor_code}] {$vendor->vendor_name}",
                    'vendor_type' => $vendor->vendor_type
                ];
            })
        ]);
    }
}
