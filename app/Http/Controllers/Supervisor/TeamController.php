<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Supervisor TeamController
 * 
 * Handles team viewing for Supervisor users (READ-ONLY):
 * - View own team members only
 * - View team statistics
 * - View member performance
 * 
 * @package App\Http\Controllers\Supervisor
 */
class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
        $this->middleware('role:supervisor');
    }

    /**
     * Display supervisor's own team dashboard.
     */
    public function index(): View
    {
        $currentUser = Auth::user();

        $teamMembers = User::where('supervisor_id', $currentUser->id)
            ->with(['roles'])
            ->orderBy('name')
            ->get();

        $teamStats = $this->teamService->getSupervisorTeamStats($currentUser);
        $teamPerformance = $this->teamService->getTeamPerformance($currentUser);

        return view('supervisor.teams.index', compact('teamMembers', 'teamStats', 'teamPerformance'));
    }

    /**
     * Get own team datatable.
     */
    public function datatable(Request $request): JsonResponse
    {
        $currentUser = Auth::user();

        $query = User::where('supervisor_id', $currentUser->id)->select('users.*');

        return DataTables::of($query)
            ->addColumn('status_badge', fn($user) => '<span class="badge bg-' . ($user->status == 'active' ? 'success' : 'secondary') . '">' . ucfirst($user->status) . '</span>')
            ->addColumn('coverage', fn($user) => $user->coverage_states ? implode(', ', $user->coverage_states) : '-')
            ->addColumn('skills', fn($user) => $user->skill_tags ? implode(', ', array_slice($user->skill_tags, 0, 3)) : '-')
            ->addColumn('actions', fn($user) => '<a href="' . route('supervisor.teams.show', $user->id) . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>')
            ->filter(function($query) use ($request) {
                if ($search = $request->search['value'] ?? null) {
                    $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%"));
                }
                if ($request->status) {
                    $query->where('status', $request->status);
                }
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show team member details (own team only).
     */
    public function show(User $user): View|JsonResponse
    {
        $currentUser = Auth::user();

        // Authorization - only own team members
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
     * Get own team statistics.
     */
    public function stats(): JsonResponse
    {
        $currentUser = Auth::user();
        return response()->json(['success' => true, 'statistics' => $this->teamService->getSupervisorTeamStats($currentUser)]);
    }

    /**
     * Get team performance chart data.
     */
    public function performance(): JsonResponse
    {
        $currentUser = Auth::user();
        return response()->json(['success' => true, 'performance' => $this->teamService->getTeamPerformance($currentUser)]);
    }
}
