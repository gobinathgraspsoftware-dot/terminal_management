<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * TeamService
 * 
 * Business logic for team management.
 * 
 * @package App\Services
 */
class TeamService
{
    /**
     * Get overall team statistics (for Admin).
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
            'largest_team' => $largestTeam ? ['name' => $largestTeam->name, 'size' => $largestTeam->technicians_count] : null,
        ];
    }

    /**
     * Get supervisor's team statistics.
     */
    public function getSupervisorTeamStats(User $supervisor): array
    {
        $memberIds = User::where('supervisor_id', $supervisor->id)->pluck('id')->toArray();

        $teamSize = User::where('supervisor_id', $supervisor->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $todaysJobs = DB::table('job_orders')->whereIn('technician_id', $memberIds)->whereDate('scheduled_date', today())->count();
        $pendingJobs = DB::table('job_orders')->whereIn('technician_id', $memberIds)->whereIn('status', ['pending_assignment', 'assigned', 'in_progress'])->count();
        $completedThisMonth = DB::table('job_orders')->whereIn('technician_id', $memberIds)->where('status', 'completed')->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year)->count();

        $sla = $this->calculateSLA($memberIds);

        $coverageStates = User::where('supervisor_id', $supervisor->id)
            ->whereNotNull('coverage_states')
            ->get()
            ->pluck('coverage_states')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        return [
            'total_members' => array_sum($teamSize),
            'active_members' => $teamSize['active'] ?? 0,
            'inactive_members' => ($teamSize['inactive'] ?? 0) + ($teamSize['suspended'] ?? 0),
            'todays_jobs' => $todaysJobs,
            'pending_jobs' => $pendingJobs,
            'completed_this_month' => $completedThisMonth,
            'sla_compliance' => $sla,
            'coverage_states' => $coverageStates,
        ];
    }

    /**
     * Get team performance for charts.
     */
    public function getTeamPerformance(User $supervisor): array
    {
        $memberIds = User::where('supervisor_id', $supervisor->id)->pluck('id')->toArray();
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $jobs = DB::table('job_orders')
            ->whereIn('technician_id', $memberIds)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $labels = [];
        $data = [];
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $labels[] = $d->format('D');
            $data[] = $jobs[$d->format('Y-m-d')] ?? 0;
        }

        $topPerformers = DB::table('job_orders')
            ->join('users', 'job_orders.technician_id', '=', 'users.id')
            ->whereIn('technician_id', $memberIds)
            ->where('job_orders.status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->selectRaw('users.id, users.name, COUNT(*) as jobs_completed')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('jobs_completed')
            ->limit(5)
            ->get();

        return [
            'jobs_this_week' => ['labels' => $labels, 'data' => $data],
            'top_performers' => $topPerformers,
        ];
    }

    /**
     * Get member statistics.
     */
    public function getMemberStatistics(User $member): array
    {
        $totalJobs = DB::table('job_orders')->where('technician_id', $member->id)->count();
        $completedJobs = DB::table('job_orders')->where('technician_id', $member->id)->where('status', 'completed')->count();
        $pendingJobs = DB::table('job_orders')->where('technician_id', $member->id)->whereIn('status', ['pending_assignment', 'assigned', 'in_progress'])->count();
        $completedThisMonth = DB::table('job_orders')->where('technician_id', $member->id)->where('status', 'completed')->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year)->count();
        $commission = DB::table('job_orders')->where('technician_id', $member->id)->where('status', 'completed')->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year)->sum('commission_amount') ?? 0;
        $sla = $this->calculateSLA([$member->id]);

        return [
            'total_jobs' => $totalJobs,
            'completed_jobs' => $completedJobs,
            'pending_jobs' => $pendingJobs,
            'completed_this_month' => $completedThisMonth,
            'commission_this_month' => number_format($commission, 2),
            'sla_compliance' => $sla,
        ];
    }

    /**
     * Get member recent jobs.
     */
    public function getMemberRecentJobs(User $member, int $limit = 10): array
    {
        return DB::table('job_orders')
            ->leftJoin('clients', 'job_orders.client_id', '=', 'clients.id')
            ->where('job_orders.technician_id', $member->id)
            ->select('job_orders.id', 'job_orders.job_number', 'job_orders.job_type', 'job_orders.status', 'job_orders.scheduled_date', 'clients.name as client_name')
            ->orderByDesc('job_orders.created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get member weekly performance.
     */
    public function getMemberWeeklyPerformance(User $member): array
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $jobs = DB::table('job_orders')
            ->where('technician_id', $member->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $labels = [];
        $data = [];
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $labels[] = $d->format('D');
            $data[] = $jobs[$d->format('Y-m-d')] ?? 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Calculate SLA compliance.
     */
    protected function calculateSLA(array $ids): array
    {
        if (empty($ids)) return ['rate' => 0, 'on_time' => 0, 'total' => 0];

        $total = DB::table('job_orders')->whereIn('technician_id', $ids)->where('status', 'completed')->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year)->count();
        $onTime = DB::table('job_orders')->whereIn('technician_id', $ids)->where('status', 'completed')->where('sla_status', 'on_track')->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year)->count();

        return ['rate' => $total > 0 ? round(($onTime / $total) * 100, 1) : 0, 'on_time' => $onTime, 'total' => $total];
    }

    /**
     * Log team assignment change.
     */
    public function logTeamChange(User $technician, ?int $oldId, ?int $newId, User $performer): void
    {
        $oldName = $oldId ? User::find($oldId)?->name : 'Independent';
        $newName = $newId ? User::find($newId)?->name : 'Independent';

        activity()
            ->causedBy($performer)
            ->performedOn($technician)
            ->withProperties(['old_supervisor_id' => $oldId, 'new_supervisor_id' => $newId, 'old_supervisor' => $oldName, 'new_supervisor' => $newName])
            ->log("Team changed: {$technician->name} from {$oldName} to {$newName}");
    }

    /**
     * Get assignment history.
     */
    public function getAssignmentHistory(User $technician, int $limit = 10): array
    {
        return DB::table('activity_log')
            ->where('subject_type', User::class)
            ->where('subject_id', $technician->id)
            ->where('description', 'like', '%Team changed%')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                $props = json_decode($log->properties, true);
                return [
                    'date' => Carbon::parse($log->created_at)->format('Y-m-d H:i'),
                    'from' => $props['old_supervisor'] ?? 'Unknown',
                    'to' => $props['new_supervisor'] ?? 'Unknown',
                ];
            })
            ->toArray();
    }
}
