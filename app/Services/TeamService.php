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
 * NOTE: Independent technicians are NOT supported.
 *       All technicians MUST have a supervisor assigned.
 *       Internal supervisors have technician teams.
 *       External supervisors do NOT have technician teams.
 *
 * CHANGED: Removed all coverage_states references — field no longer exists.
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
        $totalSupervisors = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))->where('status', 'active')->count();
        $internalSupervisors = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('supervisor_type', 'internal')->where('status', 'active')->count();
        $externalSupervisors = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('supervisor_type', 'external')->where('status', 'active')->count();

        $totalTechnicians = User::whereHas('roles', fn($q) => $q->where('roles.name', 'technician'))->where('status', 'active')->count();
        $assignedTechnicians = User::whereHas('roles', fn($q) => $q->where('roles.name', 'technician'))->whereNotNull('supervisor_id')->where('status', 'active')->count();
        $unassignedTechnicians = User::whereHas('roles', fn($q) => $q->where('roles.name', 'technician'))->whereNull('supervisor_id')->where('status', 'active')->count();

        // Average team size based on internal supervisors only (external don't have teams)
        $avgTeamSize = $internalSupervisors > 0 ? round($assignedTechnicians / $internalSupervisors, 1) : 0;

        $largestTeam = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('supervisor_type', 'internal')
            ->withCount(['technicians' => fn($q) => $q->where('status', 'active')])
            ->orderByDesc('technicians_count')
            ->first();

        return [
            'total_supervisors'      => $totalSupervisors,
            'internal_supervisors'   => $internalSupervisors,
            'external_supervisors'   => $externalSupervisors,
            'total_technicians'      => $totalTechnicians,
            'assigned_technicians'   => $assignedTechnicians,
            'unassigned_technicians' => $unassignedTechnicians,
            'avg_team_size'          => $avgTeamSize,
            'largest_team'           => [
                'supervisor_name' => $largestTeam?->name ?? '-',
                'team_size'       => $largestTeam?->technicians_count ?? 0,
            ],
        ];
    }

    /**
     * Get supervisor's own team statistics.
     * Used by: Admin\TeamController::stats(), Supervisor\TeamController::index() & stats()
     *
     * For external supervisors: returns zero team members and own job stats only.
     *
     * CHANGED: Removed coverage_states collection/stats — field no longer exists.
     */
    public function getSupervisorTeamStats(User $supervisor): array
    {
        $isExternal = $supervisor->isExternalSupervisor();

        // External supervisors have no team members
        if ($isExternal) {
            return $this->getExternalSupervisorStats($supervisor);
        }

        // Internal supervisor — full team stats
        $allTeamMembers = User::where('supervisor_id', $supervisor->id)->get();
        $activeMembers = $allTeamMembers->where('status', 'active');

        $totalMembers = $allTeamMembers->count();
        $activeCount = $activeMembers->count();

        // REMOVED: coverage_states collection — field no longer exists

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
            'supervisor_type'       => 'internal',
            'total_members'         => $totalMembers,
            'active_members'        => $activeCount,
            'todays_jobs'           => $todaysJobs,
            'pending_jobs'          => $pendingJobs,
            'completed_this_month'  => $completedThisMonth,
            'sla_compliance'        => [
                'rate'    => $slaRate,
                'on_time' => $slaOnTime,
                'total'   => $slaTotal,
            ],
            // REMOVED: 'coverage_states' key — field no longer exists
            // REMOVED: 'members_with_coverage' key — field no longer exists
            'total_jobs'            => ($todaysJobs + $pendingJobs + $completedThisMonth),
            'completed_jobs'        => $completedThisMonth,
            'completion_rate'       => $slaRate,
        ];
    }

    /**
     * Get stats for an external supervisor (no team, own jobs only).
     * CHANGED: Removed coverage_states collection — field no longer exists.
     */
    protected function getExternalSupervisorStats(User $supervisor): array
    {
        $todaysJobs = 0;
        $pendingJobs = 0;
        $completedThisMonth = 0;
        $slaRate = 100;
        $slaOnTime = 0;
        $slaTotal = 0;

        try {
            if (class_exists(JobOrder::class)) {
                // External supervisor jobs: supervisor_id = self OR technician_id = self
                $jobScope = fn($q) => $q->where('supervisor_id', $supervisor->id)
                    ->orWhere('technician_id', $supervisor->id);

                $todaysJobs = JobOrder::where($jobScope)
                    ->whereDate('job_date', today())
                    ->count();

                $pendingJobs = JobOrder::where($jobScope)
                    ->whereIn('status', ['pending_assignment', 'assigned', 'in_progress'])
                    ->count();

                $completedThisMonth = JobOrder::where($jobScope)
                    ->where('status', 'completed')
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)
                    ->count();

                $slaTotal = JobOrder::where($jobScope)
                    ->where('status', 'completed')
                    ->count();

                if ($slaTotal > 0) {
                    $slaOnTime = JobOrder::where($jobScope)
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

        // REMOVED: coverage_states collection — field no longer exists

        return [
            'supervisor_type'       => 'external',
            'total_members'         => 0,
            'active_members'        => 0,
            'todays_jobs'           => $todaysJobs,
            'pending_jobs'          => $pendingJobs,
            'completed_this_month'  => $completedThisMonth,
            'sla_compliance'        => [
                'rate'    => $slaRate,
                'on_time' => $slaOnTime,
                'total'   => $slaTotal,
            ],
            // REMOVED: 'coverage_states' key — field no longer exists
            // REMOVED: 'members_with_coverage' key — field no longer exists
            'total_jobs'            => ($todaysJobs + $pendingJobs + $completedThisMonth),
            'completed_jobs'        => $completedThisMonth,
            'completion_rate'       => $slaRate,
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
                    if ($user->isInternalSupervisor()) {
                        $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->push($user->id);
                        $jobQuery->whereIn('technician_id', $teamIds);
                    } else {
                        $jobQuery->where(function ($q) use ($user) {
                            $q->where('supervisor_id', $user->id)
                              ->orWhere('technician_id', $user->id);
                        });
                    }
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
                if ($user->isInternalSupervisor()) {
                    $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->push($user->id);
                    $query->whereIn('technician_id', $teamIds);
                } else {
                    $query->where(function ($q) use ($user) {
                        $q->where('supervisor_id', $user->id)
                          ->orWhere('technician_id', $user->id);
                    });
                }
            }

            return $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($job) {
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
                        if ($user->isInternalSupervisor()) {
                            $teamIds = User::where('supervisor_id', $user->id)->pluck('id');
                            $query->whereIn('technician_id', $teamIds);
                        } else {
                            $query->where(function ($q) use ($user) {
                                $q->where('supervisor_id', $user->id)
                                  ->orWhere('technician_id', $user->id);
                            });
                        }
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
     */
    public function logTeamChange(User $technician, ?int $oldSupervisorId, ?int $newSupervisorId, User $performer): void
    {
        $oldSupervisor = $oldSupervisorId ? User::find($oldSupervisorId)?->name : 'Unassigned';
        $newSupervisor = $newSupervisorId ? User::find($newSupervisorId)?->name : 'Unassigned';

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
            \Log::warning('Team change logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Get team performance data for supervisor dashboard chart.
     * For internal supervisors: uses team technician IDs.
     * For external supervisors: uses own supervisor_id scoped jobs.
     */
    public function getTeamPerformance(User $supervisor): array
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

                    if ($supervisor->isInternalSupervisor()) {
                        $teamIds = User::where('supervisor_id', $supervisor->id)->pluck('id');
                        $query->whereIn('technician_id', $teamIds);
                    } else {
                        // External supervisor: own jobs only
                        $query->where(function ($q) use ($supervisor) {
                            $q->where('supervisor_id', $supervisor->id)
                              ->orWhere('technician_id', $supervisor->id);
                        });
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

        return [
            'jobs_this_week' => [
                'labels' => $labels,
                'data'   => $data,
            ],
        ];
    }
}
