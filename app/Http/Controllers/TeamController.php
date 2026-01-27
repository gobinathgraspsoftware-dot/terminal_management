<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use App\Services\TeamService;
use Illuminate\Http\Request;
use App\Http\Requests\AssignTechnicianRequest;
use App\Http\Requests\BulkAssignTechnicianRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * TeamController
 *
 * Manages team operations including:
 * - Admin team management and technician assignments
 * - Supervisor team dashboard
 * - Bulk technician assignments
 * - Independent technician handling
 *
 * @package App\Http\Controllers
 */
class TeamController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Admin's team management dashboard
     * Shows all supervisors with their teams and unassigned technicians
     *
     * Route: /admin/teams
     *
     * @return \Illuminate\View\View
     */
    public function adminIndex()
    {
        // Get all supervisors with their technicians
        $supervisors = User::role('supervisor')
            ->with(['technicians' => function ($query) {
                $query->withCount([
                    'jobOrders as active_jobs' => function ($q) {
                        $q->whereIn('status', ['pending', 'in_progress']);
                    }
                ]);
            }])
            ->withCount('technicians')
            ->get();

        // Get unassigned technicians (independent technicians)
        $unassignedTechnicians = User::role('technician')
            ->whereNull('supervisor_id')
            ->withCount([
                'jobOrders as active_jobs' => function ($query) {
                    $query->whereIn('status', ['pending', 'in_progress']);
                }
            ])
            ->get();

        // Get all technicians for assignment dropdown
        $allTechnicians = User::role('technician')
            ->select('id', 'name', 'email', 'supervisor_id')
            ->with('supervisor:id,name')
            ->get();

        // Overall statistics
        $stats = [
            'total_supervisors' => $supervisors->count(),
            'total_technicians' => User::role('technician')->count(),
            'assigned_technicians' => User::role('technician')->whereNotNull('supervisor_id')->count(),
            'unassigned_technicians' => $unassignedTechnicians->count(),
            'total_active_jobs' => $allTechnicians->sum('active_jobs')
        ];

        return view('teams.admin-index', compact(
            'supervisors',
            'unassignedTechnicians',
            'allTechnicians',
            'stats'
        ));
    }

    /**
     * Supervisor's team dashboard
     * Shows all technicians assigned to this supervisor
     *
     * Route: /supervisor/teams
     *
     * @return \Illuminate\View\View
     */
    public function supervisorDashboard()
    {
        $supervisor = auth()->user();

        // Get team members with their statistics
        $teamMembers = User::where('supervisor_id', $supervisor->id)
            ->with(['jobOrders' => function ($query) {
                $query->where('created_at', '>=', now()->subMonth());
            }])
            ->withCount([
                'jobOrders as active_jobs' => function ($query) {
                    $query->whereIn('status', ['pending', 'in_progress']);
                },
                'jobOrders as completed_jobs' => function ($query) {
                    $query->where('status', 'completed')
                          ->where('created_at', '>=', now()->subMonth());
                }
            ])
            ->get();

        // Team statistics
        $stats = [
            'total_members' => $teamMembers->count(),
            'active_members' => $teamMembers->where('is_active', true)->count(),
            'total_active_jobs' => $teamMembers->sum('active_jobs'),
            'total_completed_jobs' => $teamMembers->sum('completed_jobs'),
            'average_jobs_per_tech' => $teamMembers->count() > 0
                ? round($teamMembers->sum('active_jobs') / $teamMembers->count(), 1)
                : 0
        ];

        return view('teams.index', compact('teamMembers', 'stats', 'supervisor'));
    }

    /**
     * Show individual team member details
     *
     * Route: /admin/teams/{user} OR /supervisor/teams/{user}
     *
     * @param User $user
     * @return \Illuminate\View\View
     */
    public function show(User $user)
    {
        // Authorization check - use Gate if authorize() doesn't work
        try {
            $this->authorize('viewTeamMember', $user);
        } catch (\Exception $e) {
            // Fallback authorization using Gate
            if (!auth()->user()->can('viewTeamMember', $user)) {
                abort(403, 'Unauthorized action.');
            }
        }

        // Load relationships
        $user->load([
            'supervisor',
            'jobOrders' => function ($query) {
                $query->latest()->take(10);
            },
            'activityLogs' => function ($query) {
                $query->latest()->take(20);
            }
        ]);

        // Get user statistics
        $stats = [
            'total_jobs' => $user->jobOrders()->count(),
            'active_jobs' => $user->jobOrders()->whereIn('status', ['pending', 'in_progress'])->count(),
            'completed_jobs' => $user->jobOrders()->where('status', 'completed')->count(),
            'pending_jobs' => $user->jobOrders()->where('status', 'pending')->count(),
            'completion_rate' => $this->teamService->calculateCompletionRate($user->id)
        ];

        return view('teams.show', compact('user', 'stats'));
    }

    /**
     * Assign a technician to a supervisor
     *
     * Route: POST /admin/teams/assign
     *
     * @param AssignTechnicianRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignTechnician(AssignTechnicianRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $technician = User::findOrFail($validated['technician_id']);

            // Get old supervisor for logging
            $oldSupervisor = $technician->supervisor;

            // Assign to new supervisor (null for independent)
            $newSupervisorId = $validated['supervisor_id'] ?? null;
            $technician->supervisor_id = $newSupervisorId;
            $technician->save();

            // Get new supervisor
            $newSupervisor = $newSupervisorId ? User::find($newSupervisorId) : null;

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'team_assignment',
                'description' => $this->getAssignmentDescription($technician, $oldSupervisor, $newSupervisor),
                'model_type' => User::class,
                'model_id' => $technician->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $this->getAssignmentSuccessMessage($newSupervisor),
                'data' => [
                    'technician' => $technician->fresh(['supervisor']),
                    'old_supervisor' => $oldSupervisor ? $oldSupervisor->name : 'Independent',
                    'new_supervisor' => $newSupervisor ? $newSupervisor->name : 'Independent'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Team assignment failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign technician: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign technicians to a supervisor
     *
     * Route: POST /admin/teams/bulk-assign
     *
     * @param BulkAssignTechnicianRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAssign(BulkAssignTechnicianRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $technicianIds = $validated['technician_ids'];
            $supervisorId = $validated['supervisor_id'] ?? null;

            // Update all technicians
            User::whereIn('id', $technicianIds)->update([
                'supervisor_id' => $supervisorId,
                'updated_at' => now()
            ]);

            // Get supervisor name for message
            $supervisor = $supervisorId ? User::find($supervisorId) : null;
            $supervisorName = $supervisor ? $supervisor->name : 'Independent';

            // Log bulk activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'bulk_team_assignment',
                'description' => sprintf(
                    'Bulk assigned %d technician(s) to %s',
                    count($technicianIds),
                    $supervisorName
                ),
                'model_type' => User::class,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Successfully assigned %d technician(s) to %s',
                    count($technicianIds),
                    $supervisorName
                ),
                'data' => [
                    'count' => count($technicianIds),
                    'supervisor' => $supervisorName
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk assignment failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove technician from supervisor (make independent)
     *
     * Route: POST /admin/teams/{user}/remove
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromTeam(User $user)
    {
        try {
            // Authorization check with fallback
            try {
                $this->authorize('manageTeams', User::class);
            } catch (\Exception $e) {
                if (!auth()->user()->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action.'
                    ], 403);
                }
            }

            DB::beginTransaction();

            $oldSupervisor = $user->supervisor;

            // Remove supervisor assignment
            $user->supervisor_id = null;
            $user->save();

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'team_removal',
                'description' => sprintf(
                    'Removed technician %s from supervisor %s (now independent)',
                    $user->name,
                    $oldSupervisor ? $oldSupervisor->name : 'Unknown'
                ),
                'model_type' => User::class,
                'model_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Technician is now independent',
                'data' => [
                    'technician' => $user->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Team removal failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from team: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team statistics for a supervisor
     *
     * Route: GET /admin/teams/{user}/stats OR /supervisor/teams/{user}/stats
     *
     * @param User $supervisor
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeamStats(User $supervisor)
    {
        try {
            // Authorization check with fallback
            try {
                $this->authorize('viewTeamStats', $supervisor);
            } catch (\Exception $e) {
                // Fallback: Check if user is admin or the supervisor themselves
                if (!auth()->user()->hasRole('admin') && auth()->id() !== $supervisor->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action.'
                    ], 403);
                }
            }

            $stats = $this->teamService->getTeamStatistics($supervisor->id);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get team stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team statistics'
            ], 500);
        }
    }

    /**
     * Get assignment description for activity log
     *
     * @param User $technician
     * @param User|null $oldSupervisor
     * @param User|null $newSupervisor
     * @return string
     */
    protected function getAssignmentDescription($technician, $oldSupervisor, $newSupervisor): string
    {
        $from = $oldSupervisor ? $oldSupervisor->name : 'Independent';
        $to = $newSupervisor ? $newSupervisor->name : 'Independent';

        return sprintf(
            'Assigned technician %s from %s to %s',
            $technician->name,
            $from,
            $to
        );
    }

    /**
     * Get success message for assignment
     *
     * @param User|null $supervisor
     * @return string
     */
    protected function getAssignmentSuccessMessage($supervisor): string
    {
        if ($supervisor) {
            return sprintf('Technician successfully assigned to %s', $supervisor->name);
        }
        return 'Technician is now independent';
    }
}
