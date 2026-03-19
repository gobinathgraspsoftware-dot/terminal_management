<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Team\AssignTechnicianRequest;
use App\Http\Requests\Admin\Team\BulkAssignRequest;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Admin TeamController
 *
 * NOTE: Independent technicians are NOT supported.
 *       All technicians MUST have a supervisor assigned.
 *       Internal supervisors have technician teams.
 *       External supervisors do NOT have technician teams.
 *
 * @package App\Http\Controllers\Admin
 */
class TeamController extends Controller implements HasMiddleware
{
    protected TeamService $teamService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:admin'),
        ];
    }

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Display team management dashboard.
     */
    public function index(Request $request): View
    {
        $view = $request->get('view', 'all');

        $statistics = $this->teamService->getTeamStatistics();

        $supervisors = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('status', 'active')
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $unassignedTechnicians = User::whereHas('roles', fn($q) => $q->where('roles.name', 'technician'))
            ->whereNull('supervisor_id')
            ->where('status', 'active')
            ->get();

        return view('admin.teams.index', compact('statistics', 'supervisors', 'unassignedTechnicians', 'view'));
    }

    /**
     * DataTable for team members — supports all views.
     */
    public function datatable(Request $request): JsonResponse
    {
        $view = $request->get('view', 'all');

        if ($view === 'supervisors') {
            return $this->supervisorsDatatable($request);
        }

        if ($view === 'technicians') {
            return $this->techniciansDatatable($request);
        }

        // "all" view — both supervisors and technicians
        return $this->allTeamsDatatable($request);
    }

    /**
     * All teams datatable — supervisors + technicians combined.
     */
    protected function allTeamsDatatable(Request $request): JsonResponse
    {
        $query = User::whereHas('roles', fn($q) => $q->whereIn('roles.name', ['supervisor', 'technician']))
            ->with(['roles', 'supervisor'])
            ->select('users.*');

        if ($request->supervisor_id) {
            $query->where(function ($q) use ($request) {
                $q->where('id', $request->supervisor_id)
                  ->orWhere('supervisor_id', $request->supervisor_id);
            });
        }

        return DataTables::of($query)
            ->addColumn('avatar', function ($user) {
                $url = $user->avatar
                    ? asset('storage/' . $user->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode(substr($user->name, 0, 1)) . '&size=36&background=random&color=fff';
                return '<img src="' . $url . '" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">';
            })
            ->addColumn('role', function ($user) {
                $role = $user->roles->first()?->name ?? 'unknown';
                $badge = $role === 'supervisor' ? 'primary' : 'success';
                $html = '<span class="badge bg-' . $badge . '">' . ucfirst($role) . '</span>';

                // Show supervisor_type badge for supervisors
                if ($role === 'supervisor' && $user->supervisor_type) {
                    $typeClass = $user->supervisor_type === 'internal' ? 'info' : 'warning';
                    $html .= ' <span class="badge bg-' . $typeClass . '">' . ucfirst($user->supervisor_type) . '</span>';
                }

                return $html;
            })
            ->addColumn('supervisor_info', function ($user) {
                if ($user->hasRole('supervisor')) {
                    if ($user->isExternalSupervisor()) {
                        return '<span class="badge bg-warning text-dark"><i class="bi bi-box-arrow-up-right me-1"></i>External (No Team)</span>';
                    }
                    $count = $user->technicians()->where('status', 'active')->count();
                    return '<span class="text-muted">' . $count . ' technician(s)</span>';
                }
                if ($user->supervisor) {
                    return '<span class="text-primary">' . e($user->supervisor->name) . '</span>';
                }
                return '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Unassigned</span>';
            })
            ->addColumn('status_badge', fn($user) => '<span class="badge bg-' . match($user->status) { 'active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger', default => 'warning' } . '">' . ucfirst($user->status) . '</span>')
            ->addColumn('coverage', function ($user) {
                $states = is_array($user->coverage_states) ? $user->coverage_states : (is_string($user->coverage_states) ? json_decode($user->coverage_states, true) : null);
                if (empty($states) || !is_array($states)) return '<span class="text-muted">-</span>';
                $html = '';
                foreach (array_slice($states, 0, 2) as $s) {
                    $html .= '<span class="badge bg-light text-dark me-1">' . e($s) . '</span>';
                }
                if (count($states) > 2) {
                    $html .= '<span class="badge bg-light text-dark">+' . (count($states) - 2) . '</span>';
                }
                return $html;
            })
            ->addColumn('actions', function ($user) {
                $actions = '<div class="d-flex align-items-center justify-content-center gap-1">';
                $actions .= '<a href="' . route('admin.teams.show', $user->id) . '" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="View"><i class="bi bi-eye"></i></a>';
                if ($user->hasRole('technician')) {
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-primary reassign-technician" data-id="' . $user->id . '" data-name="' . e($user->name) . '" data-supervisor="' . ($user->supervisor_id ?? '') . '" data-bs-toggle="tooltip" title="Reassign"><i class="bi bi-arrow-left-right"></i></button>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->filter(function ($query) use ($request) {
                if ($search = $request->search['value'] ?? null) {
                    $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
                }
                if ($request->status) {
                    $query->where('status', $request->status);
                }
                if ($request->supervisor_type) {
                    $query->where('supervisor_type', $request->supervisor_type);
                }
            })
            ->rawColumns(['avatar', 'role', 'supervisor_info', 'status_badge', 'coverage', 'actions'])
            ->make(true);
    }

    /**
     * Supervisors datatable.
     */
    protected function supervisorsDatatable(Request $request): JsonResponse
    {
        $query = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')])
            ->select('users.*');

        return DataTables::of($query)
            ->addColumn('avatar', function ($user) {
                $url = $user->avatar
                    ? asset('storage/' . $user->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode(substr($user->name, 0, 1)) . '&size=36&background=random&color=fff';
                return '<img src="' . $url . '" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">';
            })
            ->addColumn('supervisor_type_badge', function ($user) {
                if ($user->supervisor_type === 'internal') {
                    return '<span class="badge bg-info"><i class="bi bi-people me-1"></i>Internal</span>';
                }
                if ($user->supervisor_type === 'external') {
                    return '<span class="badge bg-warning text-dark"><i class="bi bi-box-arrow-up-right me-1"></i>External</span>';
                }
                return '<span class="badge bg-secondary">Not Set</span>';
            })
            ->addColumn('team_size', function ($user) {
                if ($user->supervisor_type === 'external') {
                    return '<span class="text-muted">N/A</span>';
                }
                return '<span class="badge bg-info">' . $user->technicians_count . ' members</span>';
            })
            ->addColumn('status_badge', fn($user) => '<span class="badge bg-' . ($user->status == 'active' ? 'success' : 'secondary') . '">' . ucfirst($user->status) . '</span>')
            ->addColumn('coverage', function ($user) {
                $states = is_array($user->coverage_states) ? $user->coverage_states : (is_string($user->coverage_states) ? json_decode($user->coverage_states, true) : null);
                if (empty($states) || !is_array($states)) return '<span class="text-muted">-</span>';
                $html = '';
                foreach (array_slice($states, 0, 2) as $s) {
                    $html .= '<span class="badge bg-light text-dark me-1">' . e($s) . '</span>';
                }
                if (count($states) > 2) {
                    $html .= '<span class="badge bg-light text-dark">+' . (count($states) - 2) . '</span>';
                }
                return $html;
            })
            ->addColumn('actions', function ($user) {
                return '<div class="d-flex align-items-center justify-content-center gap-1">'
                    . '<a href="' . route('admin.teams.show', $user->id) . '" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="View"><i class="bi bi-eye"></i></a>'
                    . '</div>';
            })
            ->filter(function ($query) use ($request) {
                if ($search = $request->search['value'] ?? null) {
                    $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
                }
                if ($request->status) {
                    $query->where('status', $request->status);
                }
                if ($request->supervisor_type) {
                    $query->where('supervisor_type', $request->supervisor_type);
                }
            })
            ->rawColumns(['avatar', 'supervisor_type_badge', 'team_size', 'status_badge', 'coverage', 'actions'])
            ->make(true);
    }

    /**
     * Technicians datatable.
     */
    protected function techniciansDatatable(Request $request): JsonResponse
    {
        $query = User::whereHas('roles', fn($q) => $q->where('roles.name', 'technician'))
            ->with('supervisor')
            ->select('users.*');

        if ($request->supervisor_id) {
            $query->where('supervisor_id', $request->supervisor_id);
        }

        return DataTables::of($query)
            ->addColumn('avatar', function ($user) {
                $url = $user->avatar
                    ? asset('storage/' . $user->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode(substr($user->name, 0, 1)) . '&size=36&background=random&color=fff';
                return '<img src="' . $url . '" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">';
            })
            ->addColumn('supervisor_name', function ($user) {
                if ($user->supervisor) {
                    $typeBadge = '';
                    if ($user->supervisor->supervisor_type) {
                        $typeClass = $user->supervisor->supervisor_type === 'internal' ? 'info' : 'warning';
                        $typeBadge = ' <span class="badge bg-' . $typeClass . ' ms-1" style="font-size:0.65em;">' . ucfirst($user->supervisor->supervisor_type) . '</span>';
                    }
                    return '<span class="text-primary">' . e($user->supervisor->name) . '</span>' . $typeBadge;
                }
                return '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Unassigned</span>';
            })
            ->addColumn('status_badge', fn($user) => '<span class="badge bg-' . ($user->status == 'active' ? 'success' : 'secondary') . '">' . ucfirst($user->status) . '</span>')
            ->addColumn('coverage', function ($user) {
                $states = is_array($user->coverage_states) ? $user->coverage_states : (is_string($user->coverage_states) ? json_decode($user->coverage_states, true) : null);
                if (empty($states) || !is_array($states)) return '<span class="text-muted">-</span>';
                $html = '';
                foreach (array_slice($states, 0, 2) as $s) {
                    $html .= '<span class="badge bg-light text-dark me-1">' . e($s) . '</span>';
                }
                if (count($states) > 2) {
                    $html .= '<span class="badge bg-light text-dark">+' . (count($states) - 2) . '</span>';
                }
                return $html;
            })
            ->addColumn('actions', function ($user) {
                return '<div class="d-flex align-items-center justify-content-center gap-1">'
                    . '<a href="' . route('admin.teams.show', $user->id) . '" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="View"><i class="bi bi-eye"></i></a>'
                    . '<button type="button" class="btn btn-sm btn-outline-primary reassign-technician" data-id="' . $user->id . '" data-name="' . e($user->name) . '" data-supervisor="' . ($user->supervisor_id ?? '') . '" data-bs-toggle="tooltip" title="Reassign"><i class="bi bi-arrow-left-right"></i></button>'
                    . '</div>';
            })
            ->filter(function ($query) use ($request) {
                if ($search = $request->search['value'] ?? null) {
                    $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
                }
                if ($request->status) {
                    $query->where('status', $request->status);
                }
            })
            ->rawColumns(['avatar', 'supervisor_name', 'status_badge', 'coverage', 'actions'])
            ->make(true);
    }

    /**
     * Show team member details.
     */
    public function show(User $user): View|JsonResponse
    {
        $user->load(['roles', 'supervisor', 'technicians']);

        $statistics = $this->teamService->getMemberStatistics($user);
        $recentJobs = $this->teamService->getMemberRecentJobs($user);
        $chartData = $this->teamService->getMemberWeeklyPerformance($user);
        $assignmentHistory = $this->teamService->getAssignmentHistory($user);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'employee_id' => $user->employee_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'supervisor_type' => $user->supervisor_type,
                    'coverage_states' => $user->coverage_states ?? [],
                    'skill_tags' => $user->skill_tags ?? [],
                    'supervisor' => $user->supervisor,
                ],
                'statistics' => $statistics
            ]);
        }

        // Only internal supervisors for assignment dropdown
        $supervisors = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('supervisor_type', 'internal')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.teams.show', compact('user', 'statistics', 'recentJobs', 'chartData', 'assignmentHistory', 'supervisors'));
    }

    /**
     * Assign single technician to supervisor (supervisor MUST be internal).
     */
    public function assign(AssignTechnicianRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $supervisor = User::findOrFail($request->supervisor_id);

            // Double-check: only internal supervisors can have technicians
            if ($supervisor->isExternalSupervisor()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign technicians to an external supervisor. Only internal supervisors can have teams.'
                ], 422);
            }

            $technician = User::findOrFail($request->technician_id);
            $oldSupervisorId = $technician->supervisor_id;

            $technician->update(['supervisor_id' => $request->supervisor_id]);
            $this->teamService->logTeamChange($technician, $oldSupervisorId, $request->supervisor_id, Auth::user());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$technician->name} assigned to {$supervisor->name}"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk assign technicians to supervisor (supervisor MUST be internal).
     */
    public function bulkAssign(BulkAssignRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $supervisor = User::findOrFail($request->supervisor_id);

            // Double-check: only internal supervisors can have technicians
            if ($supervisor->isExternalSupervisor()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign technicians to an external supervisor. Only internal supervisors can have teams.'
                ], 422);
            }

            $count = 0;
            foreach ($request->technician_ids as $id) {
                $technician = User::find($id);
                if ($technician && $technician->hasRole('technician')) {
                    $oldSupervisorId = $technician->supervisor_id;
                    $technician->update(['supervisor_id' => $request->supervisor_id]);
                    $this->teamService->logTeamChange($technician, $oldSupervisorId, $request->supervisor_id, Auth::user());
                    $count++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$count} technician(s) assigned to {$supervisor->name}",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get supervisor team stats.
     */
    public function stats(User $user): JsonResponse
    {
        if (!$user->hasRole('supervisor')) {
            return response()->json(['success' => false, 'message' => 'Not a supervisor'], 422);
        }

        return response()->json(['success' => true, 'statistics' => $this->teamService->getSupervisorTeamStats($user)]);
    }

    /**
     * Get supervisors list for dropdown/AJAX.
     * Only returns INTERNAL supervisors (they are the ones that can have teams).
     */
    public function supervisorsList(Request $request): JsonResponse
    {
        $query = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('supervisor_type', 'internal')
            ->where('status', 'active')
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')]);

        if ($search = $request->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%"));
        }

        return response()->json(['success' => true, 'supervisors' => $query->orderBy('name')->limit(50)->get()]);
    }
}
