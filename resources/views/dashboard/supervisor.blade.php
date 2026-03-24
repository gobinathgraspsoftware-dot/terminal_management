@extends('layouts.app')

@section('title', 'Supervisor Dashboard - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-people me-2"></i> Supervisor Dashboard</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-{{ $is_internal ? 'success' : 'info' }}">
                {{ ucfirst($supervisor_type ?? 'internal') }} Supervisor
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
                        <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-ticket-detailed me-1"></i> Tickets
                        </a>
                        @endcan
                        @if($is_internal)
                        <a href="{{ route('supervisor.teams.index') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-people me-1"></i> My Team
                        </a>
                        @endif
                        @can('view_claims')
                        <a href="{{ route('supervisor.claims.index') }}" class="btn btn-sm btn-warning">
                            <i class="bi bi-folder2-open me-1"></i> Claims
                        </a>
                        @endcan
                        @can('view_inventory_management')
                        <a href="{{ route('supervisor.inventory-management.index') }}" class="btn btn-sm btn-info text-white">
                            <i class="bi bi-box-seam me-1"></i> Inventory
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row 1 -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            @include('components.dashboard-widget', [
                'icon'        => 'calendar-check',
                'iconBg'      => 'primary',
                'value'       => $stats['team_jobs_today'],
                'label'       => $is_internal ? "Team Jobs Today" : "My Jobs Today",
                'refreshable' => true,
                'widgetId'    => 'team_jobs_today',
            ])
        </div>
        <div class="col-lg-3 col-md-6">
            @include('components.dashboard-widget', [
                'icon'   => 'people',
                'iconBg' => 'success',
                'value'  => $stats['team_members']['total'] ?? 0,
                'label'  => 'Team Members',
            ])
        </div>
        <div class="col-lg-3 col-md-6">
            @include('components.dashboard-widget', [
                'icon'   => 'speedometer2',
                'iconBg' => 'info',
                'value'  => ($stats['team_sla_performance']['rate'] ?? 0) . '%',
                'label'  => 'SLA Compliance',
            ])
        </div>
        <div class="col-lg-3 col-md-6">
            @include('components.dashboard-widget', [
                'icon'   => 'file-earmark-check',
                'iconBg' => 'warning',
                'value'  => $stats['pending_claims'],
                'label'  => 'Pending Claims',
                'link'   => route('supervisor.claims.index'),
            ])
        </div>
    </div>

    <!-- Job Status Breakdown -->
    @if(!empty($stats['job_status_breakdown']))
    <div class="row g-3 mb-4">
        @foreach($stats['job_status_breakdown'] as $status => $count)
            @php
                $statusColors = [
                    'pending_assignment' => 'secondary',
                    'assigned'           => 'info',
                    'in_progress'        => 'primary',
                    'completed'          => 'success',
                    'failed'             => 'danger',
                    'cancelled'          => 'dark',
                ];
                $color = $statusColors[$status] ?? 'secondary';
                $statusLabel = ucwords(str_replace('_', ' ', $status));
            @endphp
            <div class="col-md-2 col-4">
                <div class="card border-{{ $color }}">
                    <div class="card-body text-center py-2">
                        <small class="text-muted d-block">{{ $statusLabel }}</small>
                        <h4 class="text-{{ $color }} mb-0">{{ $count }}</h4>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart me-2"></i> {{ $is_internal ? 'Team' : 'My' }} Jobs This Week
                </div>
                <div class="card-body">
                    <canvas id="teamJobsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up me-2"></i> SLA Compliance Trend
                </div>
                <div class="card-body">
                    <canvas id="slaChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    @if($is_internal)
    <!-- Top Performers & Team Members -->
    <div class="row g-3 mb-4">
        <!-- Top Performers -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-trophy me-2"></i> Top Performers
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($stats['top_performers'] as $index => $performer)
                            <div class="list-group-item d-flex align-items-center">
                                <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'dark') }} rounded-pill me-3">
                                    {{ $index + 1 }}
                                </span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $performer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $performer->completed_jobs ?? 0 }} jobs completed</small>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                No data available yet
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Members -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-2"></i> Team Members</span>
                    <a href="{{ route('supervisor.teams.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($team_members_list as $member)
                                    <tr>
                                        <td class="fw-semibold">{{ $member->name }}</td>
                                        <td><small>{{ $member->email }}</small></td>
                                        <td><small>{{ $member->phone ?? '-' }}</small></td>
                                        <td>
                                            <span class="badge bg-{{ $member->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($member->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No team members</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Team Jobs This Week Chart
    var teamCtx = document.getElementById('teamJobsChart');
    if (teamCtx) {
        new Chart(teamCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($charts['team_jobs_this_week']['labels']) !!},
                datasets: [{
                    label: 'Jobs',
                    data: {!! json_encode($charts['team_jobs_this_week']['data']) !!},
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

    // SLA Compliance Trend Chart
    var slaCtx = document.getElementById('slaChart');
    if (slaCtx) {
        new Chart(slaCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($charts['sla_compliance_trend']['labels']) !!},
                datasets: [{
                    label: 'SLA Compliance %',
                    data: {!! json_encode($charts['sla_compliance_trend']['data']) !!},
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
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: function(v) { return v + '%'; } } }
                }
            }
        });
    }

    // Auto-refresh every 5 minutes
    setInterval(function() { location.reload(); }, 300000);
});
</script>
@endpush
