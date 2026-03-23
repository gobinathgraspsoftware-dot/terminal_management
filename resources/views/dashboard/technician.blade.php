@extends('layouts.app')

@section('title', 'Technician Dashboard - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-person-gear me-2"></i> My Dashboard</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div>
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
                        <a href="{{ route('technician.tickets.index') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-ticket-detailed me-1"></i> My Tickets
                        </a>
                        @endcan
                        @can('view_inventory')
                        <a href="{{ route('technician.inventory-serials.index') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-upc-scan me-1"></i> My Stock
                        </a>
                        @endcan
                        @can('view_claims')
                        <a href="{{ route('technician.claims.index') }}" class="btn btn-sm btn-warning">
                            <i class="bi bi-folder2-open me-1"></i> My Claims
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            @include('partials.dashboard-widget', [
                'icon'        => 'calendar-check',
                'iconBg'      => 'primary',
                'value'       => $stats['today_jobs'],
                'label'       => "Today's Jobs",
                'refreshable' => true,
                'widgetId'    => 'tech_today_jobs',
            ])
        </div>
        <div class="col-lg-4 col-md-6">
            @include('partials.dashboard-widget', [
                'icon'   => 'arrow-repeat',
                'iconBg' => 'info',
                'value'  => $stats['jobs_by_status']['in_progress'] ?? 0,
                'label'  => 'Jobs In Progress',
            ])
        </div>
        <div class="col-lg-4 col-md-6">
            @include('partials.dashboard-widget', [
                'icon'   => 'check-circle',
                'iconBg' => 'success',
                'value'  => $stats['jobs_by_status']['completed'] ?? 0,
                'label'  => 'Completed',
            ])
        </div>
    </div>

    <!-- Commission Summary -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-cash-coin me-2"></i> Commission Summary
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="border-end">
                                <h4 class="text-primary mb-0">RM {{ number_format($stats['commission_summary']['this_month'], 2) }}</h4>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border-end">
                                <h4 class="text-warning mb-0">RM {{ number_format($stats['commission_summary']['pending'], 2) }}</h4>
                                <small class="text-muted">Pending Payout</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="text-success mb-0">RM {{ number_format($stats['commission_summary']['paid_ytd'], 2) }}</h4>
                            <small class="text-muted">Paid (YTD)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Status Breakdown -->
    @if(!empty($stats['jobs_by_status']))
    <div class="row g-3 mb-4">
        @foreach($stats['jobs_by_status'] as $status => $count)
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

    <!-- Today's Jobs List -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-check me-2"></i> Today's Jobs</span>
                    <span class="badge bg-primary">{{ count($today_jobs_list) }} jobs</span>
                </div>
                <div class="card-body p-0">
                    @forelse($today_jobs_list as $job)
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold">
                                        {{ $job->job_no ?? 'N/A' }}
                                        @php
                                            $priorityColors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
                                            $priority = $job->priority ?? 'medium';
                                        @endphp
                                        <span class="badge bg-{{ $priorityColors[$priority] ?? 'secondary' }} ms-1">
                                            {{ ucfirst($priority) }}
                                        </span>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        <i class="bi bi-shop me-1"></i> {{ $job->client_name ?? 'N/A' }}
                                    </div>
                                    <div class="text-muted small">
                                        <i class="bi bi-geo-alt me-1"></i> {{ $job->site_name ?? 'N/A' }}
                                        @if(!empty($job->site_address))
                                            — {{ \Illuminate\Support\Str::limit($job->site_address, 50) }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    @php
                                        $jobStatusColors = [
                                            'pending_assignment' => 'secondary',
                                            'assigned'           => 'info',
                                            'in_progress'        => 'primary',
                                            'completed'          => 'success',
                                            'failed'             => 'danger',
                                        ];
                                        $jobStatus = $job->status ?? 'assigned';
                                    @endphp
                                    <span class="badge bg-{{ $jobStatusColors[$jobStatus] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $jobStatus)) }}
                                    </span>
                                    @if(!empty($job->scheduled_time))
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-clock"></i> {{ $job->scheduled_time }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x fs-1"></i>
                            <p class="mt-2">No jobs scheduled for today</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Completed Jobs -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i> Recent Completed Jobs
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Job No</th>
                                    <th>Client</th>
                                    <th>Site</th>
                                    <th>Completed</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_jobs as $job)
                                    <tr>
                                        <td class="fw-semibold">{{ $job->job_no ?? 'N/A' }}</td>
                                        <td>{{ $job->client_name ?? 'N/A' }}</td>
                                        <td>{{ $job->site_name ?? 'N/A' }}</td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $job->completed_at ? \Carbon\Carbon::parse($job->completed_at)->diffForHumans() : '-' }}
                                            </small>
                                        </td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No recent completed jobs</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
    // Auto-refresh every 5 minutes
    setInterval(function() { location.reload(); }, 300000);
});
</script>
@endpush
