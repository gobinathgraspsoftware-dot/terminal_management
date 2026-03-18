<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobCategory\StoreJobCategoryRequest;
use App\Http\Requests\Admin\JobCategory\UpdateJobCategoryRequest;
use App\Models\JobCategory;
use App\Services\JobCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class JobCategoryController extends Controller implements HasMiddleware
{
    protected JobCategoryService $jobCategoryService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:admin'),
        ];
    }

    public function __construct(JobCategoryService $jobCategoryService)
    {
        $this->jobCategoryService = $jobCategoryService;
    }

    /**
     * Display listing of job categories.
     */
    public function index(): View
    {
        Gate::authorize('view', JobCategory::class);

        $stats = [
            'total'    => JobCategory::count(),
            'active'   => JobCategory::where('status', JobCategory::STATUS_ACTIVE)->count(),
            'inactive' => JobCategory::where('status', JobCategory::STATUS_INACTIVE)->count(),
        ];

        return view('admin.job-categories.index', compact('stats'));
    }

    /**
     * Get DataTable data.
     */
    public function datatable(Request $request): JsonResponse
    {
        Gate::authorize('view', JobCategory::class);

        $query = JobCategory::select('job_categories.*');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('status_badge', function ($jobCategory) {
                $color = $jobCategory->status === JobCategory::STATUS_ACTIVE ? 'success' : 'danger';
                return '<span class="badge bg-' . $color . '">' . ucfirst($jobCategory->status) . '</span>';
            })
            ->addColumn('action', function ($jobCategory) {
                $actions = '';

                if (auth()->user()->can('update', $jobCategory)) {
                    $actions .= '<a href="' . route('admin.job-categories.edit', $jobCategory->id) . '" class="btn btn-sm btn-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>';

                    $statusIcon = $jobCategory->status === JobCategory::STATUS_ACTIVE
                        ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary me-1 btn-toggle-status" data-id="' . $jobCategory->id . '" data-status="' . $jobCategory->status . '" title="Toggle Status"><i class="bi ' . $statusIcon . '"></i></button>';
                }

                if (auth()->user()->can('delete', $jobCategory)) {
                    $actions .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $jobCategory->id . '" data-name="' . htmlspecialchars($jobCategory->category_name) . '" title="Delete"><i class="bi bi-trash"></i></button>';
                }

                return $actions ? '<div class="d-flex flex-nowrap gap-1">' . $actions . '</div>' : '<span class="text-muted">No actions</span>';
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        Gate::authorize('create', JobCategory::class);

        return view('admin.job-categories.create');
    }

    /**
     * Store a new job category.
     */
    public function store(StoreJobCategoryRequest $request): JsonResponse
    {
        try {
            $jobCategory = $this->jobCategoryService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Job category created successfully.',
                'job_category' => $jobCategory,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show edit form.
     */
    public function edit(JobCategory $jobCategory): View
    {
        Gate::authorize('update', $jobCategory);

        return view('admin.job-categories.edit', compact('jobCategory'));
    }

    /**
     * Update the job category.
     */
    public function update(UpdateJobCategoryRequest $request, JobCategory $jobCategory): JsonResponse
    {
        try {
            $jobCategory = $this->jobCategoryService->update($jobCategory, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Job category updated successfully.',
                'job_category' => $jobCategory,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the job category.
     */
    public function destroy(JobCategory $jobCategory): JsonResponse
    {
        Gate::authorize('delete', $jobCategory);

        try {
            $this->jobCategoryService->delete($jobCategory);

            return response()->json([
                'success' => true,
                'message' => 'Job category deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(JobCategory $jobCategory): JsonResponse
    {
        Gate::authorize('update', $jobCategory);

        try {
            $jobCategory = $this->jobCategoryService->toggleStatus($jobCategory);

            return response()->json([
                'success' => true,
                'message' => 'Job category status updated successfully.',
                'status' => $jobCategory->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
