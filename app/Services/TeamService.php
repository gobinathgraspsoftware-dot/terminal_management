<?php

namespace App\Services;

use App\Models\User;
use App\Models\JobOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * TeamService
 *
 * Provides team-related business logic for Admin & Supervisor TeamControllers.
 *
 * Methods called by Admin\TeamController:
 *   - getTeamStatistics()
 *   - getMemberStatistics(User)
 *   - getMemberRecentJobs(User)
 *   - getMemberWeeklyPerformance(User)
 *   - getAssignmentHistory(User)
 *   - logTeamChange(User, ?int, ?int, User)
 *   - getSupervisorTeamStats(User)
 *
 * Methods called by Supervisor\TeamController:
 *   - getSupervisorTeamStats(User)
 *   - getTeamPerformance(User)
 *   - getMemberStatistics(User)
 *   - getMemberRecentJobs(User)
 *   - getMemberWeeklyPerformance(User)
 *
 * @package App\Services
 */
class TeamService
{
    /**
     * Get overall team statistics for admin dashboard.
     * Used by: Admin\TeamController::index()
     */
    public function getTeamStatistics(): array
    {
        $totalSupervisors = User::role('supervisor')->where('status', 'active')->count();
        $totalTechnicians = User::role('technician')->where('status', 'active')->count();
        $assignedTechnicians = User::role('technician')->whereNotNull('supervisor_id')->where('status', 'active')->count();
        $independentTechnicians = User::role('technician')->whereNull('supervisor_id')->where('status', 'active')->count();

        $avgTeamSize = $totalSupervisors > 0 ? round($assignedTechnicians / $totalSupervisors, 1) : 0;

        $largestTeam = User::role('supervisor')
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')])
            ->orderByDesc('technicians_count')
            ->first();

        return [
            'total_supervisors' => $totalSupervisors,
            'total_technicians' => $totalTechnicians,
            'assigned_technicians' => $assignedTechnicians,
            'independent_technicians' => $independentTechnicians,
            'avg_team_size' => $avgTeamSize,
            'largest_team' => [
                'supervisor_name' => $largestTeam?->name ?? '-',
                'team_size' => $largestTeam?->technicians_count ?? 0,
            ],
        ];
    }

    /**
     * Get supervisor's own team statistics.
     * Used by: Admin\TeamController::stats(), Supervisor\TeamController::index() & stats()
     *
     * Returns keys matching supervisor/teams/index.blade.php:
     *   total_members, active_members, todays_jobs, pending_jobs,
     *   completed_this_month, sla_compliance, coverage_states
     */
    public function getSupervisorTeamStats(User $supervisor): array
    {
        $allTeamMembers = User::where('supervisor_id', $supervisor->id)->get();
        $activeMembers = $allTeamMembers->where('status', 'active');

        $totalMembers = $allTeamMembers->count();
        $activeCount = $activeMembers->count();

        // Collect unique coverage states across all active members
        $coverageStates = $activeMembers
            ->pluck('coverage_states')
            ->filter()
            ->map(function ($val) {
                if (is_array($val)) return $val;
                if (is_string($val)) {
                    $decoded = json_decode($val, true);
                    return is_array($decoded) ? $decoded : [];
                }
                return [];
            })
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $teamIds = $allTeamMembers->pluck('id');

        $todaysJobs = 0;
        $pendingJobs = 0;
        $completedThisMonth = 0;
        $slaRate = 100;
        $slaOnTime = 0;
        $slaTotal = 0;

        try {
            if (class_exists(JobOrder::class)) {
                $todaysJobs = JobOrder::whereIn('technician_id', $teamIds)
                    ->whereDate('job_date', today())
                    ->count();

                $pendingJobs = JobOrder::whereIn('technician_id', $teamIds)
                    ->whereIn('status', ['pending_assignment', 'assigned', 'in_progress'])
                    ->count();

                $completedThisMonth = JobOrder::whereIn('technician_id', $teamIds)
                    ->where('status', 'completed')
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)
                    ->count();

                // SLA compliance
                $slaTotal = JobOrder::whereIn('technician_id', $teamIds)
                    ->where('status', 'completed')
                    ->count();

                if ($slaTotal > 0) {
                    $slaOnTime = JobOrder::whereIn('technician_id', $teamIds)
                        ->where('status', 'completed')
                        ->where(function ($q) {
                            $q->whereNull('sla_deadline')
                              ->orWhereColumn('completed_at', '<=', 'sla_deadline');
                        })
                        ->count();
                    $slaRate = round(($slaOnTime / $slaTotal) * 100, 1);
                }
            }
        } catch (\Exception $e) {
            // job_orders table may not exist yet
        }

        return [
            'total_members'       => $totalMembers,
            'active_members'      => $activeCount,
            'todays_jobs'         => $todaysJobs,
            'pending_jobs'        => $pendingJobs,
            'completed_this_month' => $completedThisMonth,
            'sla_compliance'      => [
                'rate'    => $slaRate,
                'on_time' => $slaOnTime,
                'total'   => $slaTotal,
            ],
            'coverage_states'     => $coverageStates,
            // Backward-compatible keys used by Admin\TeamController::stats()
            'members_with_coverage' => $activeMembers->filter(fn($m) => !empty($m->coverage_states))->count(),
            'total_jobs'          => ($todaysJobs + $pendingJobs + $completedThisMonth),
            'completed_jobs'      => $completedThisMonth,
            'completion_rate'     => $slaRate,
        ];
    }

    /**
     * Get statistics for a specific team member.
     * Used by: Admin\TeamController::show(), Supervisor\TeamController::show()
     */
    public function getMemberStatistics(User $user): array
    {
        $totalJobs = 0;
        $completedJobs = 0;
        $pendingJobs = 0;
        $completedThisMonth = 0;
        $slaRate = 100;
        $slaOnTime = 0;
        $slaTotal = 0;
        $commissionThisMonth = 0;

        try {
            if (class_exists(JobOrder::class)) {
                $jobQuery = JobOrder::query();

                if ($user->hasRole('technician')) {
                    $jobQuery->where('technician_id', $user->id);
                } elseif ($user->hasRole('supervisor')) {
                    $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->push($user->id);
                    $jobQuery->whereIn('technician_id', $teamIds);
                }

                $totalJobs = (clone $jobQuery)->count();
                $completedJobs = (clone $jobQuery)->where('status', 'completed')->count();
                $pendingJobs = (clone $jobQuery)->whereIn('status', ['pending_assignment', 'assigned', 'in_progress'])->count();
                $completedThisMonth = (clone $jobQuery)->where('status', 'completed')
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)
                    ->count();

                // SLA compliance
                $slaTotal = (clone $jobQuery)->where('status', 'completed')->count();
                if ($slaTotal > 0) {
                    $slaOnTime = (clone $jobQuery)->where('status', 'completed')
                        ->where(function ($q) {
                            $q->whereNull('sla_deadline')
                              ->orWhereColumn('completed_at', '<=', 'sla_deadline');
                        })->count();
                    $slaRate = round(($slaOnTime / $slaTotal) * 100, 1);
                }
            }
        } catch (\Exception $e) {
            // Tables may not exist yet
        }

        return [
            'total_jobs' => $totalJobs,
            'completed_jobs' => $completedJobs,
            'pending_jobs' => $pendingJobs,
            'completed_this_month' => $completedThisMonth,
            'commission_this_month' => number_format($commissionThisMonth, 2),
            'sla_compliance' => [
                'rate' => $slaRate,
                'on_time' => $slaOnTime,
                'total' => $slaTotal,
            ],
        ];
    }

    /**
     * Get recent jobs for a team member.
     * Used by: Admin\TeamController::show(), Supervisor\TeamController::show()
     */
    public function getMemberRecentJobs(User $user, int $limit = 10)
    {
        try {
            if (!class_exists(JobOrder::class)) {
                return collect();
            }

            $query = JobOrder::query();

            if ($user->hasRole('technician')) {
                $query->where('technician_id', $user->id);
            } elseif ($user->hasRole('supervisor')) {
                $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->push($user->id);
                $query->whereIn('technician_id', $teamIds);
            }

            return $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($job) {
                    // Normalize field names for the view
                    $job->job_number = $job->job_no ?? $job->job_number ?? '-';
                    $job->client_name = $job->client?->client_name ?? '-';
                    $job->scheduled_date = $job->job_date ?? $job->created_at;
                    return $job;
                });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get weekly performance chart data for a member.
     * Used by: Admin\TeamController::show(), Supervisor\TeamController::show()
     */
    public function getMemberWeeklyPerformance(User $user): array
    {
        $labels = [];
        $data = [];

        try {
            if (class_exists(JobOrder::class)) {
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $labels[] = $date->format('D');

                    $query = JobOrder::where('status', 'completed')
                        ->whereDate('updated_at', $date);

                    if ($user->hasRole('technician')) {
                        $query->where('technician_id', $user->id);
                    } elseif ($user->hasRole('supervisor')) {
                        $teamIds = User::where('supervisor_id', $user->id)->pluck('id');
                        $query->whereIn('technician_id', $teamIds);
                    }

                    $data[] = $query->count();
                }
            } else {
                throw new \Exception('No JobOrder model');
            }
        } catch (\Exception $e) {
            $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $data = [0, 0, 0, 0, 0, 0, 0];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Get assignment history for a user from activity_log table.
     * Used by: Admin\TeamController::show()
     */
    public function getAssignmentHistory(User $user, int $limit = 10): array
    {
        try {
            return DB::table('activity_log')
                ->where('subject_type', User::class)
                ->where('subject_id', $user->id)
                ->where('description', 'like', '%Team assignment%')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($log) {
                    $properties = json_decode($log->properties, true);
                    return [
                        'date' => Carbon::parse($log->created_at)->format('d M Y H:i'),
                        'from' => $properties['old_supervisor_name'] ?? 'Unknown',
                        'to' => $properties['new_supervisor_name'] ?? 'Unknown',
                        'changed_by' => $properties['changed_by'] ?? '-',
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Log a team change using Spatie Activity Log.
     * Used by: Admin\TeamController::assign(), bulkAssign(), remove()
     */
    public function logTeamChange(User $technician, ?int $oldSupervisorId, ?int $newSupervisorId, User $performer): void
    {
        $oldSupervisor = $oldSupervisorId ? User::find($oldSupervisorId)?->name : 'Independent';
        $newSupervisor = $newSupervisorId ? User::find($newSupervisorId)?->name : 'Independent';

        try {
            activity('team')
                ->causedBy($performer)
                ->performedOn($technician)
                ->withProperties([
                    'old_supervisor_id' => $oldSupervisorId,
                    'new_supervisor_id' => $newSupervisorId,
                    'old_supervisor_name' => $oldSupervisor,
                    'new_supervisor_name' => $newSupervisor,
                    'changed_by' => $performer->name,
                ])
                ->log("Team assignment changed for {$technician->name}: from {$oldSupervisor} to {$newSupervisor}");
        } catch (\Exception $e) {
            // Activity log package may not be installed
            \Log::warning('Team change logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Get team performance data for supervisor dashboard chart.
     * Used by: Supervisor\TeamController::index() & performance()
     *
     * Returns nested under 'jobs_this_week' key to match view expectation:
     *   $teamPerformance['jobs_this_week']['labels'] / ['data']
     */
    public function getTeamPerformance(User $supervisor): array
    {
        $teamIds = User::where('supervisor_id', $supervisor->id)->pluck('id');
        $labels = [];
        $data = [];

        try {
            if (class_exists(JobOrder::class)) {
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $labels[] = $date->format('D');
                    $data[] = JobOrder::whereIn('technician_id', $teamIds)
                        ->where('status', 'completed')
                        ->whereDate('updated_at', $date)
                        ->count();
                }
            } else {
                throw new \Exception('No JobOrder model');
            }
        } catch (\Exception $e) {
            $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $data = [0, 0, 0, 0, 0, 0, 0];
        }

        return [
            'jobs_this_week' => [
                'labels' => $labels,
                'data'   => $data,
            ],
        ];
    }
}
