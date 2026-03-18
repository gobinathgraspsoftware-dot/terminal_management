<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobType\StoreJobTypeRequest;
use App\Http\Requests\Admin\JobType\UpdateJobTypeRequest;
use App\Models\JobType;
use App\Services\JobTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class JobTypeController extends Controller implements HasMiddleware
{
    protected JobTypeService $jobTypeService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:admin'),
        ];
    }

    public function __construct(JobTypeService $jobTypeService)
    {
        $this->jobTypeService = $jobTypeService;
    }

    /**
     * Display listing of job types.
     */
    public function index(): View
    {
        Gate::authorize('view', JobType::class);

        $stats = [
            'total'    => JobType::count(),
            'active'   => JobType::where('status', JobType::STATUS_ACTIVE)->count(),
            'inactive' => JobType::where('status', JobType::STATUS_INACTIVE)->count(),
        ];

        return view('admin.job-types.index', compact('stats'));
    }

    /**
     * Get DataTable data.
     */
    public function datatable(Request $request): JsonResponse
    {
        Gate::authorize('view', JobType::class);

        $query = JobType::select('job_types.*');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('status_badge', function ($jobType) {
                $color = $jobType->status === JobType::STATUS_ACTIVE ? 'success' : 'danger';
                return '<span class="badge bg-' . $color . '">' . ucfirst($jobType->status) . '</span>';
            })
            ->addColumn('action', function ($jobType) {
                $actions = '';

                if (auth()->user()->can('update', $jobType)) {
                    $actions .= '<a href="' . route('admin.job-types.edit', $jobType->id) . '" class="btn btn-sm btn-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>';

                    $statusIcon = $jobType->status === JobType::STATUS_ACTIVE
                        ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary me-1 btn-toggle-status" data-id="' . $jobType->id . '" data-status="' . $jobType->status . '" title="Toggle Status"><i class="bi ' . $statusIcon . '"></i></button>';
                }

                if (auth()->user()->can('delete', $jobType)) {
                    $actions .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $jobType->id . '" data-name="' . htmlspecialchars($jobType->job_title) . '" title="Delete"><i class="bi bi-trash"></i></button>';
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
        Gate::authorize('create', JobType::class);

        return view('admin.job-types.create');
    }

    /**
     * Store a new job type.
     */
    public function store(StoreJobTypeRequest $request): JsonResponse
    {
        try {
            $jobType = $this->jobTypeService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Job type created successfully.',
                'job_type' => $jobType,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show edit form.
     */
    public function edit(JobType $jobType): View
    {
        Gate::authorize('update', $jobType);

        return view('admin.job-types.edit', compact('jobType'));
    }

    /**
     * Update the job type.
     */
    public function update(UpdateJobTypeRequest $request, JobType $jobType): JsonResponse
    {
        try {
            $jobType = $this->jobTypeService->update($jobType, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Job type updated successfully.',
                'job_type' => $jobType,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the job type.
     */
    public function destroy(JobType $jobType): JsonResponse
    {
        Gate::authorize('delete', $jobType);

        try {
            $this->jobTypeService->delete($jobType);

            return response()->json([
                'success' => true,
                'message' => 'Job type deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(JobType $jobType): JsonResponse
    {
        Gate::authorize('update', $jobType);

        try {
            $jobType = $this->jobTypeService->toggleStatus($jobType);

            return response()->json([
                'success' => true,
                'message' => 'Job type status updated successfully.',
                'status' => $jobType->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
