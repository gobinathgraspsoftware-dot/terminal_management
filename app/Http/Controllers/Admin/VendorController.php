<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\VendorService;
use App\Exports\VendorsExport;
use App\Imports\VendorsImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

// ─────────────────────────────────────────────────────────────
// REMOVED IMPORTS (models not yet built):
// - App\Models\PurchaseOrder
// - App\Models\Invoice
// ─────────────────────────────────────────────────────────────

class VendorController extends Controller
{
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

        return view('admin.vendors.index', compact('statistics'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Vendor::select('vendors.*')
            ->with(['createdBy', 'updatedBy'])
            ->withCount('branches');

        // ─────────────────────────────────────────────────────────────
        // TODO: Re-add when PO/GRN/Invoice modules are built:
        // ->withCount('purchaseOrders')
        // ->withCount('grns')
        // ->withCount(['invoices as invoices_count' => function ($q) {
        //     $q->where('invoice_type', 'ap');
        // }])
        // ─────────────────────────────────────────────────────────────

        if ($request->get('show_trashed') === 'true') {
            $query->withTrashed();
        }

        return DataTables::of($query)
            ->addColumn('status_badge', function ($vendor) {
                if ($vendor->trashed()) {
                    return '<span class="badge bg-danger">Deleted</span>';
                }

                $badgeClass = $vendor->status === Vendor::STATUS_ACTIVE ? 'bg-success' : 'bg-secondary';
                $statusText = ucfirst($vendor->status);

                return '<span class="badge ' . $badgeClass . ' status-toggle-badge"
                              data-vendor-id="' . $vendor->id . '"
                              style="cursor: pointer;"
                              title="Click to toggle status">'
                              . $statusText .
                        '</span>';
            })
            ->addColumn('vendor_type_badge', function ($vendor) {
                return $this->getVendorTypeBadge($vendor->vendor_type);
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
                return $vendor->payment_terms . ' days';
            })
            ->addColumn('purchase_orders_count', function ($vendor) {
                // TODO: Re-enable when PO module is built
                return 0;
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

                // Support both vendor_type (legacy string) and vendor_type_id (new FK)
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
     * Get vendor type badge
     */
    private function getVendorTypeBadge(?string $type): string
    {
        if (!$type) {
            return '<span class="badge bg-dark">Unknown</span>';
        }

        return match($type) {
            Vendor::TYPE_SUPPLIER => '<span class="badge bg-primary">Supplier</span>',
            Vendor::TYPE_SUBCON => '<span class="badge bg-info">Sub-contractor</span>',
            Vendor::TYPE_COURIER => '<span class="badge bg-warning">Courier</span>',
            Vendor::TYPE_OTHER => '<span class="badge bg-secondary">Other</span>',
            default => '<span class="badge bg-dark">Unknown</span>',
        };
    }

    /**
     * Show the form for creating a new vendor
     */
    public function create(): View
    {
        $nextCode = Vendor::generateVendorCode();
        $vendorTypes = $this->getVendorTypes();

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
            'branches.state',
            'branches.city',
            'vendorType',
            'createdBy',
            'updatedBy'
        ]);

        // ─────────────────────────────────────────────────────────────
        // TODO: Re-add when PO/GRN/Invoice modules are built:
        // 'purchaseOrders' => function ($query) { $query->latest()->limit(10); },
        // 'grns' => function ($query) { $query->latest()->limit(10); },
        // 'invoices' => function ($query) { $query->where('invoice_type', 'ap')->latest()->limit(10); },
        // ─────────────────────────────────────────────────────────────

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
        $vendorTypes = $this->getVendorTypes();

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
            // ─────────────────────────────────────────────────────────────
            // TODO: Re-add when PO/Invoice modules are built:
            // if ($vendor->purchaseOrders()->whereNotIn('status', ['closed', 'cancelled'])->exists()) {
            //     return response()->json(['success' => false, 'message' => 'Cannot delete vendor with active purchase orders.'], 422);
            // }
            // if ($vendor->invoices()->where('status', '!=', 'paid')->exists()) {
            //     return response()->json(['success' => false, 'message' => 'Cannot delete vendor with pending invoices.'], 422);
            // }
            // ─────────────────────────────────────────────────────────────

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

    // ==========================================
    // VENDOR CODE SUGGESTION ENDPOINTS
    // ==========================================

    /**
     * Suggest vendor codes based on vendor name (Gmail-style)
     * GET /admin/vendors/suggest-code?vendor_name=Maybank
     */
    public function suggestCode(Request $request): JsonResponse
    {
        $vendorName = $request->get('vendor_name', '');
        $defaultCode = Vendor::generateVendorCode();

        $suggestions = [];
        if (!empty(trim($vendorName))) {
            $suggestions = Vendor::suggestVendorCodes($vendorName);
        }

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'default_code' => $defaultCode,
        ]);
    }

    /**
     * Check if a vendor code is available
     * GET /admin/vendors/check-code?vendor_code=MAY
     */
    public function checkCode(Request $request): JsonResponse
    {
        $vendorCode = $request->get('vendor_code', '');
        $excludeId = $request->get('exclude_id');

        $available = Vendor::isCodeAvailable($vendorCode, $excludeId ? (int) $excludeId : null);

        return response()->json([
            'available' => $available,
            'vendor_code' => $vendorCode,
        ]);
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Get vendor types from DB for dropdowns
     * Falls back to hardcoded array if vendor_types table has no active records
     */
    private function getVendorTypes()
    {
        $dbTypes = VendorType::where('is_active', true)
            ->orderBy('title')
            ->get();

        if ($dbTypes->isNotEmpty()) {
            return $dbTypes;
        }

        // Fallback: return as collection-like array for backward compat
        return collect([
            (object) ['id' => null, 'title' => 'Supplier'],
            (object) ['id' => null, 'title' => 'Sub-contractor'],
            (object) ['id' => null, 'title' => 'Courier'],
            (object) ['id' => null, 'title' => 'Other'],
        ]);
    }
}
