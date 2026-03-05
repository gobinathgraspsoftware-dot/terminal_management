@extends('layouts.app')

@section('title', 'Supervisor Dashboard - TMS')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-people me-2"></i> Supervisor Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div>
            <span class="text-muted">My Team Performance</span>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('supervisor.teams.index') }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i> Assign Jobs
                    </a>
                    <a href="{{ route('supervisor.teams.index') }}" class="btn btn-success">
                        <i class="bi bi-people me-2"></i> View Team
                    </a>
                    <a href="{{ route('supervisor.purchase-orders.index') }}" class="btn btn-warning">
                        <i class="bi bi-file-earmark-check me-2"></i> Pending Approvals
                    </a>
                    <a href="{{ route('supervisor.stock-reports.index') }}" class="btn btn-info">
                        <i class="bi bi-graph-up me-2"></i> Team Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Team Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <x-dashboard-widget
            icon="calendar-check"
            iconBg="primary"
            :value="$stats['team_jobs_today']"
            label="Team Jobs Today"
            :refreshable="true"
            widgetId="team_jobs_today"
        />
    </div>

    <div class="col-lg-3 col-md-6">
        <x-dashboard-widget
            icon="people"
            iconBg="success"
            :value="$stats['team_members']['total']"
            label="Total Team Members"
            :link="route('supervisor.teams.index')"
        >
            <div class="d-flex justify-content-between text-sm">
                <span class="text-success">
                    <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                    Active: {{ $stats['team_members']['active'] }}
                </span>
                <span class="text-muted">
                    <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                    Inactive: {{ $stats['team_members']['inactive'] }}
                </span>
            </div>
        </x-dashboard-widget>
    </div>

    <div class="col-lg-3 col-md-6">
        <x-dashboard-widget
            icon="speedometer"
            iconBg="info"
            :value="$stats['team_sla_performance']['rate'] . '%'"
            label="SLA Compliance Rate"
            :trend="$stats['team_sla_performance']['rate'] >= 90 ? 'up' : 'down'"
            :trendValue="$stats['team_sla_performance']['rate'] >= 90 ? 'Excellent' : 'Needs Improvement'"
        >
            <small class="text-muted">
                {{ $stats['team_sla_performance']['on_time'] }} / {{ $stats['team_sla_performance']['total'] }} jobs on time
            </small>
        </x-dashboard-widget>
    </div>

    <div class="col-lg-3 col-md-6">
        <x-dashboard-widget
            icon="file-earmark-check"
            iconBg="warning"
            :value="$stats['pending_claims']"
            label="Pending Claim Approvals"
        />
    </div>
</div>

<!-- Job Status Breakdown -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clipboard-data me-2"></i> Team Job Status Breakdown
            </div>
            <div class="card-body">
                @if(count($stats['job_status_breakdown']) > 0)
                    <div class="row g-3">
                        @foreach($stats['job_status_breakdown'] as $status => $count)
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-uppercase text-muted small">{{ str_replace('_', ' ', $status) }}</div>
                                            <div class="h3 mb-0">{{ $count }}</div>
                                        </div>
                                        <div class="text-primary fs-2">
                                            <i class="bi bi-{{ 
                                                $status === 'assigned' ? 'person-check' : 
                                                ($status === 'in_progress' ? 'hourglass-split' : 
                                                ($status === 'pending_confirmation' ? 'clock-history' : 'clipboard-check'))
                                            }}"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-3">No active jobs</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-trophy me-2"></i> Top Performers (This Month)
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($stats['top_performers'] as $index => $performer)
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'info') }} rounded-circle" 
                                          style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                                        {{ $index + 1 }}
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $performer->name }}</div>
                                    <small class="text-muted">
                                        {{ $performer->jobs_completed }} jobs • 
                                        RM {{ number_format($performer->total_commission, 2) }}
                                    </small>
                                </div>
                                @if($index === 0)
                                    <i class="bi bi-trophy-fill text-warning"></i>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">
                            No data available
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <!-- Team Jobs This Week -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i> Team Jobs This Week
            </div>
            <div class="card-body">
                <canvas id="teamJobsWeekChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- SLA Compliance Trend -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up me-2"></i> SLA Compliance Trend (7 Days)
            </div>
            <div class="card-body">
                <canvas id="slaComplianceChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Team Members List -->
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2"></i> My Team Members</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="teamMembersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($team_members_list as $member)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 35px; height: 35px;">
                                                {{ strtoupper(substr($member->name, 0, 1)) }}
                                            </div>
                                            <strong>{{ $member->name }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $member->email }}</td>
                                    <td>{{ $member->phone ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $member->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($member->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('supervisor.teams.show', $member->id) }}" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No team members found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable for team members
    $('#teamMembersTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']],
        language: {
            search: "Search team members:"
        }
    });

    // Team Jobs This Week Chart
    const teamJobsWeekCtx = document.getElementById('teamJobsWeekChart').getContext('2d');
    new Chart(teamJobsWeekCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($charts['team_jobs_this_week']['labels']) !!},
            datasets: [{
                label: 'Team Jobs',
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
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // SLA Compliance Trend Chart
    const slaComplianceCtx = document.getElementById('slaComplianceChart').getContext('2d');
    new Chart(slaComplianceCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($charts['sla_compliance_trend']['labels']) !!},
            datasets: [{
                label: 'Compliance %',
                data: {!! json_encode($charts['sla_compliance_trend']['data']) !!},
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush