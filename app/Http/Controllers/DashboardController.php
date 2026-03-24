<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display role-based dashboard
     */
    public function index()
    {
        $user = auth()->user();
        $role = $user->roles->first()?->name;

        try {
            return match ($role) {
                'admin' => view('dashboard.admin', $this->getAdminData()),
                'supervisor' => view('dashboard.supervisor', $this->getSupervisorData()),
                'technician' => view('dashboard.technician', $this->getTechnicianData()),
                default => redirect()->route('login'),
            };
        } catch (\Exception $e) {
            Log::error('Dashboard Error [' . $role . ']: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Return view with empty data to avoid white screen
            return match ($role) {
                'admin' => view('dashboard.admin', $this->getEmptyAdminData()),
                'supervisor' => view('dashboard.supervisor', $this->getEmptySupervisorData()),
                'technician' => view('dashboard.technician', $this->getEmptyTechnicianData()),
                default => redirect()->route('login'),
            };
        }
    }

    /**
     * Get Admin Dashboard Data
     */
    protected function getAdminData(): array
    {
        return [
            'stats' => [
                'pending_jobs' => $this->getPendingJobsCount(),
                'today_jobs' => $this->getTodayJobsStats(),
                'pending_po_approvals' => 0, // PO module removed
                'pending_claim_approvals' => $this->getPendingClaimApprovals(),
                'pending_payout_approvals' => $this->getPendingPayoutApprovals(),
                'sla_breaches' => $this->getSLABreaches(),
            ],
            'charts' => [
                'jobs_this_week' => $this->getJobsThisWeek(),
                'revenue_this_month' => $this->getRevenueThisMonth(),
            ],
            'recent_activities' => $this->getRecentActivities(),
        ];
    }

    /**
     * Get Supervisor Dashboard Data
     *
     * Internal supervisors: full team data + team member list
     * External supervisors: own job data only, no team sections
     */
    protected function getSupervisorData(): array
    {
        $user = auth()->user();
        $isInternal = $user->isInternalSupervisor();
        $isExternal = $user->isExternalSupervisor();

        $data = [
            // Pass supervisor type flags for conditional rendering in view
            'supervisor_type' => $user->supervisor_type ?? 'internal',
            'is_internal' => $isInternal,
            'is_external' => $isExternal,

            'stats' => [
                'team_jobs_today' => $this->getTeamJobsToday($user),
                'job_status_breakdown' => $this->getTeamJobStatusBreakdown($user),
                'team_sla_performance' => $this->getTeamSLAPerformance($user),
                'team_members' => $isInternal ? $this->getTeamMembersStatus($user) : ['total' => 0, 'active' => 0, 'inactive' => 0],
                'pending_claims' => $isInternal ? $this->getTeamPendingClaims($user) : 0,
                'top_performers' => $isInternal ? $this->getTeamTopPerformers($user) : [],
            ],
            'charts' => [
                'team_jobs_this_week' => $this->getTeamJobsThisWeek($user),
                'sla_compliance_trend' => $this->getTeamSLAComplianceTrend($user),
            ],
            'team_members_list' => $isInternal ? $this->getTeamMembers($user) : [],
        ];

        return $data;
    }

    /**
     * Get Technician Dashboard Data
     */
    protected function getTechnicianData(): array
    {
        $user = auth()->user();

        return [
            'stats' => [
                'today_jobs' => $this->getTechnicianTodayJobs($user),
                'jobs_by_status' => $this->getTechnicianJobsByStatus($user),
                'commission_summary' => $this->getTechnicianCommissionSummary($user),
            ],
            'today_jobs_list' => $this->getTechnicianTodayJobsList($user),
            'recent_jobs' => $this->getTechnicianRecentJobs($user),
        ];
    }

    // ==========================================
    // ADMIN DASHBOARD METHODS
    // ==========================================

    protected function getPendingJobsCount(): int
    {
        return DB::table('job_orders')
            ->whereIn('status', ['pending_assignment', 'assigned'])
            ->count();
    }

    protected function getTodayJobsStats(): array
    {
        $today = Carbon::today();

        return DB::table('job_orders')
            ->whereDate('created_at', $today)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    // PO module removed — return 0
    protected function getPendingPOApprovals(): int
    {
        return 0;
    }

    protected function getPendingClaimApprovals(): int
    {
        return DB::table('claims')
            ->where('status', 'pending_approval')
            ->count();
    }

    protected function getPendingPayoutApprovals(): int
    {
        return DB::table('payout_batches')
            ->where('status', 'pending_approval')
            ->count();
    }

    protected function getSLABreaches(): int
    {
        return DB::table('job_orders')
            ->where('sla_status', 'breached')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }

    protected function getJobsThisWeek(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $jobs = DB::table('job_orders')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        for ($date = $startOfWeek->copy(); $date <= $endOfWeek; $date->addDay()) {
            $labels[] = $date->format('D');
            $dateStr = $date->format('Y-m-d');
            $data[] = $jobs->firstWhere('date', $dateStr)?->count ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    protected function getRevenueThisMonth(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $revenue = DB::table('invoices')
            ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
            ->where('status', '!=', 'cancelled')
            ->select(DB::raw('DATE(invoice_date) as date'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        // Group by week for monthly view
        $weeks = [];
        for ($i = 0; $i < 5; $i++) {
            $weekStart = $startOfMonth->copy()->addWeeks($i);
            if ($weekStart > $endOfMonth) break;

            $weekEnd = $weekStart->copy()->addWeek()->min($endOfMonth);
            $weeks[] = ['start' => $weekStart, 'end' => $weekEnd];
            $labels[] = 'Week ' . ($i + 1);
        }

        foreach ($weeks as $week) {
            $weekTotal = $revenue
                ->filter(fn($r) => $r->date >= $week['start']->format('Y-m-d') && $r->date <= $week['end']->format('Y-m-d'))
                ->sum('total');
            $data[] = round($weekTotal, 2);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    protected function getRecentActivities(int $limit = 10): array
    {
        return DB::table('activity_log')
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->select(
                'activity_log.description',
                'activity_log.created_at',
                DB::raw("COALESCE(users.name, 'System') as user_name")
            )
            ->orderBy('activity_log.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // ==========================================
    // SUPERVISOR DASHBOARD METHODS
    // ==========================================

    /**
     * Build the job scope closure for the current supervisor.
     *
     * Internal: supervisor_id = self OR technician_id IN (team tech ids)
     * External: supervisor_id = self only (no team technicians)
     */
    protected function supervisorJobScope($user): \Closure
    {
        return function ($query) use ($user) {
            $query->where('supervisor_id', $user->id);

            // Internal supervisors also see their technicians' jobs
            if ($user->isInternalSupervisor()) {
                $query->orWhereIn('technician_id', function ($q) use ($user) {
                    $q->select('id')
                        ->from('users')
                        ->where('supervisor_id', $user->id);
                });
            }
        };
    }

    protected function getTeamJobsToday($user): int
    {
        $today = Carbon::today();

        return DB::table('job_orders')
            ->where($this->supervisorJobScope($user))
            ->whereDate('created_at', $today)
            ->count();
    }

    protected function getTeamJobStatusBreakdown($user): array
    {
        return DB::table('job_orders')
            ->where($this->supervisorJobScope($user))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function getTeamSLAPerformance($user): array
    {
        $total = DB::table('job_orders')
            ->where($this->supervisorJobScope($user))
            ->whereIn('status', ['completed'])
            ->count();

        $onTime = DB::table('job_orders')
            ->where($this->supervisorJobScope($user))
            ->whereIn('status', ['completed'])
            ->where('sla_status', 'on_track')
            ->count();

        return [
            'total' => $total,
            'on_time' => $onTime,
            'rate' => $total > 0 ? round(($onTime / $total) * 100, 2) : 0,
        ];
    }

    protected function getTeamMembersStatus($user): array
    {
        $teamMembers = DB::table('users')
            ->where('supervisor_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($teamMembers),
            'active' => $teamMembers['active'] ?? 0,
            'inactive' => $teamMembers['inactive'] ?? 0,
        ];
    }

    protected function getTeamPendingClaims($user): int
    {
        return DB::table('claims')
            ->whereIn('technician_id', function($q) use ($user) {
                $q->select('id')
                    ->from('users')
                    ->where('supervisor_id', $user->id);
            })
            ->where('status', 'pending_approval')
            ->count();
    }

    protected function getTeamTopPerformers($user, int $limit = 5): array
    {
        return DB::table('job_orders')
            ->join('users', 'job_orders.technician_id', '=', 'users.id')
            ->where(function($query) use ($user) {
                $query->where('job_orders.supervisor_id', $user->id)
                    ->orWhere('users.supervisor_id', $user->id);
            })
            ->where('job_orders.status', 'completed')
            ->whereBetween('job_orders.completed_at', [Carbon::now()->startOfMonth(), Carbon::now()])
            ->select(
                'users.name',
                DB::raw('count(*) as jobs_completed'),
                DB::raw('SUM(job_orders.commission_amount) as total_commission')
            )
            ->groupBy('users.id', 'users.name')
            ->orderBy('jobs_completed', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    protected function getTeamJobsThisWeek($user): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $jobs = DB::table('job_orders')
            ->where($this->supervisorJobScope($user))
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        for ($date = $startOfWeek->copy(); $date <= $endOfWeek; $date->addDay()) {
            $labels[] = $date->format('D');
            $dateStr = $date->format('Y-m-d');
            $data[] = $jobs->firstWhere('date', $dateStr)?->count ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    protected function getTeamSLAComplianceTrend($user): array
    {
        $last7Days = [];
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $last7Days[] = $date->format('Y-m-d');
            $labels[] = $date->format('D');
        }

        foreach ($last7Days as $date) {
            $total = DB::table('job_orders')
                ->where($this->supervisorJobScope($user))
                ->whereDate('completed_at', $date)
                ->count();

            $onTime = DB::table('job_orders')
                ->where($this->supervisorJobScope($user))
                ->whereDate('completed_at', $date)
                ->where('sla_status', 'on_track')
                ->count();

            $data[] = $total > 0 ? round(($onTime / $total) * 100, 2) : 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    protected function getTeamMembers($user): array
    {
        // External supervisors have no team members
        if ($user->isExternalSupervisor()) {
            return [];
        }

        return DB::table('users')
            ->where('supervisor_id', $user->id)
            ->select('id', 'name', 'email', 'phone', 'status')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    // ==========================================
    // TECHNICIAN DASHBOARD METHODS
    // ==========================================

    protected function getTechnicianTodayJobs($user): int
    {
        $today = Carbon::today();

        return DB::table('job_orders')
            ->where('technician_id', $user->id)
            ->whereDate('scheduled_date', $today)
            ->count();
    }

    protected function getTechnicianJobsByStatus($user): array
    {
        return DB::table('job_orders')
            ->where('technician_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function getTechnicianCommissionSummary($user): array
    {
        $thisMonth = DB::table('job_orders')
            ->where('technician_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [Carbon::now()->startOfMonth(), Carbon::now()])
            ->sum('commission_amount');

        $pending = DB::table('job_orders')
            ->where('technician_id', $user->id)
            ->where('status', 'completed')
            ->whereNull('invoice_id') // Not yet invoiced/paid
            ->sum('commission_amount');

        $paid = DB::table('payout_lines')
            ->where('technician_id', $user->id)
            ->where('status', 'paid')
            ->whereYear('payment_date', Carbon::now()->year)
            ->sum('commission_amount');

        return [
            'this_month' => round($thisMonth ?? 0, 2),
            'pending' => round($pending ?? 0, 2),
            'paid_ytd' => round($paid ?? 0, 2),
        ];
    }

    protected function getTechnicianTodayJobsList($user): array
    {
        $today = Carbon::today();

        return DB::table('job_orders')
            ->join('clients', 'job_orders.client_id', '=', 'clients.id')
            ->leftJoin('sites', 'job_orders.site_id', '=', 'sites.id')
            ->where('job_orders.technician_id', $user->id)
            ->whereDate('job_orders.scheduled_date', $today)
            ->select(
                'job_orders.*',
                'clients.client_name as client_name',
                'sites.site_name as site_name',
                'sites.address as site_address'
            )
            ->orderBy('job_orders.priority', 'desc')
            ->orderBy('job_orders.scheduled_time', 'asc')
            ->get()
            ->toArray();
    }

    protected function getTechnicianRecentJobs($user, int $limit = 5): array
    {
        return DB::table('job_orders')
            ->join('clients', 'job_orders.client_id', '=', 'clients.id')
            ->leftJoin('sites', 'job_orders.site_id', '=', 'sites.id')
            ->where('job_orders.technician_id', $user->id)
            ->where('job_orders.status', 'completed')
            ->select(
                'job_orders.*',
                'clients.client_name as client_name',
                'sites.site_name as site_name'
            )
            ->orderBy('job_orders.completed_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // ==========================================
    // AJAX WIDGET ENDPOINTS
    // ==========================================

    /**
     * Get widget data via AJAX (for real-time updates)
     */
    public function getWidgetData(Request $request)
    {
        try {
            $widget = $request->input('widget');
            $user = auth()->user();
            $role = $user->roles->first()?->name;

            $data = match ($widget) {
                'pending_jobs' => ['count' => $this->getPendingJobsCount()],
                'today_jobs' => ['count' => array_sum($this->getTodayJobsStats())],
                'sla_breaches' => ['count' => $this->getSLABreaches()],
                'team_jobs_today' => ['count' => $this->getTeamJobsToday($user)],
                'tech_today_jobs' => ['count' => $this->getTechnicianTodayJobs($user)],
                default => ['error' => 'Invalid widget'],
            };

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Widget Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load data'], 500);
        }
    }

    // ==========================================
    // EMPTY DATA FALLBACKS (for error handling)
    // ==========================================

    protected function getEmptyAdminData(): array
    {
        return [
            'stats' => [
                'pending_jobs' => 0,
                'today_jobs' => [],
                'pending_po_approvals' => 0,
                'pending_claim_approvals' => 0,
                'pending_payout_approvals' => 0,
                'sla_breaches' => 0,
            ],
            'charts' => [
                'jobs_this_week' => ['labels' => [], 'data' => []],
                'revenue_this_month' => ['labels' => [], 'data' => []],
            ],
            'recent_activities' => [],
        ];
    }

    protected function getEmptySupervisorData(): array
    {
        $user = auth()->user();

        return [
            'supervisor_type' => $user->supervisor_type ?? 'internal',
            'is_internal' => $user->isInternalSupervisor(),
            'is_external' => $user->isExternalSupervisor(),
            'stats' => [
                'team_jobs_today' => 0,
                'job_status_breakdown' => [],
                'team_sla_performance' => ['total' => 0, 'on_time' => 0, 'rate' => 0],
                'team_members' => ['total' => 0, 'active' => 0, 'inactive' => 0],
                'pending_claims' => 0,
                'top_performers' => [],
            ],
            'charts' => [
                'team_jobs_this_week' => ['labels' => [], 'data' => []],
                'sla_compliance_trend' => ['labels' => [], 'data' => []],
            ],
            'team_members_list' => [],
        ];
    }

    protected function getEmptyTechnicianData(): array
    {
        return [
            'stats' => [
                'today_jobs' => 0,
                'jobs_by_status' => [],
                'commission_summary' => ['this_month' => 0, 'pending' => 0, 'paid_ytd' => 0],
            ],
            'today_jobs_list' => [],
            'recent_jobs' => [],
        ];
    }
}
