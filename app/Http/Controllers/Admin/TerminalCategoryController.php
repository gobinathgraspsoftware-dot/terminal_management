<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TerminalCategory\StoreTerminalCategoryRequest;
use App\Http\Requests\Admin\TerminalCategory\UpdateTerminalCategoryRequest;
use App\Models\TerminalCategory;
use App\Services\TerminalCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Admin Terminal Category Controller
 *
 * Handles terminal category management for Admin:
 * - Full CRUD operations
 * - Drag-drop sorting
 * - Serial tracking toggle
 * - Status management
 *
 * @package App\Http\Controllers\Admin
 */
class TerminalCategoryController extends Controller implements HasMiddleware
{
    protected TerminalCategoryService $categoryService;

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

    public function __construct(TerminalCategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display listing of terminal categories.
     */
    public function index(): View
    {
        Gate::authorize('view', TerminalCategory::class);

        $categories = TerminalCategory::withCount('terminalModels')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        $stats = [
            'total_categories' => TerminalCategory::count(),
            'active_categories' => TerminalCategory::where('status', 'active')->count(),
            'serial_tracked' => TerminalCategory::where('is_serial_tracked', true)->count(),
            'total_models' => \App\Models\TerminalModel::count(),
        ];

        return view('admin.terminal-categories.index', compact('categories', 'stats'));
    }

    /**
     * Get DataTable data for categories.
     */
    public function datatable(Request $request): JsonResponse
    {
        Gate::authorize('view', TerminalCategory::class);

        $query = TerminalCategory::withCount('terminalModels')
            ->select('terminal_categories.*');

        return DataTables::of($query)
            ->addColumn('type_badge', function ($category) {
                $colors = [
                    'terminal' => 'primary',
                    'router' => 'info',
                    'sim' => 'warning',
                    'accessory' => 'secondary',
                    'other' => 'dark',
                ];
                $color = $colors[$category->category_type] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($category->category_type) . '</span>';
            })
            ->addColumn('serial_tracking', function ($category) {
                $checked = $category->is_serial_tracked ? 'checked' : '';
                $disabled = Gate::denies('update', $category) ? 'disabled' : '';
                return '<div class="form-check form-switch">
                    <input class="form-check-input serial-toggle" type="checkbox" 
                           data-id="' . $category->id . '" ' . $checked . ' ' . $disabled . '>
                </div>';
            })
            ->addColumn('model_count', function ($category) {
                $count = $category->terminal_models_count;
                $badge = $count > 0 ? 'bg-success' : 'bg-secondary';
                return '<span class="badge ' . $badge . '">' . $count . ' models</span>';
            })
            ->addColumn('status_badge', function ($category) {
                $badge = $category->status === 'active' ? 'bg-success' : 'bg-danger';
                return '<span class="badge ' . $badge . '">' . ucfirst($category->status) . '</span>';
            })
            ->addColumn('action', function ($category) {
                $actions = '';
                
                if (Gate::allows('update', $category)) {
                    $actions .= '<a href="' . route('admin.terminal-categories.edit', $category->id) . '" 
                                   class="btn btn-sm btn-primary me-1" title="Edit">
                                   <i class="bi bi-pencil"></i>
                               </a>';
                }
                
                if (Gate::allows('delete', $category)) {
                    $actions .= '<button type="button" class="btn btn-sm btn-danger delete-category" 
                                        data-id="' . $category->id . '" 
                                        data-name="' . htmlspecialchars($category->category_name) . '"
                                        title="Delete">
                                   <i class="bi bi-trash"></i>
                               </button>';
                }
                
                return $actions ?: '<span class="text-muted">No actions</span>';
            })
            ->rawColumns(['type_badge', 'serial_tracking', 'model_count', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
    {
        Gate::authorize('create', TerminalCategory::class);

        return view('admin.terminal-categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreTerminalCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Terminal category created successfully',
                'category' => $category,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the category.
     */
    public function edit(TerminalCategory $terminalCategory): View
    {
        Gate::authorize('update', $terminalCategory);

        $category = $terminalCategory->load('terminalModels');

        return view('admin.terminal-categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateTerminalCategoryRequest $request, TerminalCategory $terminalCategory): JsonResponse
    {
        try {
            $category = $this->categoryService->update($terminalCategory, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Terminal category updated successfully',
                'category' => $category,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(TerminalCategory $terminalCategory): JsonResponse
    {
        Gate::authorize('delete', $terminalCategory);

        try {
            // Check if category has associated models
            if ($terminalCategory->terminalModels()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with associated terminal models',
                ], 422);
            }

            $this->categoryService->delete($terminalCategory);

            return response()->json([
                'success' => true,
                'message' => 'Terminal category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle category status.
     */
    public function toggleStatus(TerminalCategory $terminalCategory): JsonResponse
    {
        Gate::authorize('update', $terminalCategory);

        try {
            $newStatus = $terminalCategory->status === 'active' ? 'inactive' : 'active';
            $terminalCategory->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => 'Category status updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle serial tracking.
     */
    public function toggleSerialTracking(TerminalCategory $terminalCategory): JsonResponse
    {
        Gate::authorize('update', $terminalCategory);

        try {
            $newValue = !$terminalCategory->is_serial_tracked;
            $terminalCategory->update(['is_serial_tracked' => $newValue]);

            return response()->json([
                'success' => true,
                'is_serial_tracked' => $newValue,
                'message' => 'Serial tracking updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update serial tracking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update sort order for categories (drag-drop).
     */
    public function updateSortOrder(Request $request): JsonResponse
    {
        Gate::authorize('update', TerminalCategory::class);

        try {
            $request->validate([
                'orders' => 'required|array',
                'orders.*.id' => 'required|exists:terminal_categories,id',
                'orders.*.sort_order' => 'required|integer|min:0',
            ]);

            $this->categoryService->updateSortOrder($request->input('orders'));

            return response()->json([
                'success' => true,
                'message' => 'Sort order updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sort order: ' . $e->getMessage(),
            ], 500);
        }
    }
}
