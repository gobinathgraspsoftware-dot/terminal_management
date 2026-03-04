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
     * Display admin team management dashboard.
     */
    public function index(Request $request): View
    {
        $currentView = $request->query('view', 'all');

        $supervisors = User::role('supervisor')
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $independentTechnicians = User::role('technician')
            ->whereNull('supervisor_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $allTechnicians = User::role('technician')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $statistics = $this->teamService->getTeamStatistics();

        return view('admin.teams.index', compact(
            'supervisors', 'independentTechnicians', 'allTechnicians', 'statistics', 'currentView'
        ));
    }

    /**
     * Get datatable data based on view type.
     *
     * KEY FIX: Uses whereHas('roles') instead of Spatie's role() scope.
     * Spatie's role() does a JOIN on roles/model_has_roles tables, creating
     * ambiguous `name` column (users.name vs roles.name). This causes
     * "Column 'name' in order clause is ambiguous" SQL errors.
     */
    public function datatable(Request $request): JsonResponse
    {
        try {
            $view = $request->get('view', 'all');

            if ($view === 'supervisors') {
                return $this->supervisorsDatatable($request);
            }

            // Use whereHas instead of role() to avoid JOIN ambiguity
            $query = User::whereHas('roles', function ($q) {
                    $q->where('roles.name', 'technician');
                })
                ->with(['supervisor:id,name'])
                ->select('users.*');

            // Apply view-based filtering
            switch ($view) {
                case 'technicians':
                    $query->whereNotNull('users.supervisor_id');
                    break;
                case 'independent':
                    $query->whereNull('users.supervisor_id');
                    break;
            }

            return DataTables::of($query)
                ->addColumn('supervisor_name', function ($user) {
                    if ($user->supervisor) {
                        return e($user->supervisor->name);
                    }
                    return '<span class="badge bg-warning text-dark">Independent</span>';
                })
                ->addColumn('status_badge', function ($user) {
                    $class = $user->status === 'active' ? 'success' : 'secondary';
                    return '<span class="badge bg-' . $class . '">' . ucfirst($user->status) . '</span>';
                })
                ->addColumn('coverage', function ($user) {
                    $states = $user->coverage_states;
                    if (is_string($states)) {
                        $states = json_decode($states, true);
                    }
                    if (is_array($states) && count($states) > 0) {
                        return e(implode(', ', array_slice($states, 0, 3)));
                    }
                    return '-';
                })
                ->addColumn('actions', function ($user) {
                    $html = '<div class="btn-group btn-group-sm">';
                    $html .= '<a href="' . route('admin.teams.show', $user->id) . '" class="btn btn-info" title="View"><i class="bi bi-eye"></i></a>';
                    $html .= '<button class="btn btn-primary reassign-btn" data-id="' . $user->id . '" data-name="' . e($user->name) . '" data-supervisor="' . ($user->supervisor_id ?? '') . '" title="Reassign"><i class="bi bi-arrow-left-right"></i></button>';
                    if ($user->supervisor_id) {
                        $html .= '<button class="btn btn-warning remove-btn" data-id="' . $user->id . '" data-name="' . e($user->name) . '" title="Remove"><i class="bi bi-person-dash"></i></button>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->filter(function ($query) use ($request) {
                    if ($search = $request->input('search.value')) {
                        $query->where(function ($q) use ($search) {
                            $q->where('users.name', 'like', "%{$search}%")
                              ->orWhere('users.employee_id', 'like', "%{$search}%");
                        });
                    }
                    if ($request->filled('supervisor_id')) {
                        if ($request->supervisor_id === 'independent') {
                            $query->whereNull('users.supervisor_id');
                        } else {
                            $query->where('users.supervisor_id', $request->supervisor_id);
                        }
                    }
                    if ($request->filled('status')) {
                        $query->where('users.status', $request->status);
                    }
                })
                ->orderColumn('employee_id', fn($query, $order) => $query->orderBy('users.employee_id', $order))
                ->orderColumn('name', fn($query, $order) => $query->orderBy('users.name', $order))
                ->orderColumn('status', fn($query, $order) => $query->orderBy('users.status', $order))
                ->rawColumns(['supervisor_name', 'status_badge', 'actions'])
                ->make(true);

        } catch (\Exception $e) {
            \Log::error('Team DataTable Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Return valid DataTable JSON so no browser alert popup
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    /**
     * Get supervisors datatable.
     */
    protected function supervisorsDatatable(Request $request): JsonResponse
    {
        $query = User::whereHas('roles', function ($q) {
                $q->where('roles.name', 'supervisor');
            })
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')])
            ->select('users.*');

        return DataTables::of($query)
            ->addColumn('team_count', function ($user) {
                return '<span class="badge bg-primary">' . ($user->technicians_count ?? 0) . ' members</span>';
            })
            ->addColumn('status_badge', function ($user) {
                $class = $user->status === 'active' ? 'success' : 'secondary';
                return '<span class="badge bg-' . $class . '">' . ucfirst($user->status) . '</span>';
            })
            ->addColumn('coverage', function ($user) {
                $states = $user->coverage_states;
                if (is_string($states)) {
                    $states = json_decode($states, true);
                }
                if (is_array($states) && count($states) > 0) {
                    return e(implode(', ', array_slice($states, 0, 3)));
                }
                return '-';
            })
            ->addColumn('actions', function ($user) {
                $html = '<div class="btn-group btn-group-sm">';
                $html .= '<a href="' . route('admin.teams.index') . '?supervisor=' . $user->id . '" class="btn btn-info" title="View Team"><i class="bi bi-people"></i></a>';
                $html .= '<a href="' . route('admin.users.show', $user->id) . '" class="btn btn-secondary" title="Profile"><i class="bi bi-person"></i></a>';
                $html .= '</div>';
                return $html;
            })
            ->filter(function ($query) use ($request) {
                if ($search = $request->input('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('users.name', 'like', "%{$search}%")
                          ->orWhere('users.employee_id', 'like', "%{$search}%");
                    });
                }
                if ($request->filled('status')) {
                    $query->where('users.status', $request->status);
                }
            })
            ->orderColumn('employee_id', fn($query, $order) => $query->orderBy('users.employee_id', $order))
            ->orderColumn('name', fn($query, $order) => $query->orderBy('users.name', $order))
            ->rawColumns(['team_count', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show team member details.
     */
    public function show(User $user): View|JsonResponse
    {
        $user->load(['supervisor:id,name', 'roles']);
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

        $supervisors = User::role('supervisor')->where('status', 'active')->orderBy('name')->get();
        return view('admin.teams.show', compact('user', 'statistics', 'recentJobs', 'chartData', 'assignmentHistory', 'supervisors'));
    }

    /**
     * Assign single technician to supervisor.
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

            $message = $request->supervisor_id
                ? "Assigned to " . User::find($request->supervisor_id)->name
                : "Now independent";

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk assign technicians to supervisor.
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

            $message = $request->supervisor_id
                ? "{$count} technician(s) assigned to " . User::find($request->supervisor_id)->name
                : "{$count} technician(s) now independent";

            return response()->json(['success' => true, 'message' => $message, 'count' => $count]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove technician from team.
     */
    public function remove(User $user): JsonResponse
    {
        try {
            if (!$user->hasRole('technician')) {
                return response()->json(['success' => false, 'message' => 'Not a technician'], 422);
            }

            $oldSupervisorId = $user->supervisor_id;

            DB::beginTransaction();
            $user->update(['supervisor_id' => null]);
            $this->teamService->logTeamChange($user, $oldSupervisorId, null, Auth::user());
            DB::commit();

            return response()->json(['success' => true, 'message' => "{$user->name} is now independent"]);
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
        $query = User::role('supervisor')
            ->where('status', 'active')
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')]);

        if ($search = $request->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%"));
        }

        return response()->json(['success' => true, 'supervisors' => $query->orderBy('name')->limit(50)->get()]);
    }

    /**
     * Get independent technicians list.
     */
    public function independentList(Request $request): JsonResponse
    {
        $query = User::role('technician')->whereNull('supervisor_id')->where('status', 'active');

        if ($search = $request->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%"));
        }

        return response()->json(['success' => true, 'technicians' => $query->orderBy('name')->limit(50)->get()]);
    }
}
