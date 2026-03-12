<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorTypeRequest;
use App\Http\Requests\Admin\UpdateVendorTypeRequest;
use App\Models\VendorType;
use App\Services\VendorTypeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VendorTypeController extends Controller
{
    use AuthorizesRequests;

    protected VendorTypeService $vendorTypeService;

    public function __construct(VendorTypeService $vendorTypeService)
    {
        $this->vendorTypeService = $vendorTypeService;
    }

    /**
     * Display listing with server-side DataTable
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', VendorType::class);

        if ($request->ajax()) {
            return $this->getDataTableData($request);
        }

        $stats = $this->vendorTypeService->getStats();

        return view('admin.vendor-types.index', compact('stats'));
    }

    /**
     * Server-side DataTable response
     */
    private function getDataTableData(Request $request): JsonResponse
    {
        $query = VendorType::query();

        // Search
        if ($search = $request->input('search.value')) {
            $query->search($search);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $totalRecords = VendorType::count();
        $filteredRecords = $query->count();

        // Sorting
        $columns = ['id', 'title', 'description', 'is_active', 'created_at'];
        $orderColumn = $columns[$request->input('order.0.column', 0)] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');
        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $vendorTypes = $query
            ->skip($request->input('start', 0))
            ->take($request->input('length', 10))
            ->get();

        $data = $vendorTypes->map(function ($type) {
            return [
                'id' => $type->id,
                'title' => e($type->title),
                'description' => e($type->description) ?: '<span class="text-muted">—</span>',
                'status_badge' => $type->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>',
                'created_at' => $type->created_at->format('d M Y'),
                'actions' => $this->getActionButtons($type),
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Action buttons for DataTable
     */
    private function getActionButtons(VendorType $vendorType): string
    {
        $actions = '<div class="btn-group btn-group-sm">';

        if (Auth::user()->can('edit_vendor_types')) {
            $actions .= '<a href="' . route('admin.vendor-types.edit', $vendorType) . '"
                class="btn btn-sm btn-primary" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>';

            $statusIcon = $vendorType->is_active ? 'bi-toggle-on' : 'bi-toggle-off';
            $statusTitle = $vendorType->is_active ? 'Deactivate' : 'Activate';
            $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary toggle-status"
                data-id="' . $vendorType->id . '" title="' . $statusTitle . '">
                <i class="bi ' . $statusIcon . '"></i>
            </button>';
        }

        if (Auth::user()->can('delete_vendor_types')) {
            $actions .= '<button type="button" class="btn btn-sm btn-danger delete-vendor-type"
                data-id="' . $vendorType->id . '" title="Delete">
                <i class="bi bi-trash"></i>
            </button>';
        }

        $actions .= '</div>';
        return $actions;
    }

    /**
     * Show create form
     */
    public function create(): View
    {
        $this->authorize('create', VendorType::class);

        return view('admin.vendor-types.create');
    }

    /**
     * Store new vendor type
     */
    public function store(StoreVendorTypeRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $vendorType = $this->vendorTypeService->createVendorType($request->validated());

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($vendorType)
                ->withProperties($request->validated())
                ->log('Vendor type created');

            return response()->json([
                'success' => true,
                'message' => 'Vendor type created successfully',
                'redirect' => route('admin.vendor-types.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create vendor type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show edit form
     */
    public function edit(VendorType $vendorType): View
    {
        $this->authorize('update', $vendorType);

        return view('admin.vendor-types.edit', compact('vendorType'));
    }

    /**
     * Update vendor type
     */
    public function update(UpdateVendorTypeRequest $request, VendorType $vendorType): JsonResponse
    {
        try {
            DB::beginTransaction();

            $vendorType = $this->vendorTypeService->updateVendorType($vendorType, $request->validated());

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($vendorType)
                ->withProperties($request->validated())
                ->log('Vendor type updated');

            return response()->json([
                'success' => true,
                'message' => 'Vendor type updated successfully',
                'redirect' => route('admin.vendor-types.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update vendor type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete vendor type
     */
    public function destroy(VendorType $vendorType): JsonResponse
    {
        $this->authorize('delete', $vendorType);

        try {
            DB::beginTransaction();

            $title = $vendorType->title;
            $this->vendorTypeService->deleteVendorType($vendorType);

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['title' => $title])
                ->log('Vendor type deleted');

            return response()->json([
                'success' => true,
                'message' => 'Vendor type deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vendor type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(VendorType $vendorType): JsonResponse
    {
        $this->authorize('update', $vendorType);

        try {
            $vendorType = $this->vendorTypeService->toggleStatus($vendorType);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($vendorType)
                ->log('Vendor type status toggled to ' . ($vendorType->is_active ? 'active' : 'inactive'));

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $vendorType->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
