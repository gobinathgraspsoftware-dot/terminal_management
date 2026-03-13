<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Models\PurchaseOrder;
use App\Models\Invoice;
use App\Services\VendorService;
use App\Exports\VendorsExport;
use App\Imports\VendorsImport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    use AuthorizesRequests;

    protected VendorService $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Display a listing of vendors
     */
    public function index(): View
    {
        $statistics = $this->vendorService->getStatistics();
        $vendorTypes = VendorType::active()->orderBy('title')->get();

        return view('admin.vendors.index', compact('statistics', 'vendorTypes'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Vendor::select('vendors.*')
            ->with(['createdBy', 'updatedBy', 'vendorType'])
            ->withCount('purchaseOrders')
            ->withCount('grns')
            ->withCount('branches')
            ->withCount(['invoices as invoices_count' => function ($q) {
                $q->where('invoice_type', 'ap');
            }]);

        if ($request->get('show_trashed') === 'true') {
            $query->withTrashed();
        }

        return DataTables::of($query)
            ->addColumn('status_badge', function ($vendor) {
                if ($vendor->trashed()) {
                    return '<span class="badge bg-danger">Deleted</span>';
                }

                $badgeClass = $vendor->status === Vendor::STATUS_ACTIVE ? 'bg-success' : 'bg-secondary';
                $statusText = ucfirst($vendor->status ?? 'active');

                return '<span class="badge ' . $badgeClass . ' status-toggle-badge"
                              data-vendor-id="' . $vendor->id . '"
                              style="cursor: pointer;"
                              title="Click to toggle status">'
                              . $statusText .
                        '</span>';
            })
            ->addColumn('vendor_type_badge', function ($vendor) {
                return $this->getVendorTypeBadge($vendor);
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
            ->addColumn('bank_info', function ($vendor) {
                if ($vendor->bank_name && $vendor->bank_account_no) {
                    return '<strong>' . e($vendor->bank_name) . '</strong><br>' .
                           '<small class="text-muted">' . e($vendor->bank_account_no) . '</small>';
                }
                return '<span class="text-muted">Not Set</span>';
            })
            ->addColumn('branches_count_display', function ($vendor) {
                $count = $vendor->branches_count ?? 0;
                return '<span class="badge bg-light text-dark">' . $count . ' branch' . ($count !== 1 ? 'es' : '') . '</span>';
            })
            ->addColumn('payment_terms_display', function ($vendor) {
                return ($vendor->payment_terms ?? 30) . ' days';
            })
            ->addColumn('purchase_orders_count', function ($vendor) {
                return $vendor->purchase_orders_count ?? 0;
            })
            ->addColumn('created_info', function ($vendor) {
                $html = $vendor->created_at ? $vendor->created_at->format('Y-m-d H:i') : '-';
                if ($vendor->createdBy) {
                    $html .= '<br><small class="text-muted">by ' . e($vendor->createdBy->name) . '</small>';
                }
                return $html;
            })
            ->addColumn('actions', function ($vendor) {
                return $this->getActionButtons($vendor);
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

                // Filter by vendor_type_id (from VendorType table)
                if ($request->filled('vendor_type')) {
                    $query->where('vendor_type_id', $request->vendor_type);
                }

                if ($request->filled('state')) {
                    $query->whereHas('branches', function ($bq) use ($request) {
                        $bq->where('state_id', $request->state);
                    });
                }
            })
            ->rawColumns(['status_badge', 'vendor_type_badge', 'pic_info', 'bank_info', 'branches_count_display', 'created_info', 'actions'])
            ->make(true);
    }

    /**
     * Generate action buttons for DataTable
     */
    private function getActionButtons(Vendor $vendor): string
    {
        $actions = '<div class="d-flex align-items-center gap-1 flex-nowrap">';

        if (Auth::user()->can('view_vendors')) {
            $actions .= '<a href="' . route('admin.vendors.show', $vendor->id) . '"
                class="btn btn-sm btn-info" title="View">
                <i class="bi bi-eye"></i>
            </a>';
        }

        if ($vendor->trashed()) {
            if (Auth::user()->can('restore_vendors')) {
                $actions .= '<button type="button" class="btn btn-sm btn-success restore-vendor"
                    data-id="' . $vendor->id . '" title="Restore">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>';
            }
        } else {
            if (Auth::user()->can('edit_vendors')) {
                $actions .= '<a href="' . route('admin.vendors.edit', $vendor->id) . '"
                    class="btn btn-sm btn-primary" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>';
            }

            if (Auth::user()->can('edit_vendors')) {
                $statusIcon = $vendor->status === 'active' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
                $statusTitle = $vendor->status === 'active' ? 'Deactivate' : 'Activate';
                $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary toggle-status"
                    data-id="' . $vendor->id . '" title="' . $statusTitle . '">
                    <i class="bi ' . $statusIcon . '"></i>
                </button>';
            }

            if (Auth::user()->can('delete_vendors')) {
                $actions .= '<button type="button" class="btn btn-sm btn-danger delete-vendor"
                    data-id="' . $vendor->id . '" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>';
            }
        }

        $actions .= '</div>';
        return $actions;
    }

    /**
     * Get vendor type badge - now from VendorType relationship
     */
    private function getVendorTypeBadge(Vendor $vendor): string
    {
        $typeName = $vendor->vendorType?->title ?? ucfirst($vendor->vendor_type ?? 'Unknown');
        $colors = ['bg-primary', 'bg-info', 'bg-warning', 'bg-success', 'bg-secondary', 'bg-dark'];
        $colorIndex = $vendor->vendor_type_id ? (($vendor->vendor_type_id - 1) % count($colors)) : 5;

        return '<span class="badge ' . $colors[$colorIndex] . '">' . e($typeName) . '</span>';
    }

    /**
     * Show the form for creating a new vendor
     */
    public function create(): View
    {
        $nextCode = Vendor::generateVendorCode();
        $vendorTypes = VendorType::active()->orderBy('title')->get();

        return view('admin.vendors.create', compact('nextCode', 'vendorTypes'));
    }

    /**
     * Store a newly created vendor
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $vendor = $this->vendorService->createVendor($request->validated());

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($vendor)
                ->withProperties($request->except(['bank_account_no']))
                ->log('Vendor created');

            return response()->json([
                'success' => true,
                'message' => 'Vendor created successfully',
                'vendor' => $vendor,
                'redirect' => route('admin.vendors.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create vendor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified vendor
     */
    public function show(Vendor $vendor): View
    {
        $vendor->load([
            'vendorType',
            'branches.state',
            'branches.city',
            'purchaseOrders' => function ($query) {
                $query->latest()->limit(10);
            },
            'grns' => function ($query) {
                $query->latest()->limit(10);
            },
            'invoices' => function ($query) {
                $query->where('invoice_type', 'ap')
                      ->latest()
                      ->limit(10);
            },
            'createdBy',
            'updatedBy'
        ]);

        $statistics = $this->vendorService->getVendorStatistics($vendor);
        $apAging = $this->vendorService->getApAging($vendor);

        return view('admin.vendors.show', compact('vendor', 'statistics', 'apAging'));
    }

    /**
     * Show the form for editing the specified vendor
     */
    public function edit(Vendor $vendor): View
    {
        $vendor->load(['branches.state', 'branches.city', 'vendorType']);
        $vendorTypes = VendorType::active()->orderBy('title')->get();

        return view('admin.vendors.edit', compact('vendor', 'vendorTypes'));
    }

    /**
     * Update the specified vendor
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->vendorService->updateVendor($vendor, $request->validated());

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($vendor)
                ->withProperties($request->except(['bank_account_no']))
                ->log('Vendor updated');

            return response()->json([
                'success' => true,
                'message' => 'Vendor updated successfully',
                'vendor' => $vendor->fresh(['branches']),
                'redirect' => route('admin.vendors.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update vendor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified vendor from storage
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        try {
            if ($vendor->purchaseOrders()->whereNotIn('status', ['closed', 'cancelled'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete vendor with active purchase orders.'
                ], 422);
            }

            if ($vendor->invoices()->where('status', '!=', 'paid')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete vendor with pending invoices.'
                ], 422);
            }

            DB::beginTransaction();

            $vendor->update(['updated_by' => Auth::id()]);
            $vendor->delete();

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($vendor)
                ->log('Vendor deleted');

            return response()->json([
                'success' => true,
                'message' => 'Vendor deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vendor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted vendor
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $vendor = Vendor::withTrashed()->findOrFail($id);

            if (!$vendor->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor is not deleted'
                ], 422);
            }

            DB::beginTransaction();

            $vendor->restore();
            $vendor->update(['updated_by' => Auth::id()]);

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($vendor)
                ->log('Vendor restored');

            return response()->json([
                'success' => true,
                'message' => 'Vendor restored successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore vendor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle vendor status (active/inactive)
     */
    public function toggleStatus(Vendor $vendor): JsonResponse
    {
        try {
            DB::beginTransaction();

            $newStatus = $vendor->status === Vendor::STATUS_ACTIVE
                ? Vendor::STATUS_INACTIVE
                : Vendor::STATUS_ACTIVE;

            $vendor->update([
                'status' => $newStatus,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($vendor)
                ->withProperties(['status' => $newStatus])
                ->log('Vendor status toggled');

            return response()->json([
                'success' => true,
                'message' => 'Vendor status updated successfully',
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
     * Suggest vendor codes based on vendor name (AJAX)
     */
    public function suggestCode(Request $request): JsonResponse
    {
        $vendorName = $request->get('vendor_name', '');
        $suggestions = Vendor::suggestVendorCodes($vendorName);
        $defaultCode = Vendor::generateVendorCode();

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'default_code' => $defaultCode,
        ]);
    }

    /**
     * Check if vendor code is available (AJAX)
     */
    public function checkCode(Request $request): JsonResponse
    {
        $code = $request->get('vendor_code', '');
        $excludeId = $request->get('exclude_id');

        $available = Vendor::isCodeAvailable($code, $excludeId ? (int) $excludeId : null);

        return response()->json([
            'success' => true,
            'available' => $available,
        ]);
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
            ->log('Vendors exported');

        return Excel::download(new VendorsExport($request->all()), $filename);
    }

    /**
     * Import vendors from Excel
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $import = new VendorsImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            activity()
                ->causedBy(Auth::user())
                ->withProperties($results)
                ->log('Vendors imported');

            return response()->json([
                'success' => true,
                'message' => "Import completed. {$results['success']} vendors imported, {$results['failed']} failed.",
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
        $filename = 'vendor_import_template.xlsx';

        return Excel::download(new VendorsExport([], true), $filename);
    }

    /**
     * Get vendors list for dropdowns (AJAX)
     */
    public function getList(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $type = $request->get('type');

        $query = Vendor::active()
            ->select('id', 'vendor_code', 'vendor_name', 'vendor_type', 'vendor_type_id');

        if ($type) {
            // Support both legacy enum and new FK
            if (is_numeric($type)) {
                $query->where('vendor_type_id', $type);
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
                    'vendor_type' => $vendor->vendor_type,
                    'vendor_type_id' => $vendor->vendor_type_id
                ];
            })
        ]);
    }
}
