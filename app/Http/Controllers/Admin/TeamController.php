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
 * Handles team management for Admin users:
 * - View all teams (supervisors + their technicians)
 * - Assign technicians to supervisors (mandatory)
 * - Bulk assign technicians
 * - View team statistics
 *
 * NOTE: Independent technicians are NOT supported.
 *       All technicians MUST have a supervisor assigned.
 *       Supervisors can operate without technicians.
 *
 * @package App\Http\Controllers\Admin
 */
class TeamController extends Controller implements HasMiddleware
{
    protected TeamService $teamService;

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
            ->with(['technicians' => fn($q) => $q->where('status', 'active')->with('roles')])
            ->orderBy('name')
            ->get();

        // Unassigned technicians — these need to be assigned to a supervisor
        $unassignedTechnicians = User::whereHas('roles', fn($q) => $q->where('roles.name', 'technician'))
            ->whereNull('supervisor_id')
            ->where('status', 'active')
            ->get();

        return view('admin.teams.index', compact('statistics', 'supervisors', 'unassignedTechnicians', 'view'));
    }

    /**
     * DataTable for team members.
     */
    public function datatable(Request $request): JsonResponse
    {
        $view = $request->get('view', 'all');

        // For supervisors view - return supervisor data
        if ($view === 'supervisors') {
            return $this->supervisorsDatatable($request);
        }

        // For technicians view - all technicians (all must have supervisor)
        $query = User::whereHas('roles', fn($q) => $q->where('roles.name', 'technician'))
            ->with(['roles', 'supervisor'])
            ->select('users.*');

        // Filter by supervisor
        if ($request->supervisor_id) {
            $query->where('supervisor_id', $request->supervisor_id);
        }

        // Filter unassigned (needs attention)
        if ($view === 'unassigned') {
            $query->whereNull('supervisor_id');
        }

        return DataTables::of($query)
            ->addColumn('role', fn($user) => '<span class="badge bg-success">Technician</span>')
            ->addColumn('supervisor_name', fn($user) => $user->supervisor
                ? '<span class="text-primary">' . e($user->supervisor->name) . '</span>'
                : '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Unassigned</span>')
            ->addColumn('status_badge', fn($user) => '<span class="badge bg-' . ($user->status == 'active' ? 'success' : 'secondary') . '">' . ucfirst($user->status) . '</span>')
            ->addColumn('coverage', function($user) {
                $states = is_array($user->coverage_states) ? $user->coverage_states : (is_string($user->coverage_states) ? json_decode($user->coverage_states, true) : null);
                return !empty($states) && is_array($states) ? implode(', ', $states) : '-';
            })
            ->addColumn('actions', function($user) {
                $actions = '<div class="d-flex align-items-center gap-1 flex-nowrap">';
                $actions .= '<a href="' . route('admin.teams.show', $user->id) . '" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>';
                $actions .= '<button type="button" class="btn btn-sm btn-primary reassign-technician" data-id="' . $user->id . '" data-name="' . e($user->name) . '" data-supervisor="' . ($user->supervisor_id ?? '') . '" title="Reassign"><i class="bi bi-arrow-left-right"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->filter(function($query) use ($request) {
                if ($search = $request->search['value'] ?? null) {
                    $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
                }
                if ($request->status) {
                    $query->where('status', $request->status);
                }
            })
            ->rawColumns(['role', 'supervisor_name', 'status_badge', 'actions'])
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
            ->addColumn('role', fn($user) => '<span class="badge bg-primary">Supervisor</span>')
            ->addColumn('team_size', fn($user) => '<span class="badge bg-info">' . $user->technicians_count . ' members</span>')
            ->addColumn('status_badge', fn($user) => '<span class="badge bg-' . ($user->status == 'active' ? 'success' : 'secondary') . '">' . ucfirst($user->status) . '</span>')
            ->addColumn('coverage', function($user) {
                $states = is_array($user->coverage_states) ? $user->coverage_states : (is_string($user->coverage_states) ? json_decode($user->coverage_states, true) : null);
                return !empty($states) && is_array($states) ? implode(', ', $states) : '-';
            })
            ->addColumn('actions', function($user) {
                $actions = '<div class="d-flex align-items-center gap-1 flex-nowrap">';
                $actions .= '<a href="' . route('admin.teams.show', $user->id) . '" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>';
                $actions .= '</div>';
                return $actions;
            })
            ->filter(function($query) use ($request) {
                if ($search = $request->search['value'] ?? null) {
                    $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%"));
                }
                if ($request->status) {
                    $query->where('status', $request->status);
                }
            })
            ->rawColumns(['role', 'team_size', 'status_badge', 'actions'])
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
                    'coverage_states' => $user->coverage_states ?? [],
                    'skill_tags' => $user->skill_tags ?? [],
                    'supervisor' => $user->supervisor,
                ],
                'statistics' => $statistics
            ]);
        }

        $supervisors = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.teams.show', compact('user', 'statistics', 'recentJobs', 'chartData', 'assignmentHistory', 'supervisors'));
    }

    /**
     * Assign single technician to supervisor.
     * supervisor_id is REQUIRED — no independent technicians allowed.
     */
    public function assign(AssignTechnicianRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $technician = User::findOrFail($request->technician_id);
            $oldSupervisorId = $technician->supervisor_id;

            $technician->update(['supervisor_id' => $request->supervisor_id]);
            $this->teamService->logTeamChange($technician, $oldSupervisorId, $request->supervisor_id, Auth::user());

            DB::commit();

            $supervisorName = User::find($request->supervisor_id)->name;
            return response()->json([
                'success' => true,
                'message' => "{$technician->name} assigned to {$supervisorName}"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk assign technicians to supervisor.
     * supervisor_id is REQUIRED — no independent technicians allowed.
     */
    public function bulkAssign(BulkAssignRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

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

            $supervisorName = User::find($request->supervisor_id)->name;
            return response()->json([
                'success' => true,
                'message' => "{$count} technician(s) assigned to {$supervisorName}",
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
     */
    public function supervisorsList(Request $request): JsonResponse
    {
        $query = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('status', 'active')
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')]);

        if ($search = $request->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%"));
        }

        return response()->json(['success' => true, 'supervisors' => $query->orderBy('name')->limit(50)->get()]);
    }
}
