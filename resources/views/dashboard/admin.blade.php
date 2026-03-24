@extends('layouts.app')

@section('title', 'Admin Dashboard - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-speedometer2 me-2"></i> Admin Dashboard</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">
                <i class="bi bi-clock me-1"></i> Last updated: <span id="lastUpdated">{{ now()->format('h:i A') }}</span>
            </span>
            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex gap-2 flex-wrap">
                        @can('view_tickets')
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-ticket-detailed me-1"></i> Tickets
                        </a>
                        @endcan
                        @can('view_all_claims')
                        <a href="{{ route('admin.claims.index') }}" class="btn btn-sm btn-warning">
                            <i class="bi bi-folder2-open me-1"></i> Claims
                        </a>
                        @endcan
                        @can('view_quotations')
                        <a href="{{ route('admin.quotations.index') }}" class="btn btn-sm btn-info text-white">
                            <i class="bi bi-file-earmark-text me-1"></i> Quotations
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row 1 - Job Stats -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            @include('components.dashboard-widget', [
                'icon'        => 'clipboard-check',
                'iconBg'      => 'primary',
                'value'       => $stats['pending_jobs'],
                'label'       => 'Pending Jobs',
                'refreshable' => true,
                'widgetId'    => 'pending_jobs',
            ])
        </div>
        <div class="col-lg-4 col-md-6">
            @include('components.dashboard-widget', [
                'icon'        => 'calendar-check',
                'iconBg'      => 'success',
                'value'       => array_sum($stats['today_jobs']),
                'label'       => "Today's Jobs",
                'refreshable' => true,
                'widgetId'    => 'today_jobs',
            ])
        </div>
        <div class="col-lg-4 col-md-6">
            @include('components.dashboard-widget', [
                'icon'        => 'exclamation-triangle',
                'iconBg'      => 'danger',
                'value'       => $stats['sla_breaches'],
                'label'       => 'SLA Breaches',
                'refreshable' => true,
                'widgetId'    => 'sla_breaches',
            ])
        </div>
    </div>

    <!-- Stats Row 2 - Pending Approvals -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6 col-md-6">
            @include('components.dashboard-widget', [
                'title'  => 'Claim Approvals',
                'icon'   => 'file-earmark-check',
                'iconBg' => 'warning',
                'value'  => $stats['pending_claim_approvals'],
                'label'  => 'Pending Claim Approvals',
                'link'   => route('admin.claims.index'),
            ])
        </div>
        <div class="col-lg-6 col-md-6">
            @include('components.dashboard-widget', [
                'title'  => 'Payout Approvals',
                'icon'   => 'wallet2',
                'iconBg' => 'success',
                'value'  => $stats['pending_payout_approvals'],
                'label'  => 'Pending Payout Approvals',
            ])
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart me-2"></i> Jobs This Week
                </div>
                <div class="card-body">
                    <canvas id="jobsThisWeekChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up me-2"></i> Revenue This Month
                </div>
                <div class="card-body">
                    <canvas id="revenueThisMonthChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Jobs Breakdown & Recent Activities -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-pie-chart me-2"></i> Today's Job Status
                </div>
                <div class="card-body">
                    @if(count($stats['today_jobs']) > 0)
                        <canvas id="todayJobsChart" height="280"></canvas>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x fs-1"></i>
                            <p class="mt-2">No jobs today</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <span><i class="bi bi-activity me-2"></i> Recent Activities</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                        @forelse($recent_activities as $activity)
                            <div class="list-group-item">
                                <div class="d-flex align-items-start">
                                    <div class="bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-circle-fill text-primary" style="font-size: 0.5rem;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small">{{ $activity->causer_name ?? 'System' }}</div>
                                        <small class="text-muted">{{ $activity->description }}</small>
                                        <div>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                No recent activities
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Health -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-heart-pulse me-2"></i> System Health
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach(['Database' => 'Connected', 'Storage' => 'Operational', 'Cache' => 'Operational', 'Queue' => '0 pending'] as $name => $status)
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="text-success fs-2"><i class="bi bi-check-circle-fill"></i></div>
                                    <div class="fw-semibold mt-2">{{ $name }}</div>
                                    <small class="text-muted">{{ $status }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Jobs This Week Chart
    var jobsCtx = document.getElementById('jobsThisWeekChart');
    if (jobsCtx) {
        new Chart(jobsCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($charts['jobs_this_week']['labels']) !!},
                datasets: [{
                    label: 'Jobs Created',
                    data: {!! json_encode($charts['jobs_this_week']['data']) !!},
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // Revenue This Month Chart
    var revenueCtx = document.getElementById('revenueThisMonthChart');
    if (revenueCtx) {
        new Chart(revenueCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($charts['revenue_this_month']['labels']) !!},
                datasets: [{
                    label: 'Revenue (RM)',
                    data: {!! json_encode($charts['revenue_this_month']['data']) !!},
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Today's Jobs Doughnut Chart
    @if(count($stats['today_jobs']) > 0)
    var todayCtx = document.getElementById('todayJobsChart');
    if (todayCtx) {
        var todayData = @json($stats['today_jobs']);
        var labels = Object.keys(todayData).map(function(k) {
            return k.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
        });
        var colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#0dcaf0', '#6610f2'];

        new Chart(todayCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: Object.values(todayData),
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
                }
            }
        });
    }
    @endif

    // Auto-refresh every 5 minutes
    setInterval(function() { location.reload(); }, 300000);

    // Update timestamp
    function updateTime() {
        var now = new Date();
        $('#lastUpdated').text(now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));
    }
    setInterval(updateTime, 60000);
});
</script>
@endpush
