<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Supervisor TeamController — READ-ONLY team view.
 *
 * Internal supervisors: Full team view with DataTable + stats.
 * External supervisors: Own stats only (no technician team).
 */
class TeamController extends Controller implements HasMiddleware
{
    protected TeamService $teamService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:supervisor'),
        ];
    }

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * My Team — single consolidated page.
     * Internal: shows team DataTable + team stats.
     * External: shows own stats + info message (no team).
     */
    public function index(): View
    {
        $currentUser = Auth::user();
        $teamStats = $this->teamService->getSupervisorTeamStats($currentUser);
        $teamPerformance = $this->teamService->getTeamPerformance($currentUser);

        $isInternal = $currentUser->isInternalSupervisor();
        $isExternal = $currentUser->isExternalSupervisor();

        return view('supervisor.teams.index', compact(
            'teamStats',
            'teamPerformance',
            'isInternal',
            'isExternal'
        ));
    }

    /**
     * Server-side DataTable for team members.
     * Only available for INTERNAL supervisors.
     */
    public function datatable(Request $request): JsonResponse
    {
        $currentUser = Auth::user();

        // External supervisors have no team — return empty
        if ($currentUser->isExternalSupervisor()) {
            return response()->json([
                'draw' => (int) ($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $query = User::where('supervisor_id', $currentUser->id)
            ->select('users.*');

        return DataTables::of($query)
            ->addColumn('avatar', function ($user) {
                $url = $user->avatar
                    ? asset('storage/' . $user->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode(substr($user->name, 0, 1)) . '&size=36&background=random&color=fff';
                return '<img src="' . $url . '" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">';
            })
            ->addColumn('status_badge', fn($user) => '<span class="badge bg-' . match($user->status) {
                'active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger', default => 'warning'
            } . '">' . ucfirst($user->status) . '</span>')
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
            ->addColumn('skills', function ($user) {
                $tags = is_array($user->skill_tags) ? $user->skill_tags : (is_string($user->skill_tags) ? json_decode($user->skill_tags, true) : null);
                if (empty($tags) || !is_array($tags)) return '<span class="text-muted">-</span>';
                $html = '';
                foreach (array_slice($tags, 0, 2) as $t) {
                    $html .= '<span class="badge bg-info bg-opacity-10 text-info me-1">' . e($t) . '</span>';
                }
                if (count($tags) > 2) {
                    $html .= '<span class="badge bg-info bg-opacity-10 text-info">+' . (count($tags) - 2) . '</span>';
                }
                return $html;
            })
            ->addColumn('actions', fn($user) => '<a href="' . route('supervisor.teams.show', $user->id) . '" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="View Details"><i class="bi bi-eye"></i></a>')
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
            ->rawColumns(['avatar', 'status_badge', 'coverage', 'skills', 'actions'])
            ->make(true);
    }

    /**
     * Show team member details (own team only).
     * Only available for INTERNAL supervisors.
     */
    public function show(User $user): View|JsonResponse
    {
        $currentUser = Auth::user();

        // External supervisors cannot view team members (they have none)
        if ($currentUser->isExternalSupervisor()) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'External supervisors do not have team members.'], 403);
            }
            abort(403, 'External supervisors do not have team members.');
        }

        if ($user->supervisor_id !== $currentUser->id) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized');
        }

        $user->load(['roles']);
        $statistics = $this->teamService->getMemberStatistics($user);
        $recentJobs = $this->teamService->getMemberRecentJobs($user);
        $chartData = $this->teamService->getMemberWeeklyPerformance($user);

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
                ],
                'statistics' => $statistics
            ]);
        }

        return view('supervisor.teams.show', compact('user', 'statistics', 'recentJobs', 'chartData'));
    }

    /**
     * Team statistics API.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'statistics' => $this->teamService->getSupervisorTeamStats(Auth::user())
        ]);
    }
}
