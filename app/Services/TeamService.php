<?php

namespace App\Services;

use App\Models\User;
use App\Models\JobOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * TeamService
 * 
 * Handles business logic for team operations including:
 * - Team statistics calculation
 * - Performance metrics
 * - Team analytics
 * 
 * @package App\Services
 */
class TeamService
{
    /**
     * Get comprehensive statistics for a supervisor's team
     * 
     * @param int $supervisorId
     * @return array
     */
    public function getTeamStatistics(int $supervisorId): array
    {
        $supervisor = User::findOrFail($supervisorId);
        
        // Get team members
        $teamMembers = User::where('supervisor_id', $supervisorId)
            ->withCount([
                'jobOrders as total_jobs',
                'jobOrders as active_jobs' => function ($query) {
                    $query->whereIn('status', ['pending', 'in_progress']);
                },
                'jobOrders as completed_jobs' => function ($query) {
                    $query->where('status', 'completed');
                }
            ])
            ->get();

        // Calculate aggregate statistics
        $stats = [
            'supervisor' => [
                'id' => $supervisor->id,
                'name' => $supervisor->name,
                'email' => $supervisor->email,
            ],
            'team_size' => $teamMembers->count(),
            'active_members' => $teamMembers->where('is_active', true)->count(),
            'inactive_members' => $teamMembers->where('is_active', false)->count(),
            'job_statistics' => [
                'total_jobs' => $teamMembers->sum('total_jobs'),
                'active_jobs' => $teamMembers->sum('active_jobs'),
                'completed_jobs' => $teamMembers->sum('completed_jobs'),
                'average_jobs_per_technician' => $teamMembers->count() > 0 
                    ? round($teamMembers->sum('total_jobs') / $teamMembers->count(), 2) 
                    : 0,
            ],
            'performance_metrics' => $this->calculateTeamPerformanceMetrics($teamMembers),
            'monthly_statistics' => $this->getMonthlyStatistics($supervisorId),
        ];

        return $stats;
    }

    /**
     * Calculate performance metrics for a team
     * 
     * @param Collection $teamMembers
     * @return array
     */
    protected function calculateTeamPerformanceMetrics(Collection $teamMembers): array
    {
        $totalCompleted = $teamMembers->sum('completed_jobs');
        $totalJobs = $teamMembers->sum('total_jobs');

        return [
            'completion_rate' => $totalJobs > 0 
                ? round(($totalCompleted / $totalJobs) * 100, 2) 
                : 0,
            'active_workload' => $teamMembers->sum('active_jobs'),
            'technicians_with_work' => $teamMembers->where('active_jobs', '>', 0)->count(),
            'technicians_available' => $teamMembers->where('active_jobs', 0)->where('is_active', true)->count(),
        ];
    }

    /**
     * Get monthly statistics for a supervisor's team
     * 
     * @param int $supervisorId
     * @return array
     */
    protected function getMonthlyStatistics(int $supervisorId): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthlyJobs = JobOrder::whereHas('assignedTo', function ($query) use ($supervisorId) {
                $query->where('supervisor_id', $supervisorId);
            })
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        return [
            'period' => now()->format('F Y'),
            'total_jobs' => $monthlyJobs->count(),
            'completed_jobs' => $monthlyJobs->where('status', 'completed')->count(),
            'pending_jobs' => $monthlyJobs->where('status', 'pending')->count(),
            'in_progress_jobs' => $monthlyJobs->where('status', 'in_progress')->count(),
        ];
    }

    /**
     * Calculate completion rate for a specific technician
     * 
     * @param int $technicianId
     * @return float
     */
    public function calculateCompletionRate(int $technicianId): float
    {
        $totalJobs = JobOrder::where('assigned_to', $technicianId)->count();
        
        if ($totalJobs === 0) {
            return 0.0;
        }

        $completedJobs = JobOrder::where('assigned_to', $technicianId)
            ->where('status', 'completed')
            ->count();

        return round(($completedJobs / $totalJobs) * 100, 2);
    }

    /**
     * Get workload distribution across team
     * 
     * @param int $supervisorId
     * @return array
     */
    public function getWorkloadDistribution(int $supervisorId): array
    {
        $technicians = User::where('supervisor_id', $supervisorId)
            ->withCount([
                'jobOrders as active_jobs' => function ($query) {
                    $query->whereIn('status', ['pending', 'in_progress']);
                }
            ])
            ->get()
            ->map(function ($tech) {
                return [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'active_jobs' => $tech->active_jobs,
                    'status' => $tech->is_active ? 'Active' : 'Inactive',
                ];
            })
            ->sortByDesc('active_jobs')
            ->values();

        return [
            'distribution' => $technicians->toArray(),
            'max_workload' => $technicians->max('active_jobs') ?? 0,
            'min_workload' => $technicians->min('active_jobs') ?? 0,
            'average_workload' => $technicians->avg('active_jobs') ?? 0,
        ];
    }

    /**
     * Get team performance comparison
     * 
     * @return array
     */
    public function getTeamPerformanceComparison(): array
    {
        $supervisors = User::role('supervisor')
            ->with(['technicians' => function ($query) {
                $query->withCount([
                    'jobOrders as total_jobs',
                    'jobOrders as completed_jobs' => function ($q) {
                        $q->where('status', 'completed');
                    }
                ]);
            }])
            ->get();

        $comparison = $supervisors->map(function ($supervisor) {
            $teamMembers = $supervisor->technicians;
            $totalJobs = $teamMembers->sum('total_jobs');
            $completedJobs = $teamMembers->sum('completed_jobs');

            return [
                'supervisor_id' => $supervisor->id,
                'supervisor_name' => $supervisor->name,
                'team_size' => $teamMembers->count(),
                'total_jobs' => $totalJobs,
                'completed_jobs' => $completedJobs,
                'completion_rate' => $totalJobs > 0 
                    ? round(($completedJobs / $totalJobs) * 100, 2) 
                    : 0,
                'jobs_per_technician' => $teamMembers->count() > 0 
                    ? round($totalJobs / $teamMembers->count(), 2) 
                    : 0,
            ];
        })->sortByDesc('completion_rate')->values();

        return $comparison->toArray();
    }

    /**
     * Get technicians available for assignment
     * 
     * @param int|null $currentSupervisorId Exclude technicians from this supervisor
     * @return Collection
     */
    public function getAvailableTechnicians(?int $currentSupervisorId = null): Collection
    {
        $query = User::role('technician')
            ->select('id', 'name', 'email', 'supervisor_id', 'is_active')
            ->with('supervisor:id,name');

        if ($currentSupervisorId) {
            $query->where('supervisor_id', '!=', $currentSupervisorId)
                  ->orWhereNull('supervisor_id');
        }

        return $query->get();
    }

    /**
     * Check if technician can be reassigned
     * 
     * @param int $technicianId
     * @return array
     */
    public function canReassignTechnician(int $technicianId): array
    {
        $technician = User::findOrFail($technicianId);

        // Check for active jobs
        $activeJobs = JobOrder::where('assigned_to', $technicianId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        return [
            'can_reassign' => true, // Can always reassign, but with warnings
            'active_jobs' => $activeJobs,
            'has_active_jobs' => $activeJobs > 0,
            'warning' => $activeJobs > 0 
                ? "Technician has {$activeJobs} active job(s). Consider completing them first." 
                : null,
        ];
    }

    /**
     * Get team activity summary for dashboard
     * 
     * @param int $supervisorId
     * @param int $days
     * @return array
     */
    public function getTeamActivitySummary(int $supervisorId, int $days = 7): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $activity = JobOrder::whereHas('assignedTo', function ($query) use ($supervisorId) {
                $query->where('supervisor_id', $supervisorId);
            })
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        // Format for chart display
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->format('Y-m-d');
        }

        $formattedActivity = [];
        foreach ($dates as $date) {
            $dayActivity = $activity->where('date', $date);
            $formattedActivity[$date] = [
                'date' => $date,
                'completed' => $dayActivity->where('status', 'completed')->sum('count'),
                'in_progress' => $dayActivity->where('status', 'in_progress')->sum('count'),
                'pending' => $dayActivity->where('status', 'pending')->sum('count'),
            ];
        }

        return [
            'period' => "{$days} days",
            'activity' => array_values($formattedActivity),
        ];
    }
}
